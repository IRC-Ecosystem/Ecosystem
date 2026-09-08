const test = require("node:test");
const assert = require("node:assert/strict");

const injectDb = (modelPath, db) => {
  const dbPath = require.resolve("../config/db");
  const resolvedModel = require.resolve(modelPath);
  require.cache[dbPath] = { id: dbPath, filename: dbPath, loaded: true, exports: db };
  delete require.cache[resolvedModel];
  return require(modelPath);
};

test("payment upsert and replay check use one leased connection", async () => {
  const calls = [];
  let fingerprint;
  const connection = {
    beginTransaction: (callback) => { calls.push("begin"); callback(null); },
    commit: (callback) => { calls.push("commit"); callback(null); },
    rollback: (callback) => { calls.push("rollback"); callback(null); },
    release: () => calls.push("release"),
    query: (sql, values, callback) => {
      calls.push(sql.replace(/\s+/g, " ").trim());
      if (sql.includes("INSERT INTO payments")) {
        fingerprint = values[8];
        return callback(null, { insertId: 7 });
      }
      return callback(null, [{ id: 7, status: "processing", request_fingerprint: fingerprint }]);
    }
  };
  const model = injectDb("../models/paymentModel", {
    getConnection: (callback) => callback(null, connection),
    query: () => assert.fail("mutation must not query through pool")
  });

  await model.create({
    transactionId: 1,
    provider: "smartbank",
    method: "smartbank",
    status: "processing",
    amount: 25000,
    idempotencyKey: "warungpos-INV-1"
  });

  assert.deepEqual(calls.filter((call) => ["begin", "commit", "rollback", "release"].includes(call)), ["begin", "commit", "release"]);
});

test("endpoint activation updates use one leased transaction", async () => {
  const calls = [];
  const connection = {
    beginTransaction: (callback) => { calls.push("begin"); callback(null); },
    commit: (callback) => { calls.push("commit"); callback(null); },
    rollback: (callback) => { calls.push("rollback"); callback(null); },
    release: () => calls.push("release"),
    query: (sql, values, callback) => { calls.push(sql.replace(/\s+/g, " ").trim()); callback(null, { affectedRows: 1 }); }
  };
  const model = injectDb("../models/apiIntegrationModel", {
    getConnection: (callback) => callback(null, connection),
    query: () => assert.fail("mutation must not query through pool")
  });

  await model.setActive({ id: 2, provider: "umkm-insight" });

  assert.equal(calls.filter((call) => call.startsWith?.("UPDATE")).length, 2);
  assert.deepEqual(calls.filter((call) => ["begin", "commit", "rollback", "release"].includes(call)), ["begin", "commit", "release"]);
});
