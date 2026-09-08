import { Router } from 'express';
import { prisma } from '../lib/prisma';
import bcrypt from 'bcrypt';
import crypto from 'crypto';
import { z } from 'zod';
import { adminAuth } from '../middleware/adminAuth';
import { AuditService } from '../services/audit.service';
import { AppError } from '../middleware/errorHandler';
import { idempotencyMiddleware } from '../middleware/idempotency';
import { createRateLimiter } from '../middleware/rateLimit';

const router = Router();
router.use(adminAuth);
router.use(createRateLimiter({ windowMs: 60_000, limit: 60, key: (req) => req.ip ?? 'unknown' }));
router.use(idempotencyMiddleware);

const serviceSchema = z.object({
  service_name: z.string().regex(/^[A-Z0-9_-]{2,64}$/),
  display_name: z.string().min(2).max(191),
  rate_limit_rps: z.number().int().min(1).max(10_000).optional(),
  key_label: z.string().max(191).optional(),
});

async function issueKey(serviceId: string, label?: string) {
  const key = `sbk_${crypto.randomBytes(32).toString('hex')}`;
  return {
    plain: key,
    record: await prisma.serviceApiKey.create({ data: {
      service_id: serviceId,
      key_prefix: key.substring(0, 8),
      key_hash: await bcrypt.hash(key, 12),
      label,
    } }),
  };
}

router.post('/services', async (req, res, next) => {
  try {
    const input = serviceSchema.parse(req.body);
    const existing = await prisma.service.findUnique({ where: { service_name: input.service_name } });
    if (existing) throw new AppError(409, 'SERVICE_EXISTS', 'Service sudah terdaftar');
    const service = await prisma.service.create({ data: {
      service_name: input.service_name,
      display_name: input.display_name,
      rate_limit_rps: input.rate_limit_rps,
    } });
    const key = await issueKey(service.id, input.key_label ?? 'initial');
    await AuditService.record({ actorType: 'ADMIN', action: 'SERVICE_REGISTERED', targetType: 'SERVICE', targetId: service.id, metadata: { service_name: service.service_name } });
    res.status(201).json({ success: true, data: { service, api_key: key.plain, key_id: key.record.id } });
  } catch (error) { next(error); }
});

router.get('/services', async (_req, res, next) => {
  try {
    const services = await prisma.service.findMany({ include: { api_keys: { select: { id: true, key_prefix: true, label: true, status: true, last_used_at: true, expires_at: true, created_at: true } } }, orderBy: { created_at: 'desc' } });
    res.json({ success: true, data: services });
  } catch (error) { next(error); }
});

router.post('/services/:id/keys', async (req, res, next) => {
  try {
    const service = await prisma.service.findUnique({ where: { id: req.params.id } });
    if (!service) throw new AppError(404, 'SERVICE_NOT_FOUND', 'Service tidak ditemukan');
    const key = await issueKey(service.id, z.object({ label: z.string().max(191).optional() }).parse(req.body).label);
    await AuditService.record({ actorType: 'ADMIN', action: 'API_KEY_ISSUED', targetType: 'SERVICE_API_KEY', targetId: key.record.id, metadata: { service_id: service.id } });
    res.status(201).json({ success: true, data: { api_key: key.plain, key_id: key.record.id, key_prefix: key.record.key_prefix } });
  } catch (error) { next(error); }
});

router.delete('/services/:serviceId/keys/:keyId', async (req, res, next) => {
  try {
    const key = await prisma.serviceApiKey.findFirst({ where: { id: req.params.keyId, service_id: req.params.serviceId } });
    if (!key) throw new AppError(404, 'API_KEY_NOT_FOUND', 'API key tidak ditemukan');
    await prisma.serviceApiKey.update({ where: { id: key.id }, data: { status: 'REVOKED', revoked_at: new Date() } });
    await AuditService.record({ actorType: 'ADMIN', action: 'API_KEY_REVOKED', targetType: 'SERVICE_API_KEY', targetId: key.id, metadata: { service_id: req.params.serviceId } });
    res.json({ success: true, data: { revoked: true } });
  } catch (error) { next(error); }
});

router.get('/audit', async (req, res, next) => {
  try {
    const limit = Math.min(200, Math.max(1, Number(req.query.limit) || 50));
    const logs = await prisma.auditLog.findMany({
      where: {
        ...(req.query.service_id ? { service_id: String(req.query.service_id) } : {}),
        ...(req.query.action ? { action: String(req.query.action) } : {}),
      },
      orderBy: { timestamp: 'desc' },
      take: limit,
    });
    res.json({ success: true, data: { logs, count: logs.length } });
  } catch (error) { next(error); }
});

router.get('/audit/verify', async (_req, res, next) => {
  try { res.json({ success: true, data: await AuditService.verifyChain() }); } catch (error) { next(error); }
});

export const adminRoutes = router;
