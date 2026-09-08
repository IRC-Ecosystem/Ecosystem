const db = require("../config/db");

const query = (sql, values = []) => new Promise((resolve, reject) => {
  db.query(sql, values, (error, results) => error ? reject(error) : resolve(results));
});

const getById = async (id) => {
  const rows = await query("SELECT id, status, provider_reference FROM refunds WHERE id = ? LIMIT 1", [id]);
  return rows[0] || null;
};

exports.begin = async ({ paymentId, idempotencyKey, reasonCode }) => {
  const result = await query(
    `
      INSERT INTO refunds (payment_id, idempotency_key, reason_code, status)
      VALUES (?, ?, ?, 'processing')
      ON DUPLICATE KEY UPDATE
        id = LAST_INSERT_ID(id),
        reason_code = IF(status = 'failed', VALUES(reason_code), reason_code),
        status = IF(status = 'failed', 'processing', status),
        response_body = IF(status = 'failed', NULL, response_body)
    `,
    [paymentId, idempotencyKey, reasonCode]
  );
  return getById(result.insertId);
};

exports.succeed = async ({ refundId, providerReference, responseBody }) => query(
  `
    UPDATE refunds r
    INNER JOIN payments p ON p.id = r.payment_id
    INNER JOIN transactions t ON t.id = p.transaction_id
    SET r.status = 'success', r.provider_reference = ?, r.response_body = ?, p.status = 'refunded', t.status = 'refunded'
    WHERE r.id = ? AND r.status = 'processing' AND p.status = 'success'
  `,
  [providerReference, responseBody, refundId]
);

exports.fail = ({ refundId, error }) => query(
  "UPDATE refunds SET status = 'failed', response_body = ? WHERE id = ? AND status = 'processing'",
  [String(error).slice(0, 5000), refundId]
);
