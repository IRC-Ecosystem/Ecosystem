import crypto from 'crypto';

export class InsightEventService {
  static async publishPaymentSettled(input: {
    serviceName: string;
    buyerExternalId: string;
    sellerExternalId: string;
    grossAmount: string;
    externalRefId?: string;
    result: any;
  }) {
    const url = process.env.UMKM_INSIGHT_EVENTS_URL;
    const apiKey = process.env.UMKM_INSIGHT_SMARTBANK_KEY;
    const secret = process.env.UMKM_INSIGHT_EVENT_HMAC_SECRET;
    if (!url || !apiKey || !secret) return;

    const payload = JSON.stringify({
      source: 'SMARTBANK',
      event_id: `smartbank-${input.result?.data?.transaction_id ?? input.result?.transaction_id ?? input.externalRefId}`,
      event_type: 'payment.settled',
      analytics_subject_id: input.sellerExternalId,
      transaction_id: input.result?.data?.transaction_id ?? input.result?.transaction_id,
      external_ref_id: input.externalRefId,
      amount: input.grossAmount,
      description: `Settlement ${input.serviceName}`,
      occurred_at: new Date().toISOString(),
    });
    const signature = crypto.createHmac('sha256', secret).update(payload).digest('hex');
    try {
      await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-API-Key': apiKey, 'X-Event-Source': 'SMARTBANK', 'X-Event-Id': JSON.parse(payload).event_id, 'X-Event-Signature': signature }, body: payload, signal: AbortSignal.timeout(3000) });
    } catch {
      // ponytail: delivery is best-effort until a persistent outbox worker is added.
    }
  }
}
