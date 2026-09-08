import { Injectable } from '@nestjs/common';
import { PrismaService } from '../prisma/prisma.service';

export interface PublicStats {
  totalMoneySupply: string;
  circulatingSupply: string;
  reserveBalance: string;
  loanPoolBalance: string;
  totalUsers: number;
  totalTransactions: number;
  totalVolumeSettled: string;
  settledLast24h: number;
  generatedAt: string;
}

@Injectable()
export class PublicStatsService {
  constructor(private readonly prisma: PrismaService) {}

  async getStats(): Promise<PublicStats> {
    const since = new Date(Date.now() - 24 * 60 * 60 * 1000);

    const [
      allAccounts,
      usersCount,
      txCount,
      txVolume,
      txLast24h,
    ] = await Promise.all([
      this.prisma.walletAccount.findMany({
        select: { accountType: true, accountCode: true, availableBalance: true },
      }),
      this.prisma.user.count({ where: { status: 'ACTIVE' } }),
      this.prisma.transaction.count({ where: { status: 'SETTLED' } }),
      this.prisma.transaction.aggregate({
        _sum: { grossAmount: true },
        where: { status: 'SETTLED' },
      }),
      this.prisma.transaction.count({
        where: { status: 'SETTLED', settledAt: { gte: since } },
      }),
    ]);

    const reserveAccount = allAccounts.find(
      (a) => a.accountType === 'CENTRAL_RESERVE' || a.accountCode === 'RESERVE_ACCOUNT',
    );
    const loanPoolAccount = allAccounts.find(
      (a) => a.accountType === 'LOAN_POOL_ACCOUNT' || a.accountCode === 'LOAN_POOL_ACCOUNT',
    );

    const circulatingTypes = new Set([
      'USER_WALLET',
      'MERCHANT_WALLET',
      'FEE_BANK',
      'FEE_GATEWAY',
      'FEE_MARKETPLACE',
      'FEE_POS',
      'FEE_SUPPLIER',
      'FEE_LOGISTICS',
      'TAX_SINK',
      'LOAN_POOL_ACCOUNT',
    ]);

    let totalSupply = 0n;
    let circulating = 0n;
    for (const acc of allAccounts) {
      totalSupply += acc.availableBalance;
      if (circulatingTypes.has(acc.accountType)) {
        circulating += acc.availableBalance;
      }
    }

    return {
      totalMoneySupply: totalSupply.toString(),
      circulatingSupply: circulating.toString(),
      reserveBalance: (reserveAccount?.availableBalance ?? 0n).toString(),
      loanPoolBalance: (loanPoolAccount?.availableBalance ?? 0n).toString(),
      totalUsers: usersCount,
      totalTransactions: txCount,
      totalVolumeSettled: (txVolume._sum.grossAmount ?? 0n).toString(),
      settledLast24h: txLast24h,
      generatedAt: new Date().toISOString(),
    };
  }
}