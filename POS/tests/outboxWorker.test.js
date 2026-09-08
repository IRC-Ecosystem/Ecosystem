const test = require("node:test");
const assert = require("node:assert/strict");

const { computeRetryDelayMs, processOutboxBatch } = require("../services/outboxWorker");

test("outbox retry delay is exponential with bounded jitter", () => {
  assert.equal(computeRetryDelayMs(1, () => 0), 1000);
  assert.equal(computeRetryDelayMs(2, () => 0), 2000);
  assert.equal(computeRetryDelayMs(3, () => 1), 6000);
});

test("outbox worker marks exhausted events dead-letter", async () => {
  const transitions = [];
  const store = {
    claimBatch: async () => [{ id: 7, event_type: "invoice.paid", payload: {}, attempts: 4, max_attempts: 5 }],
    markPublished: async () => assert.fail("must not publish"),
    markFailed: async (id, failure) => transitions.push({ id, ...failure })
  };

  await processOutboxBatch({
    store,
    publishers: { "invoice.paid": async () => { throw new Error("downstream unavailable"); } },
    random: () => 0
  });

  assert.deepEqual(transitions, [{ id: 7, deadLetter: true, nextAttemptAt: null, error: "downstream unavailable" }]);
});

test("reconciliation events use a publisher and retry instead of local success", async () => {
  const transitions = [];
  const store = {
    claimBatch: async () => [{ id: 8, event_type: "payment.reconcile", payload: { paymentId: 2 }, attempts: 0, max_attempts: 5 }],
    markPublished: async (id) => transitions.push({ id, published: true }),
    markFailed: async () => assert.fail("must not fail")
  };

  await processOutboxBatch({
    store,
    publishers: { "payment.reconcile": async (payload) => assert.equal(payload.paymentId, 2) },
    random: () => 0
  });

  assert.deepEqual(transitions, [{ id: 8, published: true }]);
});
