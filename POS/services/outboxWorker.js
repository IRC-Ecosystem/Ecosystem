const BASE_RETRY_MS = 1000;
const MAX_RETRY_MS = 60 * 60 * 1000;

const computeRetryDelayMs = (attempt, random = Math.random) => {
  const base = Math.min(BASE_RETRY_MS * (2 ** Math.max(attempt - 1, 0)), MAX_RETRY_MS);
  return Math.round(base + (base * 0.5 * random()));
};

const processOutboxBatch = async ({ store, publishers, random = Math.random, now = () => Date.now() }) => {
  const events = await store.claimBatch();

  for (const event of events) {
    try {
      const publish = publishers[event.event_type];
      if (!publish) throw new Error(`No publisher for ${event.event_type}`);
      await publish(event.payload);
      await store.markPublished(event.id);
    } catch (error) {
      const nextAttempts = Number(event.attempts || 0) + 1;
      const deadLetter = nextAttempts >= Number(event.max_attempts || 5);
      await store.markFailed(event.id, {
        deadLetter,
        nextAttemptAt: deadLetter ? null : new Date(now() + computeRetryDelayMs(nextAttempts, random)),
        error: error.message
      });
    }
  }

  return events.length;
};

module.exports = {
  computeRetryDelayMs,
  processOutboxBatch
};
