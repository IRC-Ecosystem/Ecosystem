import { Router } from 'express';
import { authMiddleware } from '../middleware/auth';
import { PaymentService } from '../services/payment.service';
import { AuditService } from '../services/audit.service';
import { AppError } from '../middleware/errorHandler';
import { z } from 'zod';
import { idempotencyMiddleware } from '../middleware/idempotency';
import { createRateLimiter } from '../middleware/rateLimit';
import { InsightEventService } from '../services/insight-event.service';

const router = Router();
router.use(authMiddleware);
router.use(createRateLimiter({ windowMs: 60_000, limit: 100 }));
router.use(idempotencyMiddleware);

const paymentSchema = z.object({
  buyer_external_id: z.string().min(1).max(191),
  seller_external_id: z.string().min(1).max(191),
  gross_amount: z.string().regex(/^\d+$/),
  pin: z.string().regex(/^\d{6}$/),
  description: z.string().max(512).optional().default(''),
  external_ref_id: z.string().max(191).optional(),
});

const refundSchema = z.object({
  external_ref_id: z.string().min(1).max(191),
  reason_code: z.string().min(3).max(128),
});

router.post('/', async (req, res, next) => {
  try {
    const { buyer_external_id, seller_external_id, gross_amount, pin, description, external_ref_id } = paymentSchema.parse(req.body);
    const service = (req as any).service;
    const idempotencyKey = (req.headers['x-idempotency-key'] ?? req.headers['idempotency-key']) as string | undefined;
    
    if (!idempotencyKey) {
      throw new AppError(400, 'IDEMPOTENCY_KEY_REQUIRED', 'Header X-Idempotency-Key wajib dikirim');
    }

    const result = await PaymentService.processPayment(
      buyer_external_id,
      seller_external_id,
      gross_amount,
      pin,
      description,
      external_ref_id,
      service.id,
      service.service_name,
      idempotencyKey
    );

    await AuditService.record({
      serviceId: service.id,
      serviceName: service.service_name,
      actorType: 'SISTER_APP',
      actorId: buyer_external_id,
      action: 'PAYMENT_SETTLED',
      targetType: 'TRANSACTION',
      targetId: result.data?.transaction_id ?? result.transaction_id,
      requestId: req.header('x-request-id') ?? undefined,
      ipAddress: req.ip,
      metadata: {
        seller_external_id,
        gross_amount,
        external_ref_id,
        idempotency_key: idempotencyKey,
      },
    });
    void InsightEventService.publishPaymentSettled({ serviceName: service.service_name, buyerExternalId: buyer_external_id, sellerExternalId: seller_external_id, grossAmount: gross_amount, externalRefId: external_ref_id, result });
    res.json({ success: true, data: result.data || result });
  } catch (error) {
    next(error);
  }
});

router.post('/refunds', async (req, res, next) => {
  try {
    const { external_ref_id, reason_code } = refundSchema.parse(req.body);
    const service = (req as any).service;
    const idempotencyKey = (req.headers['x-idempotency-key'] ?? req.headers['idempotency-key']) as string | undefined;
    if (!idempotencyKey) throw new AppError(400, 'IDEMPOTENCY_KEY_REQUIRED', 'Header X-Idempotency-Key wajib dikirim');

    const result = await PaymentService.refundPayment(external_ref_id, reason_code, service.id, service.service_name, idempotencyKey);
    await AuditService.record({
      serviceId: service.id,
      serviceName: service.service_name,
      actorType: 'SISTER_APP',
      actorId: service.service_name,
      action: 'PAYMENT_REVERSED',
      targetType: 'TRANSACTION',
      targetId: result.reversal_transaction_id,
      requestId: req.header('x-request-id') ?? undefined,
      ipAddress: req.ip,
      metadata: { external_ref_id, reason_code, idempotency_key: idempotencyKey },
    });
    res.json({ success: true, data: result });
  } catch (error) {
    next(error);
  }
});

export const paymentsRoutes = router;
