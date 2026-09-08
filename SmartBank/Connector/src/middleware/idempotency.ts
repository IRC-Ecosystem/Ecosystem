import { NextFunction, Request, Response } from 'express';
import { Prisma } from '@prisma/client';
import { prisma } from '../lib/prisma';
import crypto from 'crypto';
import { AppError } from './errorHandler';

const MUTATING_METHODS = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);

function requestHash(req: Request) {
  return crypto.createHash('sha256').update(JSON.stringify({ method: req.method, path: req.baseUrl + req.path, body: req.body ?? null })).digest('hex');
}

export async function idempotencyMiddleware(req: Request, res: Response, next: NextFunction) {
  if (!MUTATING_METHODS.has(req.method.toUpperCase())) return next();

  try {
    const key = req.header('idempotency-key') ?? req.header('x-idempotency-key');
    if (!key || key.length < 8 || key.length > 191) {
      throw new AppError(400, 'IDEMPOTENCY_KEY_REQUIRED', 'Idempotency-Key wajib dikirim (8-191 karakter)');
    }

    const serviceId = ((req as any).service?.id as string | undefined) ?? (req.baseUrl.includes('/admin') ? 'ADMIN' : undefined);
    if (!serviceId) throw new AppError(500, 'SERVICE_CONTEXT_MISSING', 'Service context tidak tersedia');
    const endpoint = `${req.method.toUpperCase()} ${req.baseUrl}${req.path}`;
    const hash = requestHash(req);
    const existing = await prisma.idempotencyRecord.findUnique({
      where: { service_id_endpoint_key: { service_id: serviceId, endpoint, key } },
    });

    if (existing && existing.expires_at > new Date()) {
      if (existing.request_hash !== hash) {
        throw new AppError(409, 'IDEMPOTENCY_CONFLICT', 'Idempotency-Key pernah digunakan dengan payload berbeda');
      }
      res.setHeader('Idempotency-Replayed', 'true');
      return res.status(existing.response_status).json(existing.response_body);
    }

    const originalJson = res.json.bind(res);
    res.json = ((body: unknown) => {
      const status = res.statusCode;
      if (status >= 200 && status < 300) {
        void prisma.idempotencyRecord.upsert({
          where: { service_id_endpoint_key: { service_id: serviceId, endpoint, key } },
          create: {
            service_id: serviceId,
            endpoint,
            key,
            request_hash: hash,
            response_status: status,
            response_body: body as Prisma.InputJsonValue,
            expires_at: new Date(Date.now() + 24 * 60 * 60 * 1000),
          },
          update: {
            request_hash: hash,
            response_status: status,
            response_body: body as Prisma.InputJsonValue,
            expires_at: new Date(Date.now() + 24 * 60 * 60 * 1000),
          },
        }).catch((error) => console.error('Idempotency persistence failed', error));
      }
      return originalJson(body);
    }) as Response['json'];

    return next();
  } catch (error) {
    return next(error);
  }
}
