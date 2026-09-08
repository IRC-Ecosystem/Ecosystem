const test = require("node:test");
const assert = require("node:assert/strict");
const dns = require("node:dns");

process.env.SMARTBANK_CONNECTOR_URL = "https://connector.example.test";
process.env.SMARTBANK_CONNECTOR_API_KEY = "sbk_test_key_abcdefghijklmnopqrstuvwxyz";
process.env.SMARTBANK_POS_SELLER_EXTERNAL_ID = "pos-user-merchant";
process.env.API_INTEGRATION_ALLOWED_HOSTS = "public.example";

const connector = require("../services/smartBankConnectorClient");
const apiClient = require("../services/apiIntegrationClient");

test("SmartBank rejects zero, negative, fractional, and numeric-string amounts before fetch", async () => {
  global.fetch = async () => assert.fail("fetch must not be called");

  for (const amount of [0, -1, 10.5, "100"]) {
    await assert.rejects(
      () => connector.pay({ buyerUserId: 42, amount, pin: "123456", invoice: "INV-AMOUNT" }),
      (error) => error.code === "INVALID_AMOUNT"
    );
  }
});

test("SmartBank rejects non-settled successful envelopes", async () => {
  global.fetch = async () => ({
    ok: true,
    status: 200,
    json: async () => ({ success: true, data: { status: "PENDING", transaction_id: "tx-1" } })
  });

  await assert.rejects(
    () => connector.pay({ buyerUserId: 42, amount: 25000, pin: "123456", invoice: "INV-PENDING" }),
    (error) => error.code === "INVALID_SETTLEMENT_RESPONSE"
  );
});

test("SmartBank requires explicit success, settled status, and provider transaction reference", async () => {
  const payloads = [
    {},
    { data: { status: "SETTLED", transaction_id: "tx-1" } },
    { success: true, data: { status: "SETTLED" } }
  ];

  for (const payload of payloads) {
    global.fetch = async () => ({ ok: true, status: 200, json: async () => payload });
    await assert.rejects(
      () => connector.pay({ buyerUserId: 42, amount: 25000, pin: "123456", invoice: `INV-${payloads.indexOf(payload)}` }),
      (error) => error.code === "INVALID_SETTLEMENT_RESPONSE"
    );
  }
});

test("refund uses the existing Connector reversal contract and stable idempotency", async () => {
  let request;
  global.fetch = async (url, options) => {
    request = { url, options };
    return {
      ok: true,
      status: 200,
      json: async () => ({ success: true, data: { status: "SETTLED", reversal_transaction_id: "rev-1" } })
    };
  };

  const result = await connector.refund({ invoice: "INV-001", reasonCode: "CUSTOMER_REQUEST" });

  assert.equal(result.reversal_transaction_id, "rev-1");
  assert.equal(request.url, "https://connector.example.test/v1/connect/payment-requests/refunds");
  assert.equal(request.options.headers["Idempotency-Key"], "warungpos-refund-INV-001");
  assert.deepEqual(JSON.parse(request.options.body), {
    external_ref_id: "INV-001",
    reason_code: "CUSTOMER_REQUEST"
  });
});

test("external integration rejects loopback, private, and non-http destinations", async (t) => {
  t.mock.method(dns.promises, "lookup", async (hostname) => ({
    address: hostname === "public.example" ? "93.184.216.34" : "127.0.0.1",
    family: 4
  }));

  for (const baseUrl of ["http://localhost:8080", "http://127.0.0.1", "http://[::ffff:127.0.0.1]", "file:///etc/passwd", "http://private.example"]) {
    await assert.rejects(
      () => apiClient.assertSafeIntegrationUrl(`${baseUrl}/health`),
      (error) => error.code === "UNSAFE_INTEGRATION_URL"
    );
  }

  await assert.doesNotReject(() => apiClient.assertSafeIntegrationUrl("https://public.example/health"));
});

test("external integration disables redirects to prevent allowlist escape", async (t) => {
  t.mock.method(dns.promises, "lookup", async () => [{ address: "93.184.216.34", family: 4 }]);
  let requestOptions;
  global.fetch = async (url, options) => {
    requestOptions = options;
    return { status: 200, text: async () => "ok" };
  };
  await apiClient.callExternalIntegration({
    base_url: "https://public.example",
    path: "/health",
    method: "GET",
    expected_status: 200
  });
  assert.equal(requestOptions.redirect, "error");
});
