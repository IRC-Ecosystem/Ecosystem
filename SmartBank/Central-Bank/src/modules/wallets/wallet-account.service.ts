import { Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { PrismaService } from '../prisma/prisma.service';
import { AuditLogService } from '../audit/audit-log.service';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';

// Peran bisnis yang boleh DIAJUKAN user (bukan grant langsung — approval Teller).
const UPGRADEABLE_ROLES = ['MERCHANT', 'SUPPLIER', 'ANALYTICS_VIEWER'];

@Injectable()
export class WalletAccountService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly audit: AuditLogService,
  ) {}

  async getPrimaryWallet(userId: string) {
    return this.prisma.walletAccount.findFirstOrThrow({
      where: { userId, accountType: { in: ['USER_WALLET', 'MERCHANT_WALLET'] } },
    });
  }

  async getWalletByPhone(phone: string) {
    const normalizedPhone = normalizeIndonesianPhone(phone);
    const user = await this.prisma.user.findFirst({
      where: normalizedPhone ? { phone: { in: [phone, normalizedPhone, `0${normalizedPhone.slice(3)}`] } } : { phone },
      select: {
        id: true,
        name: true,
        kycTier: true,
        phone: true,
        status: true,
        wallets: {
          where: { accountType: { in: ['USER_WALLET', 'MERCHANT_WALLET'] } },
          select: { id: true },
          take: 1,
        },
      },
    });
    if (!user || user.wallets.length === 0 || user.status !== 'ACTIVE') {
      throw new NotFoundException('Nomor HP belum terdaftar di SmartBank Wallet');
    }
    return {
      user_id: user.id,
      wallet_id: user.wallets[0].id,
      name: user.name,
      kyc_tier: user.kycTier,
      phone_masked: this.maskPhone(user.phone ?? phone),
    };
  }

  private maskPhone(phone: string): string {
    const digits = phone.replace(/\D/g, '');
    if (digits.length <= 4) return phone;
    return `${phone.slice(0, 3)}xxx-xxx-${digits.slice(-4)}`;
  }

  /**
   * Lookup wallet + counterparty info by account number (10 digit).
   * Used by Wallet when filling a transfer form: user types account number,
   * we resolve it to walletId before submitting to settlement engine.
   *
   * Returns minimal info (account number, holder name, walletId).
   * Does NOT return balance to prevent enumeration attacks.
   */
  async getWalletByAccountNumber(accountNumber: string) {
    const user = await this.prisma.user.findUnique({
      where: { accountNumber },
      select: {
        id: true,
        name: true,
        status: true,
        wallets: {
          where: { accountType: 'USER_WALLET' },
          select: { id: true },
          take: 1,
        },
      },
    });
    if (!user || user.wallets.length === 0) {
      throw new NotFoundException(`Nomor rekening ${accountNumber} tidak ditemukan`);
    }
    if (user.status === 'SUSPENDED') {
      throw new NotFoundException(`Rekening ${accountNumber} tidak aktif`);
    }
    return {
      user_id: user.id,
      account_number: accountNumber,
      holder_name: user.name,
      wallet_id: user.wallets[0].id,
    };
  }

  async getBalance(userId: string) {
    const wallet = await this.getPrimaryWallet(userId);
    const user = await this.prisma.user.findUnique({
      where: { id: userId },
      select: { accountNumber: true, name: true },
    });
    return {
      wallet_id: wallet.id,
      account_number: user?.accountNumber ?? null,
      holder_name: user?.name ?? null,
      currency: wallet.currency,
      available_balance: wallet.availableBalance,
      hold_balance: wallet.holdBalance,
    };
  }

  async getTransactions(userId: string) {
    const wallet = await this.getPrimaryWallet(userId);
    const transactions = await this.prisma.transaction.findMany({
      where: { OR: [{ payerWalletId: wallet.id }, { payeeWalletId: wallet.id }] },
      orderBy: { createdAt: 'desc' },
      take: 50,
    });
    return transactions.map((transaction) => ({
      transaction_id: transaction.id,
      transaction_type: transaction.transactionType,
      status: transaction.status,
      source_app: transaction.sourceApp,
      gross_amount: transaction.grossAmount,
      total_debit: transaction.totalDebit,
      fee_total: transaction.feeTotal,
      tax_total: transaction.taxTotal,
      created_at: transaction.createdAt,
      settled_at: transaction.settledAt,
      direction: transaction.payerWalletId === wallet.id ? 'OUT' : 'IN',
    }));
  }

  /**
   * C3: User mengajukan upgrade peran bisnis. HANYA mencatat pending_role —
   * TIDAK memberikan role baru atau mengubah kycTier. Grant final wajib via
   * TellerService.approveKyc (yang sudah memvalidasi pendingRole). Menghapus
   * vektor self-escalation: user tidak bisa lagi jadi MERCHANT/VERIFIED sendiri.
   */
  async requestUpgrade(input: {
    userId: string;
    requestedRole: string;
    businessName?: string;
    nik?: string;
    requestId: string;
  }) {
    const role = (input.requestedRole || '').toUpperCase();
    if (!UPGRADEABLE_ROLES.includes(role)) {
      throw new AppError(
        ErrorCode.VALIDATION_ERROR,
        `Peran yang diajukan tidak valid. Pilihan: ${UPGRADEABLE_ROLES.join(', ')}`,
      );
    }
    return this.prisma.$transaction(async (tx) => {
      const user = await tx.user.findUniqueOrThrow({ where: { id: input.userId } });
      if (user.role !== 'WALLET_USER') {
        throw new AppError(ErrorCode.VALIDATION_ERROR, 'Hanya WALLET_USER yang dapat mengajukan upgrade peran');
      }
      // Idempoten: kalau sudah mengajukan role yang sama, kembalikan status pending.
      if (user.pendingRole === role) {
        return { status: 'PENDING', pending_role: role, requested_at: user.pendingRoleRequestedAt };
      }
      const now = new Date();
      await tx.user.update({
        where: { id: input.userId },
        data: { pendingRole: role, pendingRoleRequestedAt: now },
      });
      await this.audit.record({
        tx,
        actorUserId: input.userId,
        serviceName: 'centralbank-core',
        action: 'UPGRADE_REQUESTED',
        targetType: 'user',
        targetId: input.userId,
        requestId: input.requestId,
        metadata: {
          requested_role: role,
          business_name: input.businessName ?? null,
          nik: input.nik ?? null,
        },
      });
      return { status: 'PENDING', pending_role: role, requested_at: now };
    });
  }
}

function normalizeIndonesianPhone(phone: string): string | null {
  const digits = phone.replace(/\D/g, '');
  if (digits.startsWith('62') && digits.length >= 10) return `+${digits}`;
  if (digits.startsWith('0') && digits.length >= 10) return `+62${digits.slice(1)}`;
  return null;
}
