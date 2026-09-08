import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { randomUUID } from 'crypto';
import { MoneyService } from '../money/money.service';
import { IdempotencyService } from '../idempotency/idempotency.service';
import { Prisma } from '@prisma/client';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';

function asJson(value: unknown): Prisma.InputJsonValue {
  return JSON.parse(JSON.stringify(value, (_key, current) => (typeof current === 'bigint' ? current.toString() : current)));
}

@Injectable()
export class LoanService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly money: MoneyService,
    private readonly idempotency: IdempotencyService,
  ) {}

  getLoan(id: string) {
    return this.prisma.loan.findUniqueOrThrow({ where: { id } });
  }

  /**
   * List active loans (PENDING, DISBURSED, PARTIAL_PAID) untuk wallet tertentu.
   * Digunakan oleh endpoint /loans/me agar Nasabah tidak perlu input manual loanId.
   * Return shape bigint → string (JSON-safe) supaya frontend tidak perlu BigInt polyfill.
   */
  async listLoansForWallet(walletId: string) {
    const loans = await this.prisma.loan.findMany({
      where: {
        borrowerWalletId: walletId,
        status: { in: ['PENDING', 'DISBURSED', 'PARTIAL_PAID'] },
      },
      orderBy: { createdAt: 'desc' },
    });

    return loans.map((l) => ({
      id: l.id,
      principal: l.principal.toString(),
      interest_amount: l.interestAmount.toString(),
      total_due: l.totalDue.toString(),
      paid_amount: l.paidAmount.toString(),
      remaining: (l.totalDue - l.paidAmount).toString(),
      status: l.status,
      created_at: l.createdAt,
      disbursed_at: l.disbursedAt,
      due_at: l.dueAt,
      recommended_by: l.recommendedBy,
      recommended_at: l.recommendedAt,
      recommendation_note: l.recommendationNote,
    }));
  }

  /**
   * Hitung limit pinjam: outstanding (PENDING+DISBURSED+PARTIAL_PAID) vs cap 100.000.
   * Cap di-declare sebagai konstanta agar konsisten antara apply & UI.
   */
  static readonly LOAN_CAP = 100_000n;

  async getLoanLimitInfo(walletId: string) {
    const outstanding = await this.prisma.loan.aggregate({
      where: {
        borrowerWalletId: walletId,
        status: { in: ['PENDING', 'DISBURSED', 'PARTIAL_PAID'] },
      },
      _sum: { principal: true, paidAmount: true },
    });

    const used = (outstanding._sum.principal ?? 0n) - (outstanding._sum.paidAmount ?? 0n);
    const cap = LoanService.LOAN_CAP;

    return {
      cap: cap.toString(),
      outstanding: used.toString(),
      remaining: (cap - used).toString(),
    };
  }

  async applyLoan(input: {
    borrowerWalletId: string;
    amount: bigint;
    idempotency: { key: string; route: string; actorId: string; requestHash: string };
  }) {
    this.money.assertPositive(input.amount);
    if (input.amount > 100000n) throw new AppError(ErrorCode.LOAN_LIMIT_EXCEEDED, 'Limit pinjaman maksimal 100000');

    return this.prisma.$transaction(async (tx) => {
      const idem = await this.idempotency.start(tx, input.idempotency);
      if (idem.replay) return idem.response;

      const wallet = await tx.walletAccount.findUnique({
        where: { id: input.borrowerWalletId },
        include: { user: true },
      });
      if (!wallet?.user) throw new AppError(ErrorCode.VALIDATION_ERROR, 'Wallet peminjam tidak valid');
      if (wallet.user.kycTier !== 'VERIFIED') {
        throw new AppError(
          ErrorCode.FORBIDDEN,
          'Pengajuan pinjaman hanya tersedia untuk nasabah yang sudah terverifikasi KYC.',
        );
      }

      const outstanding = await tx.loan.aggregate({
        where: { borrowerWalletId: input.borrowerWalletId, status: { in: ['PENDING', 'DISBURSED', 'PARTIAL_PAID'] } },
        _sum: { principal: true, paidAmount: true },
      });

      const currentOutstanding = (outstanding._sum.principal ?? 0n) - (outstanding._sum.paidAmount ?? 0n);
      if (currentOutstanding + input.amount > 100000n) {
        throw new AppError(ErrorCode.LOAN_LIMIT_EXCEEDED, 'Outstanding loan melebihi limit');
      }

      const interest = this.money.tenPercent(input.amount);
      const totalDue = input.amount + interest;
      const loanId = randomUUID();

      await tx.loan.create({
        data: {
          id: loanId,
          borrowerWalletId: input.borrowerWalletId,
          principal: input.amount,
          interestAmount: interest,
          totalDue,
          paidAmount: 0n,
          status: 'PENDING',
        },
      });

      const response = {
        loan_id: loanId,
        principal: input.amount,
        interest_amount: interest,
        total_due: totalDue,
        status: 'PENDING',
      };
      await this.idempotency.complete(tx, { ...input.idempotency, responseBody: asJson(response) });
      return response;
    });
  }
}
