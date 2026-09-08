const db = require("../config/db");
const { reconcilePaymentRecord } = require("./paymentReconciliation");

const query = (sql, values = []) => new Promise((resolve, reject) => {
  db.query(sql, values, (error, results) => error ? reject(error) : resolve(results));
});

const reconcilePayments = async (limit = 100) => {
  const rows = await query(
    `
      SELECT
        p.id AS payment_id,
        p.status AS payment_status,
        p.amount AS payment_amount,
        p.provider_reference,
        p.response_body,
        t.invoice,
        t.status AS transaction_status,
        t.stock_deducted,
        t.grand_total AS transaction_amount
      FROM payments p
      INNER JOIN transactions t ON t.id = p.transaction_id
      WHERE p.provider = 'smartbank'
      ORDER BY p.id DESC
      LIMIT ?
    `,
    [Math.max(1, Math.min(Number(limit) || 100, 500))]
  );

  for (const row of rows) {
    const result = reconcilePaymentRecord({
      invoice: row.invoice,
      transactionStatus: row.transaction_status,
      stockDeducted: row.stock_deducted,
      paymentStatus: row.payment_status,
      paymentAmount: row.payment_amount,
      transactionAmount: row.transaction_amount,
      responseBody: row.response_body
    });
    await query(
      `
        INSERT INTO payment_reconciliation
          (payment_id, invoice, provider_reference, provider_status, local_status, status, details, checked_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
      `,
      [row.payment_id, row.invoice, row.provider_reference, result.providerStatus, row.payment_status, result.status, JSON.stringify({ issues: result.issues })]
    );
  }

  return rows.length;
};

module.exports = { reconcilePayments };
