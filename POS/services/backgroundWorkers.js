const OutboxModel = require("../models/outboxModel");
const { createPublishers } = require("./outboxPublishers");
const { processOutboxBatch } = require("./outboxWorker");
const { reconcilePayments } = require("./reconciliationWorker");
const TransactionModel = require("../models/transactionModel");

const startBackgroundWorkers = () => {
  const intervalMs = Math.max(Number(process.env.OUTBOX_POLL_INTERVAL_MS) || 5000, 1000);
  const publishers = createPublishers();
  let running = false;
  let cycles = 0;

  const tick = async () => {
    if (running) return;
    running = true;
    try {
      await processOutboxBatch({ store: OutboxModel, publishers });
      cycles += 1;
      if (cycles % 12 === 0) await TransactionModel.expireReservations();
      if (cycles % 12 === 0) await reconcilePayments();
    } catch (error) {
      console.error("Background payment worker error:", error.message);
    } finally {
      running = false;
    }
  };

  const timer = setInterval(tick, intervalMs);
  timer.unref();
  void tick();
  return () => clearInterval(timer);
};

module.exports = { startBackgroundWorkers };
