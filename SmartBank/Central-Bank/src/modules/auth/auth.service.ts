import { Injectable } from '@nestjs/common';
import { JwtService } from '@nestjs/jwt';
import * as bcrypt from 'bcrypt';
import { randomUUID } from 'crypto';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';
import { generateAccountNumber } from '../../common/account-number';
import { IdempotencyService } from '../idempotency/idempotency.service';
import { PrismaService } from '../prisma/prisma.service';
import { Prisma } from '@prisma/client';
import { SettlementService } from '../settlement/settlement.service';
import { AuditLogService } from '../audit/audit-log.service';

function jsonSafe(value: unknown) {
  return JSON.parse(JSON.stringify(value, (_key, current) => (typeof current === 'bigint' ? current.toString() : current)));
}

function normalizeIndonesianPhone(phone: string): string {
  const digits = phone.replace(/\D/g, '');
  return digits.startsWith('62') ? `+${digits}` : `+62${digits.slice(1)}`;
}

@Injectable()
export class AuthService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly jwt: JwtService,
    private readonly settlement: SettlementService,
    private readonly idempotency: IdempotencyService,
    private readonly audit: AuditLogService,
  ) {}

  async register(input: {
    name: string;
    email: string;
    phone?: string;
    password: string;
    pin?: string;
    requestId: string;
    idempotencyKey: string;
    requestHash: string;
    ip: string;
  }) {
    const passwordHash = await bcrypt.hash(input.password, 10);
    const pinHash = input.pin ? await bcrypt.hash(input.pin, 10) : null;
    const userId = randomUUID();
    const walletId = randomUUID();
    const registerIdempotency = {
      key: input.idempotencyKey,
      route: 'POST /api/v1/auth/register',
      actorId: input.email,
      requestHash: input.requestHash,
    };
    const existingResponse = await this.prisma.$transaction(async (tx) => {
      const idem = await this.idempotency.start(tx, registerIdempotency);
      if (idem.replay) return idem.response;
      // Generate a unique account number; retry on collision (10^9 space,
      // collision chance is negligible but we must handle P2002 cleanly).
      let accountNumber: string | null = null;
      for (let attempt = 0; attempt < 5; attempt++) {
        const candidate = generateAccountNumber();
        try {
          await tx.user.create({
            data: {
              id: userId,
              name: input.name,
              email: input.email.toLowerCase().trim(),
              passwordHash,
              pinHash,
              role: 'WALLET_USER',
              accountNumber: candidate,
              phone: input.phone ? normalizeIndonesianPhone(input.phone) : null,
            },
          });
          accountNumber = candidate;
          break;
        } catch (err) {
          if (err instanceof Prisma.PrismaClientKnownRequestError && err.code === 'P2002') {
            const target = Array.isArray(err.meta?.target) ? err.meta.target.join(', ') : String(err.meta?.target ?? '');
            if (!target.includes('account_number') && !target.includes('accountNumber')) {
              throw new AppError(
                ErrorCode.VALIDATION_ERROR,
                target.includes('phone') ? 'Nomor telepon sudah terdaftar' : 'Email sudah terdaftar',
              );
            }
            continue;
          }
          throw err;
        }
      }
      if (!accountNumber) {
        throw new AppError(ErrorCode.DATABASE_TRANSACTION_FAILED, 'Gagal menghasilkan nomor rekening unik');
      }
      await tx.walletAccount.create({
        data: {
          id: walletId,
          userId,
          accountType: 'USER_WALLET',
          accountCode: `USER_${userId}`,
        },
      });
      return null;
    });
    if (existingResponse) return existingResponse;
    const distribution = await this.settlement.settleInitialDistribution({
      walletId,
      actorUserId: userId,
      requestId: input.requestId,
      idempotency: {
        key: input.idempotencyKey,
        route: 'INITIAL_DISTRIBUTION',
        actorId: userId,
        requestHash: input.requestHash,
      },
    });
    // Fetch the assigned accountNumber from the freshly created user.
    const created = await this.prisma.user.findUniqueOrThrow({
      where: { id: userId },
      select: { accountNumber: true },
    });

    const response = {
      user_id: userId,
      wallet_id: walletId,
      account_number: created.accountNumber,
      initial_distribution: distribution,
    };
    await this.prisma.$transaction((tx) =>
      this.idempotency.complete(tx, {
        ...registerIdempotency,
        responseBody: jsonSafe(response) as never,
      }),
    );
    await this.audit.record({
      actorUserId: userId,
      serviceName: 'centralbank-core',
      action: 'USER_REGISTERED',
      targetType: 'user',
      targetId: userId,
      requestId: input.requestId,
      metadata: { ip: input.ip },
    });
    return response;
  }

  async login(email: string, password: string, requestId: string, ip: string) {
    const normalizedEmail = email.toLowerCase().trim();
    const user = await this.prisma.user.findUnique({
      where: { email: normalizedEmail },
      include: {
        wallets: {
          where: { accountType: 'USER_WALLET' },
          select: { id: true }
        }
      }
    });
    if (!user || !(await bcrypt.compare(password, user.passwordHash))) {
      await this.audit.record({
        serviceName: 'centralbank-core',
        action: 'LOGIN_FAILED',
        targetType: 'login_email',
        targetId: normalizedEmail,
        requestId,
        metadata: { ip },
      });
      throw new AppError(ErrorCode.UNAUTHORIZED, 'Email atau password salah');
    }
    if (user.status === 'SUSPENDED') {
      await this.audit.record({
        serviceName: 'centralbank-core',
        action: 'LOGIN_FAILED_SUSPENDED',
        targetType: 'login_email',
        targetId: normalizedEmail,
        requestId,
        metadata: { ip },
      });
      throw new AppError(ErrorCode.FORBIDDEN, 'Akun Anda ditangguhkan');
    }
    await this.audit.record({
      actorUserId: user.id,
      serviceName: 'centralbank-core',
      action: 'LOGIN_SUCCESS',
      targetType: 'user',
      targetId: user.id,
      requestId,
      metadata: { ip },
    });
    const token = this.jwt.sign({ sub: user.id, email: user.email, role: user.role, name: user.name });
    return {
      access_token: token,
      expires_in: 3600,
      user_id: user.id,
      name: user.name,
      role: user.role,
      kyc_tier: user.kycTier,
      wallet_id: user.wallets[0]?.id || null,
    };
  }
}
