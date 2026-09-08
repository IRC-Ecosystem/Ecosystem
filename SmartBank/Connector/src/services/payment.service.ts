import { CentralBankClient } from '../integrations/centralbank.client';
import { LinkageService } from './linkage.service';
import { AppError } from '../middleware/errorHandler';
import { prisma } from '../lib/prisma';


export class PaymentService {
  static async processPayment(
    buyerExternalId: string,
    sellerExternalId: string,
    grossAmount: string,
    pin: string,
    description: string,
    externalRefId: string | undefined,
    serviceId: string,
    serviceName: string,
    idempotencyKey: string
  ) {
    // 1. Resolve linkages
    const buyerLink = await LinkageService.getLinkage(buyerExternalId, serviceId);
    const sellerLink = await LinkageService.getLinkage(sellerExternalId, serviceId);

    // 2. Call CentralBank atomic settlement
    const payload = {
      payer_wallet_id: buyerLink.smartbank_wallet_id,
      payee_wallet_id: sellerLink.smartbank_wallet_id,
      gross_amount: grossAmount,
      pin,
      source_app: serviceName,
      description,
      external_ref_id: externalRefId,
    };

    const res = await CentralBankClient.settlePayment(
      payload,
      idempotencyKey,
      buyerLink.smartbank_user_id, // delegated user
      serviceName
    );

    if (res.status === 401 && res.body.error?.code === 'INVALID_PIN') {
      throw new AppError(401, 'INVALID_PIN', res.body.error.message);
    }
    if (res.status !== 200 && res.status !== 201) {
      throw new AppError(res.status, res.body.error?.code || 'PAYMENT_FAILED', res.body.error?.message || 'Payment failed');
    }

    const result = res.body.data ?? res.body;
    const transactionId = result.transaction_id;
    if (!transactionId || !externalRefId) {
      throw new AppError(502, 'INVALID_SETTLEMENT_RESPONSE', 'Central Bank tidak mengembalikan referensi transaksi lengkap');
    }
    await prisma.paymentRecord.upsert({
      where: { service_id_external_ref_id: { service_id: serviceId, external_ref_id: externalRefId } },
      create: {
        service_id: serviceId,
        external_ref_id: externalRefId,
        central_transaction_id: transactionId,
        buyer_external_id: buyerExternalId,
        gross_amount: grossAmount,
      },
      update: {},
    });

    return res.body;
  }

  static async refundPayment(externalRefId: string, reasonCode: string, serviceId: string, serviceName: string, idempotencyKey: string) {
    const payment = await prisma.paymentRecord.findFirst({
      where: {
        service_id: serviceId,
        OR: [{ external_ref_id: externalRefId }, { central_transaction_id: externalRefId }],
      },
    });
    if (!payment) throw new AppError(404, 'PAYMENT_NOT_FOUND', 'Pembayaran milik layanan tidak ditemukan');
    if (payment.status === 'REVERSED') {
      return {
        original_transaction_id: payment.central_transaction_id,
        reversal_transaction_id: payment.reversal_transaction_id,
        status: 'SETTLED',
        idempotent_replay: true,
      };
    }

    const buyerLink = await LinkageService.getLinkage(payment.buyer_external_id, serviceId);
    const res = await CentralBankClient.reversePayment(
      payment.central_transaction_id,
      reasonCode,
      idempotencyKey,
      buyerLink.smartbank_user_id,
      serviceName
    );
    if (res.status !== 200 && res.status !== 201) {
      throw new AppError(res.status, res.body.error?.code || 'REFUND_FAILED', res.body.error?.message || 'Refund failed');
    }

    const result = res.body.data ?? res.body;
    await prisma.paymentRecord.update({
      where: { id: payment.id },
      data: { status: 'REVERSED', reversal_transaction_id: result.reversal_transaction_id, reversed_at: new Date() },
    });
    return result;
  }
}
