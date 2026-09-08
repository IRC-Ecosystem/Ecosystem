import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { SettlementService } from '../settlement/settlement.service';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';
import { WalletAccountService } from '../wallets/wallet-account.service';
import { AccountStatus, UserStatus } from '@prisma/client';
import { AuditLogService } from '../audit/audit-log.service';

@Injectable()
export class ManagerService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly settlement: SettlementService,
    private readonly wallets: WalletAccountService,
    private readonly audit: AuditLogService,
  ) {}

  async suspendUser(input: {
    userId: string;
    actorUserId: string;
    requestId: string;
    reasonCode?: string;
  }) {
    const wallet = await this.wallets.getPrimaryWallet(input.userId);
    return this.prisma.$transaction(async (tx) => {
      await tx.user.update({
        where: { id: input.userId },
        data: { status: UserStatus.SUSPENDED },
      });
      const updated = await tx.walletAccount.update({
        where: { id: wallet.id },
        data: { status: AccountStatus.FROZEN },
      });
      await this.audit.record({
        tx,
        actorUserId: input.actorUserId,
        serviceName: 'centralbank-core',
        action: 'USER_WALLET_SUSPENDED',
        targetType: 'wallet',
        targetId: wallet.id,
        requestId: input.requestId,
        reasonCode: input.reasonCode ?? 'SUSPICIOUS_ACTIVITY',
        metadata: { user_id: input.userId },
      });
      return updated;
    });
  }

  async activateUser(input: {
    userId: string;
    actorUserId: string;
    requestId: string;
    reasonCode?: string;
  }) {
    const wallet = await this.wallets.getPrimaryWallet(input.userId);
    return this.prisma.$transaction(async (tx) => {
      await tx.user.update({
        where: { id: input.userId },
        data: { status: UserStatus.ACTIVE },
      });
      const updated = await tx.walletAccount.update({
        where: { id: wallet.id },
        data: { status: AccountStatus.ACTIVE },
      });
      await this.audit.record({
        tx,
        actorUserId: input.actorUserId,
        serviceName: 'centralbank-core',
        action: 'USER_WALLET_ACTIVATED',
        targetType: 'wallet',
        targetId: wallet.id,
        requestId: input.requestId,
        reasonCode: input.reasonCode ?? 'ACCOUNT_REACTIVATED',
        metadata: { user_id: input.userId },
      });
      return updated;
    });
  }

  async approveLoan(input: {
    loanId: string;
    actorUserId: string;
    requestId: string;
    idempotencyKey: string;
    reasonCode?: string;
  }) {
    return this.settlement.settleLoanApproval({
      loanId: input.loanId,
      actorUserId: input.actorUserId,
      requestId: input.requestId,
      reasonCode: input.reasonCode,
      idempotency: {
        key: input.idempotencyKey,
        route: 'POST /api/v1/manager/loans/approve',
        actorId: input.actorUserId,
        requestHash: input.loanId,
      },
    });
  }

  async listPendingLoans(filters?: { minAmount?: bigint; recommendedOnly?: boolean }) {
    const where: any = { status: 'PENDING' };
    if (filters?.minAmount !== undefined) {
      where.principal = { gte: filters.minAmount };
    }
    if (filters?.recommendedOnly === true) {
      where.recommendedBy = { not: null };
    } else if (filters?.recommendedOnly === false) {
      where.recommendedBy = null;
    }
    // undefined = no filter, tampilkan semua

    const loans = await this.prisma.loan.findMany({
      where,
      orderBy: { createdAt: 'asc' },
      include: {
        borrowerWallet: {
          include: {
            user: {
              select: {
                id: true,
                name: true,
                email: true,
                phone: true,
                kycTier: true,
                status: true,
                accountNumber: true,
                identityDocumentType: true,
                identityDocumentNumber: true,
                identityDocumentName: true,
                identityDocumentUploadedAt: true,
              },
            },
          },
        },
      },
    });

    return loans
      .map((loan) => {
        const user = loan.borrowerWallet.user;
        if (!user) return null;
        return {
          id: loan.id,
          borrower_wallet_id: loan.borrowerWalletId,
          principal: loan.principal.toString(),
          interest_amount: loan.interestAmount.toString(),
          total_due: loan.totalDue.toString(),
          paid_amount: loan.paidAmount.toString(),
          status: loan.status,
          created_at: loan.createdAt,
          recommended_by: loan.recommendedBy,
          recommended_at: loan.recommendedAt,
          recommendation_note: loan.recommendationNote,
          borrower: {
            id: user.id,
            name: user.name,
            email: user.email,
            phone: user.phone,
            kyc_tier: user.kycTier,
            status: user.status,
            account_number: user.accountNumber,
            identity_document_type: user.identityDocumentType,
            identity_document_number: user.identityDocumentNumber,
            identity_document_name: user.identityDocumentName,
          },
          wallet: {
            id: loan.borrowerWallet.id,
            available_balance: loan.borrowerWallet.availableBalance.toString(),
            status: loan.borrowerWallet.status,
          },
        };
      })
      .filter((x): x is NonNullable<typeof x> => x !== null);
  }

  /**
   * Saldo LOAN_POOL_ACCOUNT — dana yang tersedia untuk dicairkan ke nasabah.
   * Manager wajib lihat ini sebelum approve, karena kalau pool kosong loan
   * tidak bisa disburse (settleLoanApproval akan tolak dengan INSUFFICIENT_BALANCE).
   */
  async getLoanPoolBalance() {
    const pool = await this.prisma.walletAccount.findUnique({
      where: { accountCode: 'LOAN_POOL_ACCOUNT' },
      select: {
        id: true,
        accountCode: true,
        accountType: true,
        currency: true,
        availableBalance: true,
        holdBalance: true,
        status: true,
      },
    });
    if (!pool) {
      throw new AppError(
        ErrorCode.VALIDATION_ERROR,
        'LOAN_POOL_ACCOUNT belum di-seed di database. Jalankan prisma db seed.',
      );
    }
    return {
      wallet_id: pool.id,
      account_code: pool.accountCode,
      account_type: pool.accountType,
      currency: pool.currency,
      available_balance: pool.availableBalance.toString(),
      hold_balance: pool.holdBalance.toString(),
      status: pool.status,
    };
  }

  async rejectLoan(input: {
    loanId: string;
    actorUserId: string;
    requestId: string;
    reasonCode?: string;
    idempotencyKey: string;
  }) {
    return this.settlement.runRejectLoan(input);
  }
}
