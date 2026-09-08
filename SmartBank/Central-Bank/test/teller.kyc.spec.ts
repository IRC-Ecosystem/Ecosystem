import { TellerService } from '../src/modules/teller/teller.service';
import { ErrorCode } from '../src/common/error-codes';

describe('TellerService KYC approval (C-1)', () => {
  function build() {
    const tx = {
      user: {
        findUniqueOrThrow: jest.fn(),
        update: jest.fn(),
      },
    };
    const prisma = { $transaction: jest.fn((cb) => cb(tx)) };
    const audit = { record: jest.fn() };
    const service = new TellerService(prisma as never, {} as never, {} as never, {} as never, audit as never);
    return { tx, service };
  }

  it('rejects approval when user has not passed base KYC', async () => {
    const { tx, service } = build();
    tx.user.findUniqueOrThrow.mockResolvedValue({
      id: 'u1', role: 'WALLET_USER', kycTier: 'BASIC', pendingRole: 'MERCHANT',
    });
    await expect(
      service.approveKyc({ userId: 'u1', approvedRole: 'MERCHANT', actorUserId: 't1', requestId: 'r1' }),
    ).rejects.toMatchObject({ code: ErrorCode.VALIDATION_ERROR });
    expect(tx.user.update).not.toHaveBeenCalled();
  });

  it('promotes user and clears pending fields on valid approval', async () => {
    const { tx, service } = build();
    tx.user.findUniqueOrThrow.mockResolvedValue({
      id: 'u1', role: 'WALLET_USER', kycTier: 'VERIFIED', pendingRole: 'MERCHANT',
    });
    tx.user.update.mockResolvedValue({ id: 'u1', role: 'MERCHANT' });
    await service.approveKyc({ userId: 'u1', approvedRole: 'MERCHANT', actorUserId: 't1', requestId: 'r1' });
    expect(tx.user.update).toHaveBeenCalledWith({
      where: { id: 'u1' },
      data: expect.objectContaining({ role: 'MERCHANT', pendingRole: null, pendingRoleRequestedAt: null }),
    });
  });
});