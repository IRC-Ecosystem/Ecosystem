const test = require("node:test");
const assert = require("node:assert/strict");

const { processRefund } = require("../services/refundService");

const payment = { id: 5, provider: "smartbank", status: "success", invoice: "INV-1" };

test("refund persists an idempotent intent then calls Connector reversal", async () => {
  const calls = [];
  const result = await processRefund({
    payment,
    reasonCode: "CUSTOMER_REQUEST",
    store: {
      begin: async (input) => { calls.push(["begin", input]); return { id: 8, status: "processing" }; },
      succeed: async (input) => calls.push(["succeed", input]),
      fail: async () => assert.fail("must not fail")
    },
    connector: {
      refund: async (input) => {
        calls.push(["connector", input]);
        return { status: "SETTLED", reversal_transaction_id: "rev-1" };
      }
    }
  });

  assert.equal(result.status, "SETTLED");
  assert.deepEqual(calls.map(([name]) => name), ["begin", "connector", "succeed"]);
  assert.equal(calls[0][1].idempotencyKey, "warungpos-refund-INV-1");
});

test("refund replay returns the stored successful reversal without another Connector call", async () => {
  const result = await processRefund({
    payment: { ...payment, status: "refunded" },
    reasonCode: "CUSTOMER_REQUEST",
    store: {
      begin: async () => ({ id: 8, status: "success", provider_reference: "rev-1" })
    },
    connector: { refund: async () => assert.fail("Connector must not be called") }
  });

  assert.equal(result.provider_reference, "rev-1");
});

test("refund rejects local payment providers explicitly", async () => {
  await assert.rejects(
    () => processRefund({ payment: { ...payment, provider: "local" }, reasonCode: "CUSTOMER_REQUEST", store: {}, connector: {} }),
    (error) => error.code === "CONNECTOR_REFUND_REQUIRED"
  );
});

test("refund failure remains failed and is never reported as local success", async () => {
  let failed;
  await assert.rejects(
    () => processRefund({
      payment,
      reasonCode: "CUSTOMER_REQUEST",
      store: {
        begin: async () => ({ id: 9, status: "processing" }),
        succeed: async () => assert.fail("must not succeed"),
        fail: async (input) => { failed = input; }
      },
      connector: { refund: async () => { throw new Error("connector unavailable"); } }
    }),
    /connector unavailable/
  );
  assert.equal(failed.refundId, 9);
  assert.match(failed.error, /connector unavailable/);
});
