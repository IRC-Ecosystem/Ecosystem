import { Injectable } from '@nestjs/common';
import { Prisma, FeeType } from '@prisma/client';
import { PrismaService } from '../prisma/prisma.service';
import { MoneyService } from '../money/money.service';

export type FeeComponent = {
  type: FeeType;
  amount: bigint;
  destinationAccountId: string;
};

export type FeeQuote = {
  grossAmount: bigint;
  feeTotal: bigint;
  taxTotal: bigint;
  totalDebit: bigint;
  components: FeeComponent[];
  breakdown: Record<string, bigint>;
};

const SOURCE_FEE_BY_APP: Record<string, FeeType | undefined> = {
  MARKETPLACE: 'MARKETPLACE',
  POS: 'POS',
  SUPPLIER: 'SUPPLIER',
  LOGISTICS: 'LOGISTICS',
};

@Injectable()
export class FeeQuoteService {
  constructor(
    private readonly prisma: PrismaService,
    private readonly money: MoneyService,
  ) {}

  async quote(input: { sourceApp: string; amount: bigint; tx?: Prisma.TransactionClient }): Promise<FeeQuote> {
    const client = input.tx ?? this.prisma;
    this.money.assertPositive(input.amount);
    const rules = await client.feeRule.findMany({
      where: {
        active: true,
        OR: [
          { sourceApp: 'GLOBAL', feeType: { in: ['BANK', 'GATEWAY', 'TAX'] } },
          { sourceApp: input.sourceApp },
        ],
      },
    });
    const sourceFeeType = SOURCE_FEE_BY_APP[input.sourceApp];
    const selected = rules.filter((rule) => {
      if (['BANK', 'GATEWAY', 'TAX'].includes(rule.feeType)) return true;
      return sourceFeeType === rule.feeType;
    });
    const components = selected
      .map((rule) => ({
        type: rule.feeType,
        amount: rule.flatAmount ?? this.money.bps(input.amount, rule.bps ?? 0),
        destinationAccountId: rule.destinationAccountId,
      }))
      .filter((component) => component.amount > 0n);
    const taxTotal = components
      .filter((component) => component.type === 'TAX')
      .reduce((sum, component) => sum + component.amount, 0n);
    const feeTotal = components
      .filter((component) => component.type !== 'TAX')
      .reduce((sum, component) => sum + component.amount, 0n);
      
    const breakdown = {
      bank: components.find(c => c.type === 'BANK')?.amount ?? 0n,
      gateway: components.find(c => c.type === 'GATEWAY')?.amount ?? 0n,
      marketplace: components.find(c => c.type === 'MARKETPLACE')?.amount ?? 0n,
      pos: components.find(c => c.type === 'POS')?.amount ?? 0n,
      supplier: components.find(c => c.type === 'SUPPLIER')?.amount ?? 0n,
      logistics: components.find(c => c.type === 'LOGISTICS')?.amount ?? 0n,
      tax: taxTotal,
      total: feeTotal + taxTotal,
    };

    return {
      grossAmount: input.amount,
      feeTotal,
      taxTotal,
      totalDebit: input.amount + feeTotal + taxTotal,
      components,
      breakdown,
    };
  }
}
