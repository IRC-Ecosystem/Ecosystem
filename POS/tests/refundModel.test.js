const test = require("node:test");
const assert = require("node:assert/strict");

const loadModelWithDb = (db) => {
  const dbPath = require.resolve("../config/db");
  const modelPath = require.resolve("../models/refundModel");
  require.cache[dbPath] = { id: dbPath, filename: dbPath, loaded: true, exports: db };
  delete require.cache[modelPath];
  return require(modelPath);
};

test("refund intent reopens failed retries but preserves successful reversals", async () => {
  const calls = [];
  const model = loadModelWithDb({
    query: (sql, values, callback) => {
      calls.push(sql.replace(/\s+/g, " ").trim());
      if (sql.trim().startsWith("SELECT")) return callback(null, [{ id: 8, status: "processing" }]);
      return callback(null, { insertId: 8, affectedRows: 2 });
    }
  });

  await model.begin({ paymentId: 5, idempotencyKey: "warungpos-refund-INV-1", reasonCode: "CUSTOMER_REQUEST" });

  assert.match(calls[0], /status = IF\(status = 'failed', 'processing', status\)/);
  assert.match(calls[0], /reason_code = IF\(status = 'failed'/);
});

test("successful refund marks payment and transaction refunded atomically", async () => {
  let sql;
  const model = loadModelWithDb({
    query: (statement, values, callback) => {
      sql = statement.replace(/\s+/g, " ").trim();
      callback(null, { affectedRows: 1 });
    }
  });

  await model.succeed({ refundId: 8, providerReference: "rev-1", responseBody: "{}" });

  assert.match(sql, /INNER JOIN transactions t ON t.id = p.transaction_id/);
  assert.match(sql, /p.status = 'refunded'/);
  assert.match(sql, /t.status = 'refunded'/);
});
