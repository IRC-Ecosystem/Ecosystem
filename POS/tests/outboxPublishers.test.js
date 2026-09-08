const test = require("node:test");
const assert = require("node:assert/strict");
const crypto = require("node:crypto");

const { createPublishers } = require("../services/outboxPublishers");

test("invoice and UMKM publishers send idempotent signed events", async () => {
  process.env.POS_INVOICE_PAID_WEBHOOK_URL = "https://invoice.example/events";
  process.env.UMKM_INSIGHT_EVENTS_URL = "https://insight.example/events";
  process.env.UMKM_INSIGHT_WARUNGPOS_KEY = "pos-key";
  process.env.UMKM_INSIGHT_EVENT_HMAC_SECRET = "secret";
  const requests = [];
  const publishers = createPublishers({
    assertSafeUrl: async () => {},
    fetchImpl: async (url, options) => {
      requests.push({ url, options });
      return { ok: true, status: 202 };
    }
  });
  const payload = { invoice: "INV-1", amount: 25000 };

  await publishers["invoice.paid"](payload);
  await publishers["umkm.insight.invoice_paid"](payload);

  assert.equal(requests.length, 2);
  assert.ok(requests[0].options.signal instanceof AbortSignal);
  assert.ok(requests[1].options.signal instanceof AbortSignal);
  assert.equal(requests[0].options.headers["Idempotency-Key"], "invoice.paid:INV-1");
  const insightBody = JSON.parse(requests[1].options.body);
  assert.equal(insightBody.event_type, "pos.invoice.paid");
  assert.equal(requests[1].options.headers["X-API-Key"], "pos-key");
  assert.equal(requests[1].options.headers["X-Event-Source"], "WARUNGPOS");
  assert.equal(requests[1].options.headers["X-Event-Id"], "warungpos-invoice-paid-INV-1");
  const expectedSignature = crypto.createHmac("sha256", "secret").update(requests[1].options.body).digest("hex");
  assert.equal(requests[1].options.headers["X-Event-Signature"], expectedSignature);
});

test("publisher fails explicitly when a required destination is not configured", async () => {
  delete process.env.POS_INVOICE_PAID_WEBHOOK_URL;
  const publishers = createPublishers({ assertSafeUrl: async () => {}, fetchImpl: async () => assert.fail("fetch must not run") });

  await assert.rejects(() => publishers["invoice.paid"]({ invoice: "INV-2" }), /not configured/i);
});
