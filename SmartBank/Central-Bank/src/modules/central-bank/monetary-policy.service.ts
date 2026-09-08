import { Injectable } from '@nestjs/common';
import { TransactionType } from '@prisma/client';
import { randomUUID } from 'crypto';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';
import { PrismaService } from '../prisma/prisma.service';

@Injectable()
export class MonetaryPolicyService {
  constructor(private readonly prisma: PrismaService) {}

  async supply() {
    const totalSupply = BigInt(process.env.TOTAL_MONEY_SUPPLY ?? '1000000000');
    const reserve = await this.prisma.walletAccount.findUniqueOrThrow({ where: { accountCode: 'CENTRAL_RESERVE' } });
    const sink = await this.prisma.walletAccount.findUniqueOrThrow({ where: { accountCode: 'BURN_OR_SINK_ACCOUNT' } });
    const circulating = await this.prisma.walletAccount.aggregate({
      where: {
        accountCode: { notIn: ['CENTRAL_RESERVE', 'BURN_OR_SINK_ACCOUNT'] },
        status: { not: 'CLOSED' },
      },
      _sum: { availableBalance: true },
    });
    const circulatingSupply = circulating._sum.availableBalance ?? 0n;
    const invariantTotal = reserve.availableBalance + circulatingSupply + sink.availableBalance;
    const valid = invariantTotal === totalSupply && totalSupply <= 1000000000n;
    return {
      total_supply: totalSupply,
      reserve_balance: reserve.availableBalance,
      circulating_supply: circulatingSupply,
      sink_or_burn_accounting: sink.availableBalance,
      invariant_total: invariantTotal,
      invariant_valid: valid,
    };
  }

  async assertSupplyInvariant() {
    const report = await this.supply();
    if (!report.invariant_valid) {
      throw new AppError(ErrorCode.SUPPLY_INVARIANT_VIOLATION, 'Supply invariant tidak valid', report);
    }
    return report;
  }

  async ledger(filters: { accountId?: string; transactionId?: string; from?: string; to?: string }) {
    return this.prisma.ledgerEntry.findMany({
      where: {
        accountId: filters.accountId,
        transactionId: filters.transactionId,
        createdAt:
          filters.from || filters.to
            ? {
                gte: filters.from ? new Date(filters.from) : undefined,
                lte: filters.to ? new Date(filters.to) : undefined,
              }
            : undefined,
      },
      orderBy: [{ createdAt: 'desc' }, { entryNo: 'asc' }],
      take: 200,
    });
  }

  async listWallets(filters: { accountType?: string; search?: string }) {
    const where: Record<string, unknown> = {
      status: { not: 'CLOSED' },
    };
    if (filters.accountType) {
      where.accountType = filters.accountType;
    } else {
      where.accountType = { in: ['USER_WALLET', 'MERCHANT_WALLET'] };
    }
    if (filters.search) {
      const term = filters.search.trim();
      where.OR = [
        { id: { contains: term } },
        { accountCode: { contains: term } },
        { user: { is: { email: { contains: term } } } },
        { user: { is: { name: { contains: term } } } },
      ];
    }
    const wallets = await this.prisma.walletAccount.findMany({
      where,
      include: { user: { select: { id: true, name: true, email: true, role: true } } },
      orderBy: { createdAt: 'desc' },
      take: 200,
    });
    return wallets.map((wallet) => ({
      id: wallet.id,
      account_type: wallet.accountType,
      account_code: wallet.accountCode,
      available_balance: wallet.availableBalance.toString(),
      status: wallet.status,
      owner: wallet.user
        ? { id: wallet.user.id, name: wallet.user.name, email: wallet.user.email, role: wallet.user.role }
        : null,
      created_at: wallet.createdAt,
    }));
  }

  async listTransactions(filters: { limit?: number }) {
    const transactions = await this.prisma.transaction.findMany({
      orderBy: { createdAt: 'desc' },
      take: Math.min(filters.limit ?? 100, 200),
      select: {
        id: true,
        transactionType: true,
        status: true,
        grossAmount: true,
        payerWalletId: true,
        payeeWalletId: true,
        createdAt: true,
        settledAt: true,
      },
    });
    return transactions.map((tx) => ({
      id: tx.id,
      transaction_type: tx.transactionType,
      status: tx.status,
      gross_amount: tx.grossAmount.toString(),
      payer_wallet_id: tx.payerWalletId,
      payee_wallet_id: tx.payeeWalletId,
      created_at: tx.createdAt,
      settled_at: tx.settledAt,
    }));
  }

  async getFeeConfigurations() {
    return this.prisma.feeConfiguration.findMany({ orderBy: { type: 'asc' } });
  }

  async getFeeConfiguration(type: string) {
    const parsed = this.parseTransactionType(type);
    const config = await this.prisma.feeConfiguration.findUnique({ where: { type: parsed } });
    if (!config) throw new AppError(ErrorCode.FEE_CONFIGURATION_NOT_FOUND, `Fee config untuk ${type} tidak ditemukan`);
    return config;
  }

  async upsertFeeConfiguration(input: {
    type: string;
    mode: string;
    value: string;
    minFee?: string;
    maxFee?: string;
    isActive?: boolean;
    updatedBy?: string;
  }) {
    const parsedType = this.parseTransactionType(input.type);
    const parsedMode = this.parseFeeMode(input.mode);
    const value = BigInt(input.value);
    if (value < 0n) throw new AppError(ErrorCode.FEE_VALUE_INVALID, 'Fee value tidak boleh negatif');
    if (parsedMode === 'PERCENT' && value > 10000n) throw new AppError(ErrorCode.FEE_VALUE_INVALID, 'Fee PERCENT maksimal 10000 bps (100%)');

    const minFee = input.minFee ? BigInt(input.minFee) : null;
    const maxFee = input.maxFee ? BigInt(input.maxFee) : null;
    const isActive = input.isActive ?? true;

    return this.prisma.feeConfiguration.upsert({
      where: { type: parsedType },
      update: { mode: parsedMode, value, minFee, maxFee, isActive, updatedBy: input.updatedBy },
      create: { id: randomUUID(), type: parsedType, mode: parsedMode, value, minFee, maxFee, isActive, updatedBy: input.updatedBy },
    });
  }

  private parseTransactionType(raw: string): TransactionType {
    const allowed: TransactionType[] = ['TOP_UP', 'WITHDRAWAL', 'TRANSFER', 'PAYMENT'];
    if (!allowed.includes(raw as TransactionType)) {
      throw new AppError(ErrorCode.FEE_VALUE_INVALID, `Transaction type harus salah satu dari: ${allowed.join(', ')}`);
    }
    return raw as TransactionType;
  }

  private parseFeeMode(raw: string): 'FLAT' | 'PERCENT' {
    if (raw !== 'FLAT' && raw !== 'PERCENT') {
      throw new AppError(ErrorCode.FEE_VALUE_INVALID, 'Mode harus FLAT atau PERCENT');
    }
    return raw;
  }

  async listAuditLogs(filters: { search?: string; serviceName?: string; limit?: number }) {
    const where: Record<string, unknown> = {};
    if (filters.serviceName) {
      where.serviceName = filters.serviceName;
    }
    if (filters.search) {
      const term = filters.search.trim();
      where.OR = [
        { actorUserId: { contains: term } },
        { action: { contains: term } },
        { targetType: { contains: term } },
        { targetId: { contains: term } },
        { requestId: { contains: term } },
        { reasonCode: { contains: term } },
      ];
    }
    const [logs, total] = await Promise.all([
      this.prisma.auditLog.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        take: Math.min(filters.limit ?? 100, 200),
      }),
      this.prisma.auditLog.count({ where }),
    ]);
    return { logs, total };
  }
}
