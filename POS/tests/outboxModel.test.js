const test = require("node:test");
const assert = require("node:assert/strict");

const loadModelWithDb = (db) => {
  const dbPath = require.resolve("../config/db");
  const modelPath = require.resolve("../models/outboxModel");
  require.cache[dbPath] = { id: dbPath, filename: dbPath, loaded: true, exports: db };
  delete require.cache[modelPath];
  return require(modelPath);
};

const buildDb = () => {
  const calls = [];
  const connection = {
    beginTransaction: (callback) => { calls.push("begin"); callback(null); },
    commit: (callback) => { calls.push("commit"); callback(null); },
    rollback: (callback) => { calls.push("rollback"); callback(null); },
    release: () => calls.push("release"),
    query: (sql, values, callback) => {
      const normalized = sql.replace(/\s+/g, " ").trim();
      calls.push({ sql: normalized, values });
      if (normalized.startsWith("SELECT")) {
        return callback(null, [{ id: 3, event_type: "invoice.paid", payload: '{"invoice":"INV-1"}', attempts: 0, max_attempts: 5 }]);
      }
      return callback(null, { affectedRows: 1 });
    }
  };
  return {
    calls,
    db: {
      getConnection: (callback) => callback(null, connection),
      query: connection.query
    }
  };
};

test("outbox claim uses a leased transaction and skip-locked rows", async () => {
  const { db, calls } = buildDb();
  const model = loadModelWithDb(db);

  const events = await model.claimBatch(10);

  assert.deepEqual(events[0].payload, { invoice: "INV-1" });
  assert.deepEqual(calls.filter((call) => typeof call === "string"), ["begin", "commit", "release"]);
  const sql = calls.filter((call) => call.sql).map((call) => call.sql).join("\n");
  assert.match(sql, /FOR UPDATE SKIP LOCKED/);
  assert.match(sql, /locked_at < DATE_SUB\(NOW\(\), INTERVAL 5 MINUTE\)/);
  assert.match(sql, /status = 'processing'/);
});

test("outbox failures persist retry scheduling or dead-letter state", async () => {
  const { db, calls } = buildDb();
  const model = loadModelWithDb(db);

  await model.markFailed(3, { deadLetter: false, nextAttemptAt: new Date("2026-08-05T01:00:00Z"), error: "timeout" });
  await model.markFailed(4, { deadLetter: true, nextAttemptAt: null, error: "exhausted" });

  const sql = calls.filter((call) => call.sql).map((call) => call.sql).join("\n");
  assert.match(sql, /status = 'pending'/);
  assert.match(sql, /status = 'dead_letter'/);
  assert.match(sql, /attempts = attempts \+ 1/);
});
