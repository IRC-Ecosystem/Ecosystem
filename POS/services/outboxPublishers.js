const crypto = require("node:crypto");
const { assertSafeIntegrationUrl } = require("./apiIntegrationClient");

const requireConfig = (name) => {
  const value = process.env[name];
  if (!value) throw new Error(`${name} not configured`);
  return value;
};

const postJson = async ({ fetchImpl, assertSafeUrl, url, payload, headers }) => {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), Number(process.env.OUTBOX_PUBLISHER_TIMEOUT_MS || 10000));
  try {
    await assertSafeUrl(url);
    const response = await fetchImpl(url, {
      method: "POST",
      headers: { "Content-Type": "application/json", ...headers },
      signal: controller.signal,
      body: JSON.stringify(payload)
    });
    if (!response.ok) throw new Error(`Outbox destination returned HTTP ${response.status}`);
  } finally {
    clearTimeout(timeout);
  }
};

const createPublishers = ({ fetchImpl = fetch, assertSafeUrl = assertSafeIntegrationUrl } = {}) => ({
  "invoice.paid": async (payload) => postJson({
    fetchImpl,
    assertSafeUrl,
    url: requireConfig("POS_INVOICE_PAID_WEBHOOK_URL"),
    payload,
    headers: { "Idempotency-Key": `invoice.paid:${payload.invoice}` }
  }),
  "umkm.insight.invoice_paid": async (payload) => {
    const secret = requireConfig("UMKM_INSIGHT_EVENT_HMAC_SECRET");
    const eventId = `warungpos-invoice-paid-${payload.invoice}`;
    const event = {
      event_type: "pos.invoice.paid",
      source: "WARUNGPOS",
      event_id: eventId,
      occurred_at: new Date().toISOString(),
      ...payload
    };
    const body = JSON.stringify(event);
    return postJson({
      fetchImpl,
      assertSafeUrl,
      url: requireConfig("UMKM_INSIGHT_EVENTS_URL"),
      payload: event,
      headers: {
        "X-API-Key": requireConfig("UMKM_INSIGHT_WARUNGPOS_KEY"),
        "X-Event-Source": "WARUNGPOS",
        "X-Event-Id": eventId,
        "X-Event-Signature": crypto.createHmac("sha256", secret).update(body).digest("hex")
      }
    });
  },
  "payment.reconcile": async (payload) => {
    if (!payload?.paymentId) throw new Error("paymentId reconciliation missing");
  }
});

module.exports = { createPublishers };
