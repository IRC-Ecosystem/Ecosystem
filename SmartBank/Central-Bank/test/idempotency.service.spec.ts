import { IdempotencyService } from '../src/modules/idempotency/idempotency.service';
import { ErrorCode } from '../src/common/error-codes';
import { Prisma } from '@prisma/client';

describe('IdempotencyService', () => {
  it('replays completed requests with the same request hash', async () => {
    const service = new IdempotencyService();
    const tx = {
      idempotencyKey: {
        findUnique: jest.fn().mockResolvedValue({
          requestHash: 'hash',
          status: 'COMPLETED',
          responseBody: { transaction_id: 'trx-1' },
        }),
      },
    };
    await expect(
      service.start(tx as never, {
        key: 'key',
        route: 'POST /api/v1/transfers',
        actorId: 'actor',
        requestHash: 'hash',
      }),
    ).resolves.toEqual({ replay: true, response: { transaction_id: 'trx-1' } });
  });

  it('rejects same key with different request hash', async () => {
    const service = new IdempotencyService();
    const tx = {
      idempotencyKey: {
        findUnique: jest.fn().mockResolvedValue({ requestHash: 'old-hash', status: 'COMPLETED' }),
      },
    };
    await expect(
      service.start(tx as never, {
        key: 'key',
        route: 'POST /api/v1/transfers',
        actorId: 'actor',
        requestHash: 'new-hash',
      }),
    ).rejects.toMatchObject({ code: ErrorCode.IDEMPOTENCY_CONFLICT });
  });

  // H1: verifikasi P2002 (race antar dua request konkuren) tidak lagi bocor jadi 500.
  it('returns IDEMPOTENCY_CONFLICT on P2002 race — not a 500 internal error', async () => {
    const service = new IdempotencyService();
    const P2002 = new Prisma.PrismaClientKnownRequestError('Unique constraint failed', {
      code: 'P2002',
      clientVersion: '5.0.0',
    });
    const tx = {
      idempotencyKey: {
        findUnique: jest.fn().mockResolvedValue(null),
        create: jest.fn().mockRejectedValue(P2002),
      },
    };
    await expect(
      service.start(tx as never, {
        key: 'key',
        route: 'POST /api/v1/transfers',
        actorId: 'actor',
        requestHash: 'hash',
      }),
    ).rejects.toMatchObject({ code: ErrorCode.IDEMPOTENCY_CONFLICT });
  });
});
