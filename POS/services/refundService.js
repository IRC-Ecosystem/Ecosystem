const processRefund = async ({ payment, reasonCode, store, connector }) => {
  if (payment?.provider !== "smartbank") {
    throw Object.assign(new Error("Refund lokal tidak didukung; reversal wajib melalui SmartBank Connector."), {
      code: "CONNECTOR_REFUND_REQUIRED"
    });
  }
  if (!["success", "refunded"].includes(payment.status)) {
    throw Object.assign(new Error("Hanya payment sukses yang dapat direversal."), { code: "REFUND_NOT_ALLOWED" });
  }
  if (!/^[A-Z0-9_]{3,128}$/.test(reasonCode || "")) {
    throw Object.assign(new Error("reason_code refund tidak valid."), { code: "INVALID_REFUND_REASON" });
  }

  const idempotencyKey = `warungpos-refund-${payment.invoice}`;
  const intent = await store.begin({ paymentId: payment.id, idempotencyKey, reasonCode });
  if (intent.status === "success") return intent;

  try {
    const result = await connector.refund({ invoice: payment.invoice, reasonCode });
    await store.succeed({ refundId: intent.id, providerReference: result.reversal_transaction_id, responseBody: JSON.stringify(result) });
    return result;
  } catch (error) {
    await store.fail({ refundId: intent.id, error: error.message });
    throw error;
  }
};

module.exports = { processRefund };
