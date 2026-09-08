const crypto = require("node:crypto");

const policyError = (code, message) => Object.assign(new Error(message), { code });

const buildPaymentFingerprint = ({ transactionId, provider, method, amount }) => crypto
  .createHash("sha256")
  .update(JSON.stringify({ transactionId, provider, method, amount }))
  .digest("hex");

const validateMoney = (amount) => {
  if (typeof amount !== "number" || !Number.isSafeInteger(amount) || amount <= 0) {
    throw policyError("INVALID_AMOUNT", "Nominal harus berupa bilangan bulat positif.");
  }

  return amount;
};

const requireConnectorPaymentMethod = (method) => {
  if (method !== "smartbank") {
    throw policyError("CONNECTOR_PAYMENT_REQUIRED", "Metode pembayaran ini belum didukung SmartBank Connector dan tidak boleh diselesaikan secara lokal.");
  }

  return method;
};

module.exports = {
  buildPaymentFingerprint,
  requireConnectorPaymentMethod,
  validateMoney
};
