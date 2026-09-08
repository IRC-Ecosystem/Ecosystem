const db = require("../config/db");
const { buildPaymentFingerprint } = require("../services/paymentPolicy");

const queryOn = (client, sql, values = []) => new Promise((resolve, reject) => {
  client.query(sql, values, (error, results) => {
    if (error) return reject(error);
    return resolve(results);
  });
});

const begin = (connection) => new Promise((resolve, reject) => connection.beginTransaction((error) => error ? reject(error) : resolve()));
const commit = (connection) => new Promise((resolve, reject) => connection.commit((error) => error ? reject(error) : resolve()));
const rollback = (connection) => new Promise((resolve) => connection.rollback(() => resolve()));

const query = (sql, values = []) => queryOn(db, sql, values);
const getConnection = () => new Promise((resolve, reject) => {
  db.getConnection((error, connection) => error ? reject(error) : resolve(connection));
});

const getSingle = async (sql, values = [], client = db) => {
  const results = await queryOn(client, sql, values);
  return results[0] || null;
};

const normalizeStatus = (status) => {
  if (["processing", "pending", "success", "failed", "refunded"].includes(status)) {
    return status;
  }

  return "pending";
};

exports.create = async ({
  transactionId,
  provider = "local",
  method,
  status,
  amount,
  paymentRequestId = null,
  providerReference = null,
  responseCode = null,
  responseBody = null,
  cashierId = null,
  idempotencyKey
}) => {
  if (!idempotencyKey) {
    throw Object.assign(new Error("Idempotency key pembayaran wajib diisi."), { code: "IDEMPOTENCY_KEY_REQUIRED" });
  }
  if (provider !== "smartbank" || method !== "smartbank") {
    throw Object.assign(new Error("Payment lokal tidak didukung; seluruh payment wajib melalui Connector."), { code: "CONNECTOR_PAYMENT_REQUIRED" });
  }
  const paymentStatus = normalizeStatus(status);
  const paidAt = paymentStatus === "success" ? new Date() : null;
  const requestFingerprint = buildPaymentFingerprint({ transactionId, provider, method, amount });

  const connection = await getConnection();
  try {
    await begin(connection);
    const result = await queryOn(
      connection,
      `
      INSERT INTO payments (
        transaction_id,
        provider,
        method,
        status,
        amount,
        payment_request_id,
        provider_reference,
        idempotency_key,
        request_fingerprint,
        provider_reference_key,
        response_code,
        response_body,
        cashier_id,
        paid_at,
        created_at
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
      ON DUPLICATE KEY UPDATE
        id = LAST_INSERT_ID(id),
        status = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(status), status),
        payment_request_id = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(payment_request_id), payment_request_id),
        response_code = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(response_code), response_code),
        response_body = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(response_body), response_body),
        paid_at = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(paid_at), paid_at)
    `,
    [
      transactionId,
      provider,
      method,
      paymentStatus,
      amount,
      paymentRequestId,
      providerReference,
      idempotencyKey,
      requestFingerprint,
      ["success", "refunded"].includes(paymentStatus) ? providerReference : null,
      responseCode,
      responseBody,
      cashierId,
      paidAt
    ]
  );

    const stored = await getSingle(
      "SELECT id, status, request_fingerprint FROM payments WHERE idempotency_key = ? LIMIT 1",
      [idempotencyKey],
      connection
    );
    if (!stored || stored.request_fingerprint !== requestFingerprint) {
      throw Object.assign(new Error("Idempotency key sudah dipakai untuk payload pembayaran lain."), { code: "IDEMPOTENCY_CONFLICT" });
    }

    await commit(connection);
    return {
      id: stored.id || result.insertId,
      status: stored.status
    };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.getLatestSuccessfulByTransactionId = async (transactionId) => {
  return getSingle(
    `
      SELECT
        p.id,
        p.transaction_id,
        p.provider,
        p.method,
        p.status,
        p.amount,
        p.payment_request_id,
        p.provider_reference,
        p.response_code,
        p.response_body,
        p.cashier_id,
        p.paid_at,
        p.created_at,
        u.nama AS cashier_name,
        u.email AS cashier_email
      FROM payments p
      LEFT JOIN users u ON u.id = p.cashier_id
      WHERE p.transaction_id = ?
        AND p.status = 'success'
      ORDER BY p.paid_at DESC, p.id DESC
      LIMIT 1
    `,
    [transactionId]
  );
};

exports.getRefundableById = (id) => getSingle(
  `
    SELECT p.id, p.provider, p.status, p.amount, p.provider_reference, t.invoice
    FROM payments p
    INNER JOIN transactions t ON t.id = p.transaction_id
    WHERE p.id = ?
    LIMIT 1
  `,
  [id]
);

exports.getRecent = async ({ limit = 8, offset = 0 } = {}) => {
  return query(
    `
      SELECT
        p.id,
        p.transaction_id,
        p.provider,
        p.method,
        p.status,
        p.amount,
        p.payment_request_id,
        p.provider_reference,
        p.response_code,
        p.cashier_id,
        p.paid_at,
        p.created_at,
        t.invoice,
        t.status AS transaction_status,
        u.nama AS cashier_name,
        u.email AS cashier_email
      FROM payments p
      LEFT JOIN transactions t ON t.id = p.transaction_id
      LEFT JOIN users u ON u.id = p.cashier_id
      ORDER BY p.created_at DESC, p.id DESC
      LIMIT ?
      OFFSET ?
    `,
    [limit, offset]
  );
};
