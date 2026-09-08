const crypto = require("crypto");

class SmartBankConnectorError extends Error {
  constructor(status, code, message, details = {}) {
    super(message);
    this.name = "SmartBankConnectorError";
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

const baseUrl = () => (process.env.SMARTBANK_CONNECTOR_URL || "http://localhost:5000").replace(/\/$/, "");
const apiKey = () => process.env.SMARTBANK_CONNECTOR_API_KEY || "";
const externalUserId = (userId) => `pos-user-${userId}`;

const callConnector = async (path, { method = "GET", body, idempotencyKey } = {}) => {
  if (!apiKey()) {
    throw new SmartBankConnectorError(503, "CONNECTOR_NOT_CONFIGURED", "API key SmartBank Connector belum dikonfigurasi di POS.");
  }

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), Number(process.env.SMARTBANK_CONNECTOR_TIMEOUT_MS || 10000));
  try {
    const response = await fetch(`${baseUrl()}${path}`, {
      method,
      signal: controller.signal,
      headers: {
        Authorization: `Bearer ${apiKey()}`,
        "Content-Type": "application/json",
        "X-Request-Id": `pos-${crypto.randomUUID()}`,
        ...(method !== "GET" ? { "Idempotency-Key": idempotencyKey || crypto.randomUUID() } : {})
      },
      ...(body === undefined ? {} : { body: JSON.stringify(body) })
    });
    const payload = await response.json().catch(() => ({}));
    if (!response.ok || payload.success !== true) {
      throw new SmartBankConnectorError(
        response.status,
        payload.error?.code || (response.ok ? "INVALID_SETTLEMENT_RESPONSE" : "CONNECTOR_REQUEST_FAILED"),
        payload.error?.message || (response.ok ? "SmartBank Connector mengembalikan envelope yang tidak valid." : `SmartBank Connector merespons HTTP ${response.status}.`),
        payload.error?.details
      );
    }
    return payload.data ?? payload;
  } catch (error) {
    if (error instanceof SmartBankConnectorError) throw error;
    const message = error.name === "AbortError"
      ? "SmartBank Connector tidak merespons dalam batas waktu."
      : "SmartBank Connector tidak dapat dijangkau.";
    throw new SmartBankConnectorError(502, "CONNECTOR_UNAVAILABLE", message);
  } finally {
    clearTimeout(timeout);
  }
};

const requestLinkOtp = (phone, userId) => callConnector("/v1/connect/users/otp/request", {
  method: "POST",
  idempotencyKey: `pos-link-otp-${userId}-${Date.now()}`,
  body: { phone, purpose: "WALLET_LINK" }
});

const verifyLinkOtp = (requestId, code, userId) => callConnector("/v1/connect/users/otp/verify", {
  method: "POST",
  idempotencyKey: `pos-verify-otp-${userId}-${requestId}-${code}`,
  body: { request_id: requestId, code }
});

const linkUser = (userId, verificationToken) => callConnector("/v1/connect/users/link", {
  method: "POST",
  idempotencyKey: `pos-link-user-${userId}`,
  body: { external_user_id: externalUserId(userId), verification_token: verificationToken }
});

const linkExternalUser = (externalId, verificationToken) => callConnector("/v1/connect/users/link", {
  method: "POST",
  idempotencyKey: `pos-link-external-${externalId}`,
  body: { external_user_id: externalId, verification_token: verificationToken }
});

const getLinkage = (userId) => callConnector(`/v1/connect/users/${encodeURIComponent(externalUserId(userId))}`);
const getLinkageByExternalId = (externalId) => callConnector(`/v1/connect/users/${encodeURIComponent(externalId)}`);

const requirePositiveIntegerAmount = (amount) => {
  if (typeof amount !== "number" || !Number.isSafeInteger(amount) || amount <= 0) {
    throw new SmartBankConnectorError(400, "INVALID_AMOUNT", "Nominal pembayaran harus bilangan bulat positif.");
  }

  return amount;
};

const requireSettledResult = (result, requiredReference) => {
  if (result?.status !== "SETTLED" || !result?.[requiredReference]) {
    throw new SmartBankConnectorError(502, "INVALID_SETTLEMENT_RESPONSE", "SmartBank Connector tidak mengembalikan settlement terminal yang valid.");
  }

  return result;
};

const pay = async ({ buyerUserId, amount, pin, invoice }) => {
  const sellerExternalId = process.env.SMARTBANK_POS_SELLER_EXTERNAL_ID;
  if (!sellerExternalId) {
    throw new SmartBankConnectorError(503, "SELLER_NOT_CONFIGURED", "SMARTBANK_POS_SELLER_EXTERNAL_ID belum dikonfigurasi.");
  }
  const validAmount = requirePositiveIntegerAmount(amount);
  const result = await callConnector("/v1/connect/payment-requests", {
    method: "POST",
    idempotencyKey: `warungpos-${invoice}`,
    body: {
      buyer_external_id: externalUserId(buyerUserId),
      seller_external_id: sellerExternalId,
      gross_amount: String(validAmount),
      pin,
      description: `Pembayaran WarungPOS ${invoice}`,
      external_ref_id: invoice
    }
  });

  return requireSettledResult(result, "transaction_id");
};

const refund = async ({ invoice, reasonCode }) => {
  const result = await callConnector("/v1/connect/payment-requests/refunds", {
    method: "POST",
    idempotencyKey: `warungpos-refund-${invoice}`,
    body: {
      external_ref_id: invoice,
      reason_code: reasonCode
    }
  });

  return requireSettledResult(result, "reversal_transaction_id");
};

module.exports = {
  SmartBankConnectorError,
  externalUserId,
  requestLinkOtp,
  verifyLinkOtp,
  linkUser,
  linkExternalUser,
  getLinkage,
  getLinkageByExternalId,
  pay,
  refund
};
