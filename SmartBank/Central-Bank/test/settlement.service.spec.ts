import { SettlementService } from '../src/modules/settlement/settlement.service';
import { AppError } from '../src/common/app-error';
import { ErrorCode } from '../src/common/error-codes';

describe('SettlementService primitives', () => {
  const service = new SettlementService({} as never, {} as never, {} as never, {} as never, {} as never, {} as never);
  const wallet = {
    id: 'wallet',
    userId: 'user',
    accountCode: null,
    accountType: 'USER_WALLET' as const,
    currency: 'CBDC_IDR',
    availableBalance: 5000n,
    holdBalance: 0n,
    status: 'ACTIVE' as const,
    createdAt: new Date(),
    updatedAt: new Date(),
  };

  it('prevents debit when balance is insufficient', () => {
    expect(() => service.ensureDebitAllowed(wallet, 10000n)).toThrow(
      new AppError(ErrorCode.INSUFFICIENT_BALANCE, 'Saldo tidak mencukupi'),
    );
  });

  it('rejects object-level access when wallet belongs to another user', () => {
    expect(() => service.ensureWalletOwnedByActor(wallet, 'attacker')).toThrow(
      new AppError(ErrorCode.FORBIDDEN, 'Akses ke wallet ini tidak diizinkan'),
    );
  });

  it('allows object-level access for the wallet owner', () => {
    expect(() => service.ensureWalletOwnedByActor(wallet, 'user')).not.toThrow();
  });

  it('retries deadlocks only up to the configured limit', async () => {
    await expect(
      service.withDeadlockRetry(async () => {
        throw new Error('Deadlock found when trying to get lock');
      }),
    ).rejects.toMatchObject({ code: ErrorCode.DEADLOCK_RETRY_EXCEEDED });
  });
});

describe('SettlementService teller settlement classification', () => {
  function buildService() {
    const reserve = {
      id: 'reserve-wallet',
      userId: null,
      accountCode: 'CENTRAL_RESERVE',
      accountType: 'CENTRAL_RESERVE',
      currency: 'CBDC_IDR',
      availableBalance: 1_000_000n,
      holdBalance: 0n,
      status: 'ACTIVE',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    const wallet = {
      id: 'user-wallet',
      userId: 'user-1',
      accountCode: null,
      accountType: 'USER_WALLET',
      currency: 'CBDC_IDR',
      availableBalance: 100_000n,
      holdBalance: 0n,
      status: 'ACTIVE',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    const balances = new Map([
      [reserve.id, reserve.availableBalance],
      [wallet.id, wallet.availableBalance],
    ]);
    const tx = {
      $queryRaw: jest.fn(),
      user: {
        findUnique: jest.fn().mockResolvedValue({ id: 'user-1', kycTier: 'VERIFIED' }),
      },
      walletAccount: {
        findUniqueOrThrow: jest.fn(({ where }) => {
          if (where.accountCode === 'CENTRAL_RESERVE') return Promise.resolve(reserve);
          if (where.id === wallet.id) return Promise.resolve(wallet);
          throw new Error('Unexpected lookup');
        }),
        findMany: jest.fn().mockResolvedValue([reserve, wallet]),
        update: jest.fn(({ where, data }) => {
          const next = (balances.get(where.id) ?? 0n) + data.availableBalance.increment;
          balances.set(where.id, next);
          return Promise.resolve({ ...(where.id === reserve.id ? reserve : wallet), availableBalance: next });
        }),
      },
      transaction: {
        create: jest.fn(),
      },
    };
    const prisma = {
      $transaction: jest.fn((callback) => callback(tx)),
    };
    const ledger = {
      post: jest.fn(),
    };
    const idempotency = {
      start: jest.fn().mockResolvedValue({ replay: false }),
      complete: jest.fn(),
    };
    const audit = {
      record: jest.fn(),
    };

    return {
      service: new SettlementService(prisma as never, ledger as never, {} as never, idempotency as never, audit as never, {} as never),
      tx,
      audit,
    };
  }

  it('records teller top-up as TOP_UP, not initial distribution', async () => {
    const { service, tx, audit } = buildService();

    await service.settleTopUp({
      walletId: 'user-wallet',
      amount: 10_000n,
      actorUserId: 'teller-1',
      requestId: 'req-top-up',
      reasonCode: 'CASH_COUNTER_TOP_UP',
      idempotency: {
        key: 'idem-top-up',
        route: 'POST /api/v1/teller/top-up',
        actorId: 'teller-1',
        requestHash: 'hash',
      },
    });

    expect(tx.transaction.create).toHaveBeenCalledWith(
      expect.objectContaining({
        data: expect.objectContaining({ transactionType: 'TOP_UP' }),
      }),
    );
    expect(audit.record).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'TELLER_TOP_UP_SETTLED',
        reasonCode: 'CASH_COUNTER_TOP_UP',
      }),
    );
  });

  it('records teller withdrawal as WITHDRAWAL, not generic transfer', async () => {
    const { service, tx, audit } = buildService();

    await service.settleWithdrawal({
      walletId: 'user-wallet',
      amount: 10_000n,
      actorUserId: 'teller-1',
      requestId: 'req-withdraw',
      reasonCode: 'CASH_COUNTER_WITHDRAWAL',
      idempotency: {
        key: 'idem-withdraw',
        route: 'POST /api/v1/teller/withdraw',
        actorId: 'teller-1',
        requestHash: 'hash',
      },
    });

    expect(tx.transaction.create).toHaveBeenCalledWith(
      expect.objectContaining({
        data: expect.objectContaining({ transactionType: 'WITHDRAWAL' }),
      }),
    );
    expect(audit.record).toHaveBeenCalledWith(
      expect.objectContaining({
        action: 'TELLER_WITHDRAWAL_SETTLED',
        reasonCode: 'CASH_COUNTER_WITHDRAWAL',
      }),
    );
  });
});

describe('SettlementService manual issuance & burn', () => {
  function buildIssuanceService() {
    const reserve = {
      id: 'reserve-wallet',
      userId: null,
      accountCode: 'CENTRAL_RESERVE',
      accountType: 'CENTRAL_RESERVE',
      currency: 'CBDC_IDR',
      availableBalance: 1_000_000n,
      holdBalance: 0n,
      status: 'ACTIVE',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    const wallet = {
      id: 'target-user-wallet',
      userId: 'user-2',
      accountCode: null,
      accountType: 'USER_WALLET',
      currency: 'CBDC_IDR',
      availableBalance: 0n,
      holdBalance: 0n,
      status: 'ACTIVE',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    const balances = new Map([
      [reserve.id, reserve.availableBalance],
      [wallet.id, wallet.availableBalance],
    ]);
    const tx = {
      $queryRaw: jest.fn(),
      user: { findUnique: jest.fn().mockResolvedValue({ id: 'user-2', kycTier: 'VERIFIED' }) },
      walletAccount: {
        findUniqueOrThrow: jest.fn(({ where }) => {
          if (where.accountCode === 'CENTRAL_RESERVE') return Promise.resolve(reserve);
          if (where.id === wallet.id) return Promise.resolve(wallet);
          throw new Error('Unexpected lookup');
        }),
        findMany: jest.fn().mockResolvedValue([reserve, wallet]),
        update: jest.fn(({ where, data }) => {
          const next = (balances.get(where.id) ?? 0n) + data.availableBalance.increment;
          balances.set(where.id, next);
          return Promise.resolve({ ...(where.id === reserve.id ? reserve : wallet), availableBalance: next });
        }),
      },
      transaction: { create: jest.fn() },
      monetaryPolicyEvent: { create: jest.fn() },
    };
    const prisma = { $transaction: jest.fn((callback) => callback(tx)) };
    const ledger = { post: jest.fn() };
    const idempotency = {
      start: jest.fn().mockResolvedValue({ replay: false }),
      complete: jest.fn(),
    };
    const audit = { record: jest.fn() };
    const money = { assertPositive: jest.fn(), parse: jest.fn() };

    return {
      service: new SettlementService(prisma as never, ledger as never, {} as never, idempotency as never, audit as never, money as never),
      tx,
      audit,
    };
  }

  it('issues CBDC from reserve to a user wallet and records a monetary policy event', async () => {
    const { service, tx, audit } = buildIssuanceService();

    await service.issueCurrency({
      targetWalletId: 'target-user-wallet',
      amount: 50_000n,
      reasonCode: 'MONETARY_EXPANSION',
      actorUserId: 'admin-1',
      requestId: 'req-issue',
      idempotency: {
        key: 'idem-issue',
        route: 'POST /api/v1/central-bank/issuance',
        actorId: 'admin-1',
        requestHash: 'hash-issue',
      },
    });

    expect(tx.transaction.create).toHaveBeenCalledWith(
      expect.objectContaining({ data: expect.objectContaining({ transactionType: 'ISSUANCE' }) }),
    );
    expect(tx.monetaryPolicyEvent.create).toHaveBeenCalledWith(
      expect.objectContaining({ data: expect.objectContaining({ eventType: 'ISSUANCE', amount: 50_000n }) }),
    );
    expect(audit.record).toHaveBeenCalledWith(
      expect.objectContaining({ action: 'ISSUANCE_SETTLED', reasonCode: 'MONETARY_EXPANSION' }),
    );
  });

  it('rejects issuance exceeding available reserve balance', async () => {
    const { service } = buildIssuanceService();
    await expect(
      service.issueCurrency({
        targetWalletId: 'target-user-wallet',
        amount: 2_000_000n,
        reasonCode: 'OVER_LIMIT',
        actorUserId: 'admin-1',
        requestId: 'req-issue-fail',
        idempotency: {
          key: 'idem-issue-fail',
          route: 'POST /api/v1/central-bank/issuance',
          actorId: 'admin-1',
          requestHash: 'hash-fail',
        },
      }),
    ).rejects.toMatchObject({ code: ErrorCode.INSUFFICIENT_BALANCE });
  });

  function buildBurnService() {
    const source = {
      id: 'source-user-wallet',
      userId: 'user-3',
      accountCode: null,
      accountType: 'USER_WALLET',
      currency: 'CBDC_IDR',
      availableBalance: 80_000n,
      holdBalance: 0n,
      status: 'ACTIVE',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    const sink = {
      id: 'sink-wallet',
      userId: null,
      accountCode: 'BURN_OR_SINK_ACCOUNT',
      accountType: 'BURN_OR_SINK_ACCOUNT',
      currency: 'CBDC_IDR',
      availableBalance: 0n,
      holdBalance: 0n,
      status: 'ACTIVE',
      createdAt: new Date(),
      updatedAt: new Date(),
    };
    const balances = new Map([
      [source.id, source.availableBalance],
      [sink.id, sink.availableBalance],
    ]);
    const tx = {
      $queryRaw: jest.fn(),
      user: { findUnique: jest.fn() },
      walletAccount: {
        findUniqueOrThrow: jest.fn(({ where }) => {
          if (where.id === source.id) return Promise.resolve(source);
          if (where.accountCode === 'BURN_OR_SINK_ACCOUNT') return Promise.resolve(sink);
          throw new Error('Unexpected lookup');
        }),
        findMany: jest.fn().mockResolvedValue([source, sink]),
        update: jest.fn(({ where, data }) => {
          const next = (balances.get(where.id) ?? 0n) + data.availableBalance.increment;
          balances.set(where.id, next);
          return Promise.resolve({ ...(where.id === source.id ? source : sink), availableBalance: next });
        }),
      },
      transaction: { create: jest.fn() },
      monetaryPolicyEvent: { create: jest.fn() },
    };
    const prisma = { $transaction: jest.fn((callback) => callback(tx)) };
    const ledger = { post: jest.fn() };
    const idempotency = {
      start: jest.fn().mockResolvedValue({ replay: false }),
      complete: jest.fn(),
    };
    const audit = { record: jest.fn() };
    const money = { assertPositive: jest.fn(), parse: jest.fn() };

    return {
      service: new SettlementService(prisma as never, ledger as never, {} as never, idempotency as never, audit as never, money as never),
      tx,
      audit,
    };
  }

  it('burns CBDC from a user wallet to the sink account', async () => {
    const { service, tx, audit } = buildBurnService();

    await service.burnCurrency({
      sourceWalletId: 'source-user-wallet',
      amount: 30_000n,
      reasonCode: 'MONETARY_CONTRACTION',
      actorUserId: 'admin-2',
      requestId: 'req-burn',
      idempotency: {
        key: 'idem-burn',
        route: 'POST /api/v1/central-bank/burn',
        actorId: 'admin-2',
        requestHash: 'hash-burn',
      },
    });

    expect(tx.transaction.create).toHaveBeenCalledWith(
      expect.objectContaining({ data: expect.objectContaining({ transactionType: 'BURN' }) }),
    );
    expect(tx.monetaryPolicyEvent.create).toHaveBeenCalledWith(
      expect.objectContaining({ data: expect.objectContaining({ eventType: 'BURN', amount: 30_000n }) }),
    );
    expect(audit.record).toHaveBeenCalledWith(
      expect.objectContaining({ action: 'BURN_SETTLED', reasonCode: 'MONETARY_CONTRACTION' }),
    );
  });

  it('rejects burn when source is not a USER_WALLET or MERCHANT_WALLET', async () => {
    const { service } = buildBurnService();
    const txReject = {
      $queryRaw: jest.fn(),
      walletAccount: {
        findUniqueOrThrow: jest.fn(({ where }) => {
          if (where.id === 'reserve-id') {
            return Promise.resolve({
              id: 'reserve-id', userId: null, accountCode: 'CENTRAL_RESERVE',
              accountType: 'CENTRAL_RESERVE', currency: 'CBDC_IDR',
              availableBalance: 1_000_000n, holdBalance: 0n, status: 'ACTIVE',
              createdAt: new Date(), updatedAt: new Date(),
            });
          }
          throw new Error('Unexpected');
        }),
      },
    };
    const prisma = { $transaction: jest.fn((callback) => callback(txReject)) };
    const money = { assertPositive: jest.fn() };
    const svc = new SettlementService(prisma as never, {} as never, {} as never, { start: jest.fn().mockResolvedValue({ replay: false }) } as never, { record: jest.fn() } as never, money as never);
    await expect(
      svc.burnCurrency({
        sourceWalletId: 'reserve-id',
        amount: 1n,
        reasonCode: 'INVALID_BURN',
        actorUserId: 'admin-2',
        requestId: 'req-burn-bad',
        idempotency: { key: 'idem-burn-bad', route: 'POST /api/v1/central-bank/burn', actorId: 'admin-2', requestHash: 'h' },
      }),
    ).rejects.toMatchObject({ code: ErrorCode.BURN_ACCOUNT_INVALID });
  });
});
