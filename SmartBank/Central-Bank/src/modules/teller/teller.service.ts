import { Injectable, ConflictException } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';
import { SettlementService } from '../settlement/settlement.service';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';
import { WalletAccountService } from '../wallets/wallet-account.service';
import { MoneyService } from '../money/money.service';
import { AuditLogService } from '../audit/audit-log.service';

@Injectable()
export class TellerService {
  /**
   * Batas nominal "pinjaman kecil" yang menjadi domain Teller untuk screening.
   * Loan di atas threshold ini langsung masuk antrean Manager tanpa Teller review.
   * Konstanta dideklarasikan di sini agar konsisten dengan query param default controller.
   */
  static readonly SMALL_LOAN_THRESHOLD = 50_000n;

  constructor(
    private readonly prisma: PrismaService,
    private readonly settlement: SettlementService,
    private readonly wallets: WalletAccountService,
    private readonly money: MoneyService,
    private readonly audit: AuditLogService,
  ) {}

  async verifyKyc(input: {
    userId: string;
    actorUserId: string;
    requestId: string;
    reasonCode?: string;
  }) {
    const user = await this.prisma.user.findUnique({ where: { id: input.userId } });
    if (!user) throw new AppError(ErrorCode.VALIDATION_ERROR, 'User tidak ditemukan');
    if (!user.identityDocumentDataUrl || !user.identityDocumentNumber || !user.identityDocumentType) {
      throw new AppError(
        ErrorCode.VALIDATION_ERROR,
        'Dokumen identitas nasabah belum diunggah. Teller wajib memeriksa dokumen sebelum verifikasi KYC.',
      );
    }

    return this.prisma.$transaction(async (tx) => {
      const updated = await tx.user.update({
        where: { id: input.userId },
        data: { kycTier: 'VERIFIED' },
      });
      await this.audit.record({
        tx,
        actorUserId: input.actorUserId,
        serviceName: 'centralbank-core',
        action: 'KYC_VERIFIED',
        targetType: 'user',
        targetId: input.userId,
        requestId: input.requestId,
        reasonCode: input.reasonCode ?? 'KYC_VERIFIED',
        metadata: {
          document_type: user.identityDocumentType,
          document_number: user.identityDocumentNumber,
          document_name: user.identityDocumentName,
        },
      });
      return updated;
    });
  }

  async findCustomer(query: string) {
    if (!query || query.length > 191) {
      throw new AppError(ErrorCode.VALIDATION_ERROR, 'Query pencarian tidak boleh kosong');
    }

    // Allow partial email match (contains) OR exact id/phone match
    const user = await this.prisma.user.findFirst({
      where: {
        OR: [
          { id: query },
          { email: { contains: query } },
          { phone: query }
        ]
      },
      include: {
        wallets: {
          where: {
            accountType: 'USER_WALLET'
          }
        }
      }
    });
    if (!user) {
      throw new AppError(ErrorCode.VALIDATION_ERROR, 'Nasabah tidak ditemukan');
    }

    // Sanitize: strip sensitive fields before returning
    const { passwordHash: _pw, pinHash: _pin, ...safeUser } = user as any;
    return {
      ...safeUser,
      wallets: user.wallets.map((w) => ({
        id: w.id,
        accountType: w.accountType,
        currency: w.currency,
        availableBalance: w.availableBalance.toString(),
        holdBalance: w.holdBalance.toString(),
        status: w.status,
        createdAt: w.createdAt,
        updatedAt: w.updatedAt,
      })),
    };
  }

  async topUp(input: {
    userId: string;
    amount: bigint;
    actorUserId: string;
    requestId: string;
    idempotencyKey: string;
    reasonCode?: string;
  }) {
    this.money.assertPositive(input.amount);
    const wallet = await this.wallets.getPrimaryWallet(input.userId);
    
    return this.settlement.settleTopUp({
      walletId: wallet.id,
      amount: input.amount,
      actorUserId: input.actorUserId,
      requestId: input.requestId,
      reasonCode: input.reasonCode,
      idempotency: {
        key: input.idempotencyKey,
        route: 'POST /api/v1/teller/top-up',
        actorId: input.actorUserId,
        requestHash: `${input.userId}-${input.amount.toString()}`,
      },
    });
  }

  async withdraw(input: {
    userId: string;
    amount: bigint;
    actorUserId: string;
    requestId: string;
    idempotencyKey: string;
    reasonCode?: string;
  }) {
    this.money.assertPositive(input.amount);
    const wallet = await this.wallets.getPrimaryWallet(input.userId);

    return this.settlement.settleWithdrawal({
      walletId: wallet.id,
      amount: input.amount,
      actorUserId: input.actorUserId,
      requestId: input.requestId,
      reasonCode: input.reasonCode,
      idempotency: {
        key: input.idempotencyKey,
        route: 'POST /api/v1/teller/withdraw',
        actorId: input.actorUserId,
        requestHash: `${input.userId}-${input.amount.toString()}`,
      },
    });
  }

  async approveKyc(input: {
    userId: string;
    approvedRole: string;
    actorUserId: string;
    requestId: string;
  }) {
    return this.prisma.$transaction(async (tx) => {
      const user = await tx.user.findUniqueOrThrow({ where: { id: input.userId } });
      if (user.role !== 'WALLET_USER') {
        throw new AppError(ErrorCode.VALIDATION_ERROR, 'User sudah memiliki peran final, tidak bisa di-upgrade ulang');
      }
      if (user.kycTier !== 'VERIFIED') {
        throw new AppError(ErrorCode.VALIDATION_ERROR, 'User belum melewati verifikasi KYC dasar');
      }
      if (!user.pendingRole || user.pendingRole !== input.approvedRole) {
        throw new AppError(ErrorCode.VALIDATION_ERROR, 'pending_role tidak cocok dengan role yang disetujui');
      }
      const updated = await tx.user.update({
        where: { id: input.userId },
        data: {
          role: input.approvedRole as any, // validated above against enum subset
          kycTier: 'VERIFIED',
          pendingRole: null,
          pendingRoleRequestedAt: null,
        },
      });
      await this.audit.record({
        tx,
        actorUserId: input.actorUserId,
        serviceName: 'centralbank-core',
        action: 'KYC_ROLE_APPROVED',
        targetType: 'user',
        targetId: input.userId,
        requestId: input.requestId,
        metadata: { approved_role: input.approvedRole },
      });
      return updated;
    });
  }

  async rejectKyc(input: {
    userId: string;
    reasonCode: string;
    actorUserId: string;
    requestId: string;
  }) {
    return this.prisma.$transaction(async (tx) => {
      const user = await tx.user.findUniqueOrThrow({ where: { id: input.userId } });
      if (!user.pendingRole) {
        throw new AppError(ErrorCode.VALIDATION_ERROR, 'Tidak ada pengajuan upgrade yang aktif');
      }
      const updated = await tx.user.update({
        where: { id: input.userId },
        data: { pendingRole: null, pendingRoleRequestedAt: null },
      });
      await this.audit.record({
        tx,
        actorUserId: input.actorUserId,
        serviceName: 'centralbank-core',
        action: 'KYC_ROLE_REJECTED',
        targetType: 'user',
        targetId: input.userId,
        requestId: input.requestId,
        reasonCode: input.reasonCode,
        metadata: { rejected_role: user.pendingRole },
      });
      return updated;
    });
  }

  /**
   * List pinjaman PENDING kecil (≤ threshold) yang BELUM di-rekomendasikan Teller lain.
   * Teller akan screening → tandai recommendedBy → Manager final-approve.
   * Loan > threshold TIDAK muncul di sini (langsung antrean Manager).
   * Loan yang sudah recommendedBy != null TIDAK muncul (sudah discreen, tinggal Manager).
   */
  async listSmallPendingLoans(maxAmount: bigint = TellerService.SMALL_LOAN_THRESHOLD) {
    const loans = await this.prisma.loan.findMany({
      where: {
        status: 'PENDING',
        principal: { lte: maxAmount },
        recommendedBy: null,
      },
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
              },
            },
          },
        },
      },
    });

    return loans.map((l) => {
      const user = l.borrowerWallet.user;
      if (!user) {
        // Defensive: orphan wallet (no user) shouldn't reach UI. Skip.
        return null;
      }
      return {
        id: l.id,
        principal: l.principal.toString(),
        interest_amount: l.interestAmount.toString(),
        total_due: l.totalDue.toString(),
        status: l.status,
        created_at: l.createdAt,
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
          id: l.borrowerWallet.id,
          currency: l.borrowerWallet.currency,
          available_balance: l.borrowerWallet.availableBalance.toString(),
        },
      };
    }).filter((x): x is NonNullable<typeof x> => x !== null);
  }

  /**
   * Teller screening: tandai loan sebagai "sudah direkomendasikan".
   * TIDAK langsung disburse. Manager final-approve via endpoint existing.
   * Conflict (409) jika loan sudah di-rekomendasikan Teller lain.
   */
  async recommendLoan(input: {
    loanId: string;
    tellerId: string;
    requestId: string;
    note?: string;
  }) {
    return this.prisma.$transaction(async (tx) => {
      const loan = await tx.loan.findUnique({ where: { id: input.loanId } });
      if (!loan) throw new AppError(ErrorCode.VALIDATION_ERROR, 'Pinjaman tidak ditemukan');
      if (loan.status !== 'PENDING') {
        throw new AppError(
          ErrorCode.VALIDATION_ERROR,
          `Pinjaman tidak dalam status PENDING (saat ini: ${loan.status})`,
        );
      }
      if (loan.recommendedBy) {
        throw new ConflictException(
          'Pinjaman sudah direkomendasikan oleh Teller lain, menunggu Manager',
        );
      }

      const updated = await tx.loan.update({
        where: { id: input.loanId },
        data: {
          recommendedBy: input.tellerId,
          recommendedAt: new Date(),
          recommendationNote: input.note ?? null,
        },
      });

      await this.audit.record({
        tx,
        actorUserId: input.tellerId,
        serviceName: 'centralbank-core',
        action: 'LOAN_RECOMMENDED_BY_TELLER',
        targetType: 'loan',
        targetId: input.loanId,
        requestId: input.requestId,
        reasonCode: 'TELLER_RECOMMENDATION',
        metadata: {
          teller_id: input.tellerId,
          note: input.note ?? null,
          principal: loan.principal.toString(),
          borrower_wallet_id: loan.borrowerWalletId,
        },
      });

      return {
        id: updated.id,
        principal: updated.principal.toString(),
        status: updated.status,
        recommended_by: updated.recommendedBy,
        recommended_at: updated.recommendedAt,
        recommendation_note: updated.recommendationNote,
      };
    });
  }
}
