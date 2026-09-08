const test = require("node:test");
const assert = require("node:assert/strict");

const { reconcilePaymentRecord } = require("../services/paymentReconciliation");

test("reconciliation matches local paid state to stored Connector settlement", () => {
  const result = reconcilePaymentRecord({
    invoice: "INV-1",
    transactionStatus: "paid",
    stockDeducted: 1,
    paymentStatus: "success",
    paymentAmount: 25000,
    transactionAmount: 25000,
    responseBody: JSON.stringify({ status: "SETTLED", transaction_id: "tx-1" })
  });

  assert.deepEqual(result, { status: "matched", providerStatus: "SETTLED", issues: [] });
});

test("reconciliation records mismatches instead of inventing provider success", () => {
  const result = reconcilePaymentRecord({
    invoice: "INV-2",
    transactionStatus: "paid",
    stockDeducted: 0,
    paymentStatus: "success",
    paymentAmount: 24999,
    transactionAmount: 25000,
    responseBody: "not-json"
  });

  assert.equal(result.status, "mismatch");
  assert.equal(result.providerStatus, "UNKNOWN");
  assert.deepEqual(result.issues.sort(), ["amount_mismatch", "provider_not_settled", "stock_not_deducted"]);
});
