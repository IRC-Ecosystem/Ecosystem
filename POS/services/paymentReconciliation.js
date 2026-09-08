const reconcilePaymentRecord = (record) => {
  let connector = null;
  try {
    connector = JSON.parse(record.responseBody || "null");
  } catch (error) {
    connector = null;
  }

  const providerStatus = connector?.status || "UNKNOWN";
  const issues = [];
  if (providerStatus !== "SETTLED" || !connector?.transaction_id) issues.push("provider_not_settled");
  if (Number(record.paymentAmount) !== Number(record.transactionAmount)) issues.push("amount_mismatch");
  if (record.transactionStatus === "paid" && Number(record.stockDeducted) !== 1) issues.push("stock_not_deducted");
  if (record.paymentStatus !== "success" || record.transactionStatus !== "paid") issues.push("local_status_mismatch");

  return {
    status: issues.length ? "mismatch" : "matched",
    providerStatus,
    issues
  };
};

module.exports = { reconcilePaymentRecord };
