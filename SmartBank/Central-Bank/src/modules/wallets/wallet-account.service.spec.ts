import { WalletAccountService } from './wallet-account.service';

describe('WalletAccountService', () => {
  it('finds an Indonesian phone stored with local format', async () => {
    const findFirst = jest.fn().mockResolvedValue({
      id: 'user-1', name: 'Test', kycTier: 'BASIC', phone: '081234567890', status: 'ACTIVE', wallets: [{ id: 'wallet-1' }],
    });
    const service = new WalletAccountService({ user: { findFirst } } as any, {} as any);

    await service.getWalletByPhone('+6281234567890');

    expect(findFirst).toHaveBeenCalledWith(expect.objectContaining({
      where: { phone: { in: ['+6281234567890', '+6281234567890', '081234567890'] } },
    }));
  });
});
