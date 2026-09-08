import { WalletAccountService } from '../src/modules/wallets/wallet-account.service';
import { ErrorCode } from '../src/common/error-codes';

// C3 self-check: upgrade request hanya mencatat pendingRole — TIDAK pernah
// mengubah `role` atau `kycTier` (menutup vektor self-escalation).
describe('WalletAccountService.requestUpgrade (C3)', () => {
  const makeService = (user: { role: string; pendingRole: string | null; pendingRoleRequestedAt?: Date | null }) => {
    const tx = {
      user: {
        findUniqueOrThrow: jest.fn().mockResolvedValue(user),
        update: jest.fn().mockResolvedValue({ ...user }),
      },
    };
    const prisma = { $transaction: jest.fn(async (cb: (t: typeof tx) => Promise<unknown>) => cb(tx)) };
    const audit = { record: jest.fn().mockResolvedValue(undefined) };
    const service = new WalletAccountService(prisma as never, audit as never);
    return { service, tx, audit };
  };

  it('rejects a non-upgradeable role (e.g. admin)', async () => {
    const { service } = makeService({ role: 'WALLET_USER', pendingRole: null });
    await expect(
      service.requestUpgrade({ userId: 'u1', requestedRole: 'CENTRAL_BANK_ADMIN', requestId: 'r1' }),
    ).rejects.toMatchObject({ code: ErrorCode.VALIDATION_ERROR });
  });

  it('sets pendingRole only — never touches role or kycTier (no self-grant)', async () => {
    const { service, tx, audit } = makeService({ role: 'WALLET_USER', pendingRole: null });
    const result = await service.requestUpgrade({
      userId: 'u1',
      requestedRole: 'MERCHANT',
      businessName: 'Toko Budi',
      nik: '1234567890123456',
      requestId: 'r1',
    });

    expect(result).toMatchObject({ status: 'PENDING', pending_role: 'MERCHANT' });
    expect(tx.user.update).toHaveBeenCalledTimes(1);
    const data = tx.user.update.mock.calls[0][0].data;
    expect(data.pendingRole).toBe('MERCHANT');
    expect(data).not.toHaveProperty('role');
    expect(data).not.toHaveProperty('kycTier');
    expect(audit.record).toHaveBeenCalledWith(expect.objectContaining({ action: 'UPGRADE_REQUESTED' }));
  });

  it('is idempotent when the same role is already pending (no second update)', async () => {
    const { service, tx } = makeService({ role: 'WALLET_USER', pendingRole: 'MERCHANT' });
    const result = await service.requestUpgrade({ userId: 'u1', requestedRole: 'MERCHANT', requestId: 'r1' });
    expect(result.status).toBe('PENDING');
    expect(tx.user.update).not.toHaveBeenCalled();
  });

  it('rejects a user who already holds a final (non-WALLET_USER) role', async () => {
    const { service } = makeService({ role: 'MERCHANT', pendingRole: null });
    await expect(
      service.requestUpgrade({ userId: 'u1', requestedRole: 'MERCHANT', requestId: 'r1' }),
    ).rejects.toMatchObject({ code: ErrorCode.VALIDATION_ERROR });
  });
});
