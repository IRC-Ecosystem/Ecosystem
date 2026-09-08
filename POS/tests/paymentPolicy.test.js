const test = require("node:test");
const assert = require("node:assert/strict");

const { buildPaymentFingerprint, validateMoney, requireConnectorPaymentMethod } = require("../services/paymentPolicy");

test("money accepts positive safe integers only", () => {
  assert.equal(validateMoney(1), 1);
  assert.equal(validateMoney(25000), 25000);

  for (const amount of [0, -1, 1.5, "25000", Number.MAX_SAFE_INTEGER + 1]) {
    assert.throws(() => validateMoney(amount), (error) => error.code === "INVALID_AMOUNT");
  }
});

test("payment fingerprint is stable and payload-sensitive", () => {
  const first = buildPaymentFingerprint({ transactionId: 1, provider: "smartbank", method: "smartbank", amount: 25000 });
  const replay = buildPaymentFingerprint({ transactionId: 1, provider: "smartbank", method: "smartbank", amount: 25000 });
  const conflict = buildPaymentFingerprint({ transactionId: 1, provider: "smartbank", method: "smartbank", amount: 26000 });
  assert.equal(first, replay);
  assert.notEqual(first, conflict);
  assert.match(first, /^[a-f0-9]{64}$/);
});

test("QRIS, transfer, and cash cannot report local payment success", () => {
  assert.equal(requireConnectorPaymentMethod("smartbank"), "smartbank");

  for (const method of ["qris", "transfer", "cash", "local", ""]) {
    assert.throws(
      () => requireConnectorPaymentMethod(method),
      (error) => error.code === "CONNECTOR_PAYMENT_REQUIRED"
    );
  }
});
