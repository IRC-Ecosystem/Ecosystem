import { Injectable } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { randomUUID } from 'crypto';
import { AppError } from '../../common/app-error';
import { ErrorCode } from '../../common/error-codes';

@Injectable()
export class IdempotencyService {
  async start(
    tx: Prisma.TransactionClient,
    input: { key: string; route: string; actorId: string; requestHash: string },
  ): Promise<{ replay: false } | { replay: true; response: unknown }> {
    const existing = await tx.idempotencyKey.findUnique({
      where: {
        idempotencyKey_route_actorId: {
          idempotencyKey: input.key,
          route: input.route,
          actorId: input.actorId,
        },
      },
    });
    if (existing) {
      if (existing.requestHash !== input.requestHash) {
        throw new AppError(ErrorCode.IDEMPOTENCY_CONFLICT, 'Idempotency-Key dipakai dengan body berbeda');
      }
      if (existing.status === 'COMPLETED') return { replay: true, response: existing.responseBody };
      throw new AppError(ErrorCode.IDEMPOTENCY_CONFLICT, 'Request dengan Idempotency-Key ini masih diproses');
    }
    // H1: Dua request konkuren bisa sama-sama findUnique-miss lalu create → P2002.
    // Catch P2002 dan kembalikan 409 (bukan 500) agar client retry.
    try {
      await tx.idempotencyKey.create({
        data: {
          id: randomUUID(),
          idempotencyKey: input.key,
          route: input.route,
          actorId: input.actorId,
          requestHash: input.requestHash,
          status: 'PROCESSING',
          // ponytail: lockedUntil (60s) vestigial — idempotency record co-located
          // dalam $transaction yang sama dengan work, crash → rollback row.
          // Field tidak pernah dibaca oleh start(). Hapus saat schema next migration.
          lockedUntil: new Date(Date.now() + 60_000),
        },
      });
    } catch (err: unknown) {
      if (err instanceof Prisma.PrismaClientKnownRequestError && err.code === 'P2002') {
        throw new AppError(
          ErrorCode.IDEMPOTENCY_CONFLICT,
          'Idempotency-Key ini sedang diproses oleh request bersamaan, silakan coba lagi',
        );
      }
      throw err;
    }
    return { replay: false };
  }

  async complete(
    tx: Prisma.TransactionClient,
    input: { key: string; route: string; actorId: string; responseBody: Prisma.InputJsonValue },
  ) {
    await tx.idempotencyKey.update({
      where: {
        idempotencyKey_route_actorId: {
          idempotencyKey: input.key,
          route: input.route,
          actorId: input.actorId,
        },
      },
      data: {
        status: 'COMPLETED',
        responseBody: input.responseBody,
        lockedUntil: null,
      },
    });
  }
}
