const test = require("node:test");
const assert = require("node:assert/strict");

const loadModelWithDb = (db) => {
  const dbPath = require.resolve("../config/db");
  const modelPath = require.resolve("../models/paymentModel");
  const connection = {
    ...db,
    beginTransaction: (callback) => callback(null),
    commit: (callback) => callback(null),
    rollback: (callback) => callback(null),
    release: () => {}
  };
  const testDb = { ...db, getConnection: (callback) => callback(null, connection) };
  require.cache[dbPath] = { id: dbPath, filename: dbPath, loaded: true, exports: testDb };
  delete require.cache[modelPath];
  return require(modelPath);
};

test("payment create requires an idempotency key", async () => {
  const model = loadModelWithDb({ query: () => assert.fail("query must not run") });

  await assert.rejects(
    () => model.create({ transactionId: 1, provider: "smartbank", method: "smartbank", status: "success", amount: 25000 }),
    (error) => error.code === "IDEMPOTENCY_KEY_REQUIRED"
  );
});

test("payment create rejects local providers explicitly", async () => {
  const model = loadModelWithDb({ query: () => assert.fail("query must not run") });
  await assert.rejects(
    () => model.create({ transactionId: 1, provider: "local", method: "qris", status: "success", amount: 25000, idempotencyKey: "local-1" }),
    (error) => error.code === "CONNECTOR_PAYMENT_REQUIRED"
  );
});

test("payment create upserts one stable request fingerprint", async () => {
  const captured = [];
  const model = loadModelWithDb({
    query: (sql, values, callback) => {
      captured.push({ sql: sql.replace(/\s+/g, " ").trim(), values });
      if (sql.includes("SELECT id, status, request_fingerprint")) {
        return callback(null, [{ id: 7, status: "success", request_fingerprint: captured[0].values[8] }]);
      }
      return callback(null, { insertId: 7, affectedRows: 1 });
    }
  });

  const result = await model.create({
    transactionId: 1,
    provider: "smartbank",
    method: "smartbank",
    status: "success",
    amount: 25000,
    paymentRequestId: "req-1",
    providerReference: "tx-1",
    idempotencyKey: "warungpos-INV-1"
  });

  assert.equal(result.id, 7);
  assert.match(captured[0].sql, /ON DUPLICATE KEY UPDATE/);
  assert.match(captured[0].sql, /request_fingerprint/);
  assert.match(captured[0].sql, /provider_reference_key/);
  assert.equal(captured[0].values[9], "tx-1");
  assert.equal(captured[0].values.includes("warungpos-INV-1"), true);
  assert.equal(captured[0].values.includes("tx-1"), true);
});

test("payment create rejects an idempotency key replayed with another payload", async () => {
  const model = loadModelWithDb({
    query: (sql, values, callback) => {
      if (sql.includes("SELECT id, status, request_fingerprint")) {
        return callback(null, [{ id: 7, status: "success", request_fingerprint: "0".repeat(64) }]);
      }
      return callback(null, { insertId: 7, affectedRows: 2 });
    }
  });

  await assert.rejects(
    () => model.create({
      transactionId: 1,
      provider: "smartbank",
      method: "smartbank",
      status: "processing",
      amount: 25000,
      idempotencyKey: "warungpos-INV-1"
    }),
    (error) => error.code === "IDEMPOTENCY_CONFLICT"
  );
});
