const test = require("node:test");
const assert = require("node:assert/strict");

const loadModelWithDb = (db) => {
  const dbPath = require.resolve("../config/db");
  const modelPath = require.resolve("../models/transactionModel");
  require.cache[dbPath] = { id: dbPath, filename: dbPath, loaded: true, exports: db };
  delete require.cache[modelPath];
  return require(modelPath);
};

const buildDb = ({ stockAffectedRows = 1 } = {}) => {
  const calls = [];
  const connection = {
    beginTransaction: (callback) => { calls.push("begin"); callback(null); },
    commit: (callback) => { calls.push("commit"); callback(null); },
    rollback: (callback) => { calls.push("rollback"); callback(null); },
    release: () => calls.push("release"),
    query: (sql, values, callback) => {
      const normalized = sql.replace(/\s+/g, " ").trim();
      calls.push({ sql: normalized, values });
      if (normalized.includes("FROM transactions") && normalized.includes("FOR UPDATE")) {
        return callback(null, [{ id: 1, invoice: "INV-1", status: "approved", stock_deducted: 0, grand_total: 25000 }]);
      }
      if (normalized.includes("FROM payments")) {
        const paymentInsert = [...calls].reverse().find((call) => call.sql?.startsWith("INSERT INTO payments"));
        return callback(null, [{ id: 9, amount: 25000, request_fingerprint: paymentInsert?.values[5] }]);
      }
      if (normalized.includes("FROM inventory_reservations")) {
        return callback(null, [{ id: 11, product_id: 4, qty: 2 }]);
      }
      if (normalized.includes("FROM transaction_items")) {
        return callback(null, [{ product_id: 4, qty: 2 }]);
      }
      if (normalized.startsWith("UPDATE products")) {
        return callback(null, { affectedRows: stockAffectedRows });
      }
      return callback(null, { affectedRows: 1, insertId: 1 });
    }
  };
  const db = {
    getConnection: (callback) => callback(null, connection),
    query: () => assert.fail("mutation must not query through the pool")
  };
  return { db, calls };
};

test("transaction creation rejects fractional money before leasing a connection", async () => {
  const model = loadModelWithDb({
    getConnection: () => assert.fail("connection must not be leased"),
    query: () => assert.fail("query must not run")
  });
  await assert.rejects(
    () => model.createTransactionWithItems({
      transaction: { invoice: "INV-FRACTION", subtotal: 100.5, fee: 1, grand_total: 101.5, status: "pending" },
      items: [{ product_id: 1, qty: 1, price: 100.5, subtotal: 100.5 }]
    }),
    (error) => error.code === "INVALID_AMOUNT"
  );
});

test("payment finalization locks rows, decrements stock conditionally, and writes outbox atomically", async () => {
  const { db, calls } = buildDb();
  const model = loadModelWithDb(db);

  const result = await model.payTransaction({ transactionId: 1, cashierId: 3, paymentMethod: "smartbank" });

  assert.deepEqual(result, { success: true });
  assert.deepEqual(calls.filter((call) => typeof call === "string"), ["begin", "commit", "release"]);
  const sql = calls.filter((call) => call.sql).map((call) => call.sql).join("\n");
  assert.match(sql, /FROM transactions[\s\S]*FOR UPDATE/);
  assert.match(sql, /FROM payments[\s\S]*FOR UPDATE/);
  assert.match(sql, /UPDATE products SET on_hand = on_hand - \?, reserved = reserved - \?/);
  assert.match(sql, /UPDATE inventory_reservations SET status = 'consumed'/);
  assert.match(sql, /INSERT INTO outbox_events/);
  assert.match(sql, /invoice\.paid/);
  assert.match(sql, /umkm\.insight\.invoice_paid/);
});

test("settled Connector response is persisted in the same transaction as stock and outbox", async () => {
  const { db, calls } = buildDb();
  const model = loadModelWithDb(db);

  const result = await model.settleSmartBankPayment({
    transactionId: 1,
    cashierId: null,
    payment: {
      amount: 25000,
      paymentRequestId: "request-1",
      providerReference: "tx-1",
      responseBody: "{}",
      idempotencyKey: "warungpos-INV-1"
    }
  });

  assert.deepEqual(result, { success: true });
  const sql = calls.filter((call) => call.sql).map((call) => call.sql).join("\n");
  assert.match(sql, /INSERT INTO payments/);
  assert.match(sql, /ON DUPLICATE KEY UPDATE/);
  assert.ok(sql.indexOf("INSERT INTO payments") < sql.indexOf("UPDATE products"));
  assert.deepEqual(calls.filter((call) => typeof call === "string"), ["begin", "commit", "release"]);
});

test("failed conditional inventory settlement rolls back and releases the connection", async () => {
  const { db, calls } = buildDb({ stockAffectedRows: 0 });
  const model = loadModelWithDb(db);

  const result = await model.payTransaction({ transactionId: 1, cashierId: 3, paymentMethod: "smartbank" });

  assert.equal(result.success, false);
  assert.equal(result.reason, "insufficient_stock");
  assert.deepEqual(calls.filter((call) => typeof call === "string"), ["begin", "rollback", "release"]);
});
