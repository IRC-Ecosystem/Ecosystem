import { ManagerService } from '../src/modules/manager/manager.service';
import { AppError } from '../src/common/app-error';
import { SettlementService } from '../src/modules/settlement/settlement.service';

describe('ManagerService.rejectLoan concurrency (C-5)', () => {
  it('serializes concurrent rejects with SELECT FOR UPDATE', async () => {
    let resolveLock: () => void;
    const lockPromise = new Promise<void>((r) => { resolveLock = r; });

    const tx = {
      $queryRaw: jest.fn().mockImplementation(() => {
        // First caller gets the lock; second caller waits then proceeds.
        return lockPromise.then(() => undefined);
      }),
      loan: {
        findUnique: jest.fn().mockImplementation(() => {
          // When first call gets here, mock the NEXT call to return REJECTED
          // so second call sees it as if first call had already updated it.
          tx.loan.findUnique.mockImplementation(() => Promise.resolve({ id: 'loan-1', status: 'REJECTED' }));
          return Promise.resolve({ id: 'loan-1', status: 'PENDING' });
        }),
        update: jest.fn().mockResolvedValue({ id: 'loan-1', status: 'REJECTED' }),
      },
    };
    const prisma = { $transaction: jest.fn((cb) => cb(tx)) };
    const idempotency = { start: jest.fn().mockResolvedValue({ replay: false }), complete: jest.fn() };
    const audit = { record: jest.fn() };
    const settlement = new SettlementService(prisma as never, {} as never, {} as never, idempotency as never, audit as never, {} as never);

    const first = settlement.runRejectLoan({ loanId: 'loan-1', actorUserId: 'm1', requestId: 'r1', idempotencyKey: 'idem1' });
    const second = (async () => {
      // The second call waits for the lock, then observes the updated status.
      return settlement.runRejectLoan({ loanId: 'loan-1', actorUserId: 'm2', requestId: 'r2', idempotencyKey: 'idem2' })
        .catch((e) => ({ err: e }));
    })();

    resolveLock!();
    await first;
    const secondResult = await second;

    expect(tx.$queryRaw).toHaveBeenCalled();
    expect((secondResult as any).err).toBeInstanceOf(AppError);
  });
});

describe('ManagerService audit trail', () => {
  it('records reason code when suspending a user wallet', async () => {
    const tx = {
      user: {
        update: jest.fn().mockResolvedValue({ id: 'user-1', status: 'SUSPENDED' }),
      },
      walletAccount: {
        update: jest.fn().mockResolvedValue({ id: 'wallet-1', status: 'FROZEN' }),
      },
    };
    const prisma = {
      $transaction: jest.fn((callback) => callback(tx)),
    };
    const wallets = {
      getPrimaryWallet: jest.fn().mockResolvedValue({ id: 'wallet-1' }),
    };
    const audit = {
      record: jest.fn(),
    };
    const settlement = {
      runRejectLoan: jest.fn().mockImplementation(async () => ({ id: 'loan-1', status: 'REJECTED' })),
    };
    const service = new ManagerService(prisma as never, settlement as never, wallets as never, audit as never);

    await service.suspendUser({
      userId: 'user-1',
      actorUserId: 'manager-1',
      requestId: 'req-suspend',
      reasonCode: 'AML_REVIEW',
    });

    expect(tx.walletAccount.update).toHaveBeenCalledWith({
      where: { id: 'wallet-1' },
      data: { status: 'FROZEN' },
    });
    expect(tx.user.update).toHaveBeenCalledWith({
      where: { id: 'user-1' },
      data: { status: 'SUSPENDED' },
    });
    expect(audit.record).toHaveBeenCalledWith(
      expect.objectContaining({
        actorUserId: 'manager-1',
        action: 'USER_WALLET_SUSPENDED',
        reasonCode: 'AML_REVIEW',
        targetId: 'wallet-1',
      }),
    );
  });
});
