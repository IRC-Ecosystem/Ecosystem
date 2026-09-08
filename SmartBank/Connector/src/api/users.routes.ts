import { Router } from 'express';
import { authMiddleware } from '../middleware/auth';
import { OtpService } from '../services/otp.service';
import { LinkageService } from '../services/linkage.service';
import { AuditService } from '../services/audit.service';
import { normalizePhone } from '../utils/phone';
import { z } from 'zod';
import { idempotencyMiddleware } from '../middleware/idempotency';
import { createRateLimiter } from '../middleware/rateLimit';
import { env } from '../config/env';

const router = Router();

router.use(authMiddleware);
router.use(createRateLimiter({ windowMs: 60_000, limit: Number(env.RATE_LIMIT_DEFAULT_RPS) }));
router.use(idempotencyMiddleware);

const otpRequestSchema = z.object({
  phone: z.string().min(3),
  purpose: z.literal('WALLET_LINK').optional(),
});

const otpVerifySchema = z.object({
  request_id: z.string().min(1),
  code: z.string().regex(/^\d{6}$/),
});

const linkSchema = z.object({
  external_user_id: z.string().min(1).max(191),
  verification_token: z.string().min(1),
});

router.post('/otp/request', async (req, res, next) => {
  try {
    const { phone } = otpRequestSchema.parse(req.body);
    const service = (req as any).service;
    const normalized = normalizePhone(phone);

    const result = await OtpService.requestOtp(normalized, service.id, service.display_name);
    await AuditService.record({
      serviceId: service.id,
      serviceName: service.service_name,
      actorType: 'SISTER_APP',
      action: 'OTP_REQUESTED',
      targetType: 'PHONE',
      targetId: result.phone_masked,
      requestId: req.header('x-request-id') ?? undefined,
      ipAddress: req.ip,
      metadata: { request_id: result.request_id },
    });
    res.json({ success: true, data: result });
  } catch (error) {
    next(error);
  }
});

router.post('/otp/verify', async (req, res, next) => {
  try {
    const { request_id, code } = otpVerifySchema.parse(req.body);
    const service = (req as any).service;
    const result = await OtpService.verifyOtp(request_id, code, service.id);
    await AuditService.record({
      serviceId: service.id,
      serviceName: service.service_name,
      actorType: 'SISTER_APP',
      action: 'OTP_VERIFIED',
      targetType: 'USER',
      targetId: result.smartbank_user_id,
      requestId: req.header('x-request-id') ?? undefined,
      ipAddress: req.ip,
      metadata: { request_id },
    });
    res.json({ success: true, data: result });
  } catch (error) {
    next(error);
  }
});

router.post('/link', createRateLimiter({
  windowMs: 60 * 60 * 1000,
  limit: 10,
  key: (req) => `${(req as any).service?.id}:${String(req.body?.external_user_id ?? '')}`,
}), async (req, res, next) => {
  try {
    const { external_user_id, verification_token } = linkSchema.parse(req.body);
    const service = (req as any).service;
    const result = await LinkageService.linkUser(external_user_id, verification_token, service.id, service.display_name);
    await AuditService.record({
      serviceId: service.id,
      serviceName: service.service_name,
      actorType: 'SISTER_APP',
      actorId: external_user_id,
      action: 'USER_LINKED',
      targetType: 'WALLET',
      targetId: result.smartbank_wallet_id,
      requestId: req.header('x-request-id') ?? undefined,
      ipAddress: req.ip,
      metadata: { smartbank_user_id: result.smartbank_user_id, created: result.created },
    });
    res.json({ success: true, data: result });
  } catch (error) {
    next(error);
  }
});

router.get('/:external_user_id', async (req, res, next) => {
  try {
    const service = (req as any).service;
    const result = await LinkageService.getLinkage(req.params.external_user_id, service.id);
    res.json({ success: true, data: { smartbank_wallet_id: result.smartbank_wallet_id } });
  } catch (error) {
    next(error);
  }
});

router.delete('/:external_user_id', async (req, res, next) => {
  try {
    const service = (req as any).service;
    const result = await LinkageService.unlinkUser(req.params.external_user_id, service.id, service.display_name);
    await AuditService.record({
      serviceId: service.id,
      serviceName: service.service_name,
      actorType: 'SISTER_APP',
      actorId: req.params.external_user_id,
      action: 'USER_UNLINKED',
      targetType: 'USER',
      targetId: req.params.external_user_id,
      requestId: req.header('x-request-id') ?? undefined,
      ipAddress: req.ip,
    });
    res.json({ success: true, data: result });
  } catch (error) {
    next(error);
  }
});

export const usersRoutes = router;
