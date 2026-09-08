const test = require("node:test");
const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");

const root = path.join(__dirname, "..");
const read = (file) => fs.readFileSync(path.join(root, file), "utf8");

test("production Docker starts the app without running local setup or demo seed", () => {
  const dockerfile = read("Dockerfile");
  const command = dockerfile.match(/^CMD\s+(.+)$/m)?.[1] || "";

  assert.match(command, /database\/migrate\.js/);
  assert.match(command, /node app\.js/);
  assert.doesNotMatch(command, /setup_local|seed|demo/i);
});

test("public requests cannot bypass rate limiting with a header", () => {
  const appSource = read("app.js");

  assert.doesNotMatch(appSource, /x-warungpos-health-check/i);
  assert.doesNotMatch(appSource, /skip\s*:/);
});

test("payment persistence migration adds idempotency, outbox, refund, and reconciliation constraints", () => {
  const migration = read("database/migrate_to_current_schema.sql");

  assert.match(migration, /idempotency_key/i);
  assert.match(migration, /UNIQUE KEY `uq_payments_idempotency`/i);
  assert.match(migration, /UNIQUE KEY `uq_payments_provider_reference`/i);
  assert.match(migration, /CREATE TABLE IF NOT EXISTS `outbox_events`/i);
  assert.match(migration, /CREATE TABLE IF NOT EXISTS `refunds`/i);
  assert.match(migration, /CREATE TABLE IF NOT EXISTS `payment_reconciliation`/i);
  assert.match(migration, /'refunded'/i);
});

test("transaction mutations lease a pool connection and use conditional inventory updates", () => {
  const dbSource = read("config/db.js");
  const transactionSource = read("models/transactionModel.js");

  assert.match(dbSource, /createPool/);
  assert.match(transactionSource, /getConnection/);
  assert.match(transactionSource, /finally\s*\{[\s\S]*release\(\)/);
  assert.match(transactionSource, /on_hand\s*-\s*reserved\s*>=\s*\?/i);
  assert.match(transactionSource, /inventory_reservations/i);
  assert.match(transactionSource, /FOR UPDATE/i);
});
