const db = require("../config/db");
const { buildPaymentFingerprint, validateMoney } = require("../services/paymentPolicy");

const getConnection = () => new Promise((resolve, reject) => {
  db.getConnection((error, connection) => error ? reject(error) : resolve(connection));
});

const beginTransaction = (connection) => new Promise((resolve, reject) => {
  connection.beginTransaction((error) => error ? reject(error) : resolve());
});

const commit = (connection) => new Promise((resolve, reject) => {
  connection.commit((error) => error ? reject(error) : resolve());
});

const rollback = (connection) => new Promise((resolve) => {
  connection.rollback(() => resolve());
});

const queryOn = (client, sql, values = []) => new Promise((resolve, reject) => {
  client.query(sql, values, (error, results) => error ? reject(error) : resolve(results));
});

const query = (sql, values = []) => queryOn(db, sql, values);

const getSingle = async (sql, values = [], client = db) => {
  const results = await queryOn(client, sql, values);
  return results[0] || null;
};

const PAYABLE_STATUSES = ["approved", "pending_payment"];
const CONSUMER_RESERVATION_MINUTES = Math.max(Number(process.env.CONSUMER_RESERVATION_MINUTES) || 15, 1);
const CASHIER_RESERVATION_MINUTES = Math.max(Number(process.env.CASHIER_RESERVATION_MINUTES) || 10, 1);

const aggregateItems = (items) => {
  const grouped = new Map();
  for (const item of items) grouped.set(Number(item.product_id), (grouped.get(Number(item.product_id)) || 0) + Number(item.qty));
  return [...grouped.entries()].map(([productId, qty]) => ({ productId, qty }));
};

const reserveInventory = async (connection, transactionId, items, expiresAt) => {
  for (const item of aggregateItems(items)) {
    const result = await queryOn(connection,
      "UPDATE products SET reserved = reserved + ?, available = on_hand - reserved, stock = on_hand - reserved WHERE id = ? AND on_hand - reserved >= ?",
      [item.qty, item.productId, item.qty]);
    if (result.affectedRows !== 1) return { success: false, productId: item.productId };
    await queryOn(connection, "INSERT INTO inventory_reservations (transaction_id, product_id, qty, status, expires_at) VALUES (?, ?, ?, 'active', ?)", [transactionId, item.productId, item.qty, expiresAt]);
    await queryOn(connection, "INSERT INTO inventory_movements (product_id, transaction_id, movement_type, reserved_delta, note) VALUES (?, ?, 'reserved', ?, 'Reservation transaksi dibuat')", [item.productId, transactionId, item.qty]);
  }
  return { success: true };
};

const releaseInventory = async (connection, transactionId, movementType = "released", note = "Reservation transaksi dilepas") => {
  const reservations = await queryOn(connection, "SELECT id, product_id, qty FROM inventory_reservations WHERE transaction_id = ? AND status = 'active' FOR UPDATE", [transactionId]);
  for (const reservation of reservations) {
    const result = await queryOn(connection, "UPDATE products SET reserved = reserved - ?, available = on_hand - reserved, stock = on_hand - reserved WHERE id = ? AND reserved >= ?", [reservation.qty, reservation.product_id, reservation.qty]);
    if (result.affectedRows !== 1) throw new Error("Reservation stok tidak konsisten.");
    await queryOn(connection, "UPDATE inventory_reservations SET status = ?, released_at = NOW() WHERE id = ?", [movementType, reservation.id]);
    await queryOn(connection, "INSERT INTO inventory_movements (product_id, transaction_id, movement_type, reserved_delta, note) VALUES (?, ?, ?, ?, ?)", [reservation.product_id, transactionId, movementType, -Number(reservation.qty), note]);
  }
};

const consumeInventory = async (connection, transactionId) => {
  const reservations = await queryOn(connection, "SELECT id, product_id, qty FROM inventory_reservations WHERE transaction_id = ? AND status = 'active' FOR UPDATE", [transactionId]);
  if (reservations.length === 0) return { success: false, reason: "reservation_missing" };
  for (const reservation of reservations) {
    const result = await queryOn(connection, "UPDATE products SET on_hand = on_hand - ?, reserved = reserved - ?, available = on_hand - reserved, stock = on_hand - reserved WHERE id = ? AND on_hand >= ? AND reserved >= ?", [reservation.qty, reservation.qty, reservation.product_id, reservation.qty, reservation.qty]);
    if (result.affectedRows !== 1) return { success: false, reason: "insufficient_stock" };
    await queryOn(connection, "UPDATE inventory_reservations SET status = 'consumed', released_at = NOW() WHERE id = ?", [reservation.id]);
    await queryOn(connection, "INSERT INTO inventory_movements (product_id, transaction_id, movement_type, on_hand_delta, reserved_delta, note) VALUES (?, ?, 'consumed', ?, ?, 'Payment berhasil diselesaikan')", [reservation.product_id, transactionId, -Number(reservation.qty), -Number(reservation.qty)]);
  }
  return { success: true };
};

const getManagerDateFilter = (filter) => {
  if (filter === "today") {
    return "DATE(t.created_at) = CURDATE()";
  }

  if (filter === "week") {
    return "YEARWEEK(t.created_at, 1) = YEARWEEK(CURDATE(), 1)";
  }

  if (filter === "month") {
    return "YEAR(t.created_at) = YEAR(CURDATE()) AND MONTH(t.created_at) = MONTH(CURDATE())";
  }

  return "1 = 1";
};

const getManagerChartGroupConfig = (filter) => {
  if (filter === "today") {
    return {
      selectExpr: "DATE_FORMAT(t.created_at, '%Y-%m-%d %H:00:00')",
      groupExpr: "DATE_FORMAT(t.created_at, '%Y-%m-%d %H:00:00')",
      alias: "sales_period"
    };
  }

  if (filter === "all") {
    return {
      selectExpr: "DATE_FORMAT(t.created_at, '%Y-%m-%d')",
      groupExpr: "DATE_FORMAT(t.created_at, '%Y-%m-%d')",
      alias: "sales_period"
    };
  }

  return {
    selectExpr: "DATE_FORMAT(t.created_at, '%Y-%m-%d')",
    groupExpr: "DATE_FORMAT(t.created_at, '%Y-%m-%d')",
    alias: "sales_period"
  };
};

exports.createTransactionWithItems = async ({ transaction, items }) => {
  validateMoney(transaction.subtotal);
  validateMoney(transaction.grand_total);
  if (!Number.isSafeInteger(transaction.fee) || transaction.fee < 0) {
    throw Object.assign(new Error("Fee harus bilangan bulat non-negatif."), { code: "INVALID_AMOUNT" });
  }
  for (const item of items) {
    validateMoney(item.price);
    validateMoney(item.subtotal);
  }
  const connection = await getConnection();
  try {
    await beginTransaction(connection);

    const transactionResult = await queryOn(
      connection,
      `
        INSERT INTO transactions (invoice, user_id, cashier_id, subtotal, fee, grand_total, status, payment_method, stock_deducted, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
      `,
      [
        transaction.invoice,
        transaction.user_id || null,
        transaction.cashier_id || null,
        transaction.subtotal,
        transaction.fee,
        transaction.grand_total,
        transaction.status,
        transaction.payment_method || null,
        transaction.stock_deducted || 0
      ]
    );

    const transactionId = transactionResult.insertId;
    const itemValues = items.map((item) => [
      transactionId,
      item.product_id,
      item.qty,
      item.price,
      item.subtotal
    ]);

    await queryOn(
      connection,
      `
        INSERT INTO transaction_items (transaction_id, product_id, qty, price, subtotal)
        VALUES ?
      `,
      [itemValues]
    );

    const reservation = await reserveInventory(
      connection,
      transactionId,
      items,
      new Date(Date.now() + (CONSUMER_RESERVATION_MINUTES * 60 * 1000))
    );
    if (!reservation.success) {
      await rollback(connection);
      return { success: false, reason: "insufficient_stock" };
    }

    await commit(connection);

    return {
      success: true,
      id: transactionId,
      invoice: transaction.invoice
    };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.createDirectInvoiceTransaction = async ({ transaction, items }) => {
  validateMoney(transaction.subtotal);
  validateMoney(transaction.grand_total);
  if (!Number.isSafeInteger(transaction.fee) || transaction.fee < 0) {
    throw Object.assign(new Error("Fee harus bilangan bulat non-negatif."), { code: "INVALID_AMOUNT" });
  }
  for (const item of items) {
    validateMoney(item.price);
    validateMoney(item.subtotal);
  }
  const connection = await getConnection();
  try {
    await beginTransaction(connection);

    for (const item of items) {
      const product = await getSingle(
        `
          SELECT id, nama_produk, stock
          FROM products
          WHERE id = ?
          LIMIT 1
          FOR UPDATE
        `,
        [item.product_id],
        connection
      );

      if (!product) {
        await rollback(connection);
        return { success: false, reason: "product_not_found" };
      }

      if (Number(product.stock) < Number(item.qty)) {
        await rollback(connection);
        return {
          success: false,
          reason: "insufficient_stock",
          productName: product.nama_produk
        };
      }
    }

    const transactionResult = await queryOn(
      connection,
      `
        INSERT INTO transactions (invoice, user_id, cashier_id, subtotal, fee, grand_total, status, payment_method, stock_deducted, created_at)
        VALUES (?, NULL, ?, ?, ?, ?, 'pending_payment', ?, 0, NOW())
      `,
      [
        transaction.invoice,
        transaction.cashier_id,
        transaction.subtotal,
        transaction.fee,
        transaction.grand_total,
        transaction.payment_method
      ]
    );

    const transactionId = transactionResult.insertId;
    const itemValues = items.map((item) => [
      transactionId,
      item.product_id,
      item.qty,
      item.price,
      item.subtotal
    ]);

    await queryOn(
      connection,
      `
        INSERT INTO transaction_items (transaction_id, product_id, qty, price, subtotal)
        VALUES ?
      `,
      [itemValues]
    );

    const reservation = await reserveInventory(
      connection,
      transactionId,
      items,
      new Date(Date.now() + (CASHIER_RESERVATION_MINUTES * 60 * 1000))
    );
    if (!reservation.success) {
      await rollback(connection);
      return { success: false, reason: "insufficient_stock" };
    }

    await commit(connection);

    return {
      success: true,
      transactionId,
      invoice: transaction.invoice
    };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.getDashboardTransactions = async () => {
  return query(
    `
      SELECT
        t.id,
        t.invoice,
        t.user_id,
        t.cashier_id,
        t.subtotal,
        t.fee,
        t.grand_total,
        t.status,
        t.payment_method,
        t.created_at,
        u.nama AS customer_name,
        u.email AS customer_email,
        c.nama AS cashier_name
      FROM transactions t
      LEFT JOIN users u ON u.id = t.user_id
      LEFT JOIN users c ON c.id = t.cashier_id
      ORDER BY t.created_at DESC, t.id DESC
    `
  );
};

exports.getByInvoiceAndUser = async (invoice, userId) => {
  const results = await query(
    `
      SELECT id, invoice, user_id, subtotal, fee, grand_total, status, payment_method, created_at
      FROM transactions
      WHERE invoice = ? AND user_id = ?
      LIMIT 1
    `,
    [invoice, userId]
  );

  return results[0] || null;
};

exports.getByUser = async (userId) => {
  return query(
    `
      SELECT
        t.id,
        t.invoice,
        t.user_id,
        t.cashier_id,
        t.subtotal,
        t.fee,
        t.grand_total,
        t.status,
        t.payment_method,
        t.created_at,
        c.nama AS cashier_name,
        COUNT(ti.id) AS total_items,
        COALESCE(SUM(ti.qty), 0) AS total_qty
      FROM transactions t
      LEFT JOIN users c ON c.id = t.cashier_id
      LEFT JOIN transaction_items ti ON ti.transaction_id = t.id
      WHERE t.user_id = ?
      GROUP BY
        t.id,
        t.invoice,
        t.user_id,
        t.cashier_id,
        t.subtotal,
        t.fee,
        t.grand_total,
        t.status,
        t.payment_method,
        t.created_at,
        c.nama
      ORDER BY t.created_at DESC, t.id DESC
    `,
    [userId]
  );
};

exports.getById = async (transactionId) => {
  return getSingle(
    `
      SELECT
        t.id,
        t.invoice,
        t.user_id,
        t.cashier_id,
        t.subtotal,
        t.fee,
        t.grand_total,
        t.status,
        t.payment_method,
        t.created_at,
        u.nama AS customer_name,
        u.email AS customer_email,
        c.nama AS cashier_name
      FROM transactions t
      LEFT JOIN users u ON u.id = t.user_id
      LEFT JOIN users c ON c.id = t.cashier_id
      WHERE t.id = ?
      LIMIT 1
    `,
    [transactionId]
  );
};

exports.getItemsByTransactionId = async (transactionId) => {
  return query(
    `
      SELECT
        ti.id,
        ti.transaction_id,
        ti.product_id,
        ti.qty,
        ti.price,
        ti.subtotal,
        p.nama_produk,
        p.gambar,
        p.kategori
      FROM transaction_items ti
      LEFT JOIN products p ON p.id = ti.product_id
      WHERE ti.transaction_id = ?
      ORDER BY ti.id ASC
    `,
    [transactionId]
  );
};

exports.updateStatus = async ({ transactionId, status, cashierId, allowedCurrentStatuses }) => {
  const connection = await getConnection();
  try {
    await beginTransaction(connection);

    const transaction = await getSingle(
      `
        SELECT id, invoice, status, stock_deducted
        FROM transactions
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
      `,
      [transactionId],
      connection
    );

    if (!transaction) {
      await rollback(connection);
      return { success: false, reason: "not_found" };
    }

    if (!allowedCurrentStatuses.includes(transaction.status)) {
      await rollback(connection);
      return { success: false, reason: "invalid_status", transaction };
    }

    await queryOn(
      connection,
      `
        UPDATE transactions
        SET status = ?, cashier_id = ?, stock_deducted = ?
        WHERE id = ? AND status IN (?)
      `,
      [status, cashierId, Number(transaction.stock_deducted || 0), transactionId, allowedCurrentStatuses]
    );

    await commit(connection);

    return { success: true };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.voidTransaction = async ({ transactionId, actor, reason, requestMeta = {} }) => {
  const connection = await getConnection();
  try {
    await beginTransaction(connection);

    const transaction = await getSingle(
      `
        SELECT id, invoice, status, stock_deducted, payment_method, grand_total
        FROM transactions
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
      `,
      [transactionId],
      connection
    );

    if (!transaction) {
      await rollback(connection);
      return { success: false, reason: "not_found" };
    }

    if (status === "rejected") {
      await releaseInventory(connection, transactionId, "released", "Transaksi ditolak kasir");
    }

    if (!["pending", "approved", "pending_payment"].includes(transaction.status) || Number(transaction.stock_deducted || 0) !== 0) {
      await rollback(connection);
      return { success: false, reason: "invalid_status", transaction };
    }

    const successfulPayment = await getSingle(
      "SELECT id FROM payments WHERE transaction_id = ? AND status = 'success' LIMIT 1 FOR UPDATE",
      [transactionId],
      connection
    );
    if (successfulPayment) {
      await rollback(connection);
      return { success: false, reason: "payment_exists", transaction };
    }

    const updateResult = await queryOn(
      connection,
      "UPDATE transactions SET status = 'voided' WHERE id = ? AND status IN ('pending', 'approved', 'pending_payment') AND stock_deducted = 0",
      [transactionId]
    );
    if (updateResult.affectedRows !== 1) {
      await rollback(connection);
      return { success: false, reason: "invalid_status", transaction };
    }

    await releaseInventory(connection, transactionId, "released", "Transaksi di-void manager");

    const beforeData = JSON.stringify({
      invoice: transaction.invoice,
      status: transaction.status,
      grand_total: Number(transaction.grand_total),
      payment_method: transaction.payment_method,
      stock_deducted: Number(transaction.stock_deducted || 0)
    });
    const afterData = JSON.stringify({ status: "voided" });
    await queryOn(
      connection,
      `
        INSERT INTO audit_logs (
          user_id, user_role, action, entity_type, entity_id,
          before_data, after_data, reason, ip_address, user_agent, created_at
        )
        VALUES (?, ?, 'transaction.voided', 'transaction', ?, CAST(? AS JSON), CAST(? AS JSON), ?, ?, ?, NOW())
      `,
      [
        actor?.id || null,
        actor?.role || null,
        String(transaction.id),
        beforeData,
        afterData,
        reason,
        requestMeta.ipAddress || null,
        requestMeta.userAgent || null
      ]
    );

    await commit(connection);
    return { success: true, transaction: { ...transaction, status: "voided" } };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.expireReservations = async ({ limit = 50 } = {}) => {
  const expired = await query(
    `
      SELECT DISTINCT ir.transaction_id
      FROM inventory_reservations ir
      INNER JOIN transactions t ON t.id = ir.transaction_id
      WHERE ir.status = 'active'
        AND ir.expires_at <= NOW()
        AND t.status IN ('pending', 'approved', 'pending_payment')
      ORDER BY ir.expires_at ASC
      LIMIT ?
    `,
    [limit]
  );

  let released = 0;
  for (const row of expired) {
    const connection = await getConnection();
    try {
      await beginTransaction(connection);
      const transaction = await getSingle("SELECT id, invoice, status FROM transactions WHERE id = ? LIMIT 1 FOR UPDATE", [row.transaction_id], connection);
      if (!transaction || !["pending", "approved", "pending_payment"].includes(transaction.status)) {
        await rollback(connection);
        continue;
      }
      await releaseInventory(connection, transaction.id, "expired", "Reservation melewati batas waktu pembayaran");
      await queryOn(connection, "UPDATE transactions SET status = 'voided' WHERE id = ?", [transaction.id]);
      await queryOn(
        connection,
        "INSERT INTO audit_logs (user_role, action, entity_type, entity_id, before_data, after_data, reason, created_at) VALUES ('system', 'transaction.expired', 'transaction', ?, CAST(? AS JSON), CAST(? AS JSON), ?, NOW())",
        [String(transaction.id), JSON.stringify({ invoice: transaction.invoice, status: transaction.status }), JSON.stringify({ status: "voided" }), "Reservation melewati batas waktu pembayaran"]
      );
      await commit(connection);
      released += 1;
    } catch (error) {
      await rollback(connection);
      throw error;
    } finally {
      connection.release();
    }
  }
  return released;
};

exports.validatePayableTransaction = async (transactionId) => {
  const transaction = await getSingle(
    `
      SELECT id, invoice, status, stock_deducted
      FROM transactions
      WHERE id = ?
      LIMIT 1
    `,
    [transactionId]
  );

  if (!transaction) {
    return { success: false, reason: "not_found" };
  }

  if (!PAYABLE_STATUSES.includes(transaction.status)) {
    return { success: false, reason: "invalid_status", transaction };
  }

  if (Number(transaction.stock_deducted || 0) === 0) {
    const items = await query(
      `
        SELECT product_id, qty
        FROM transaction_items
        WHERE transaction_id = ?
      `,
      [transactionId]
    );

    for (const item of items) {
      const product = await getSingle(
        `
          SELECT id, nama_produk, stock
          FROM products
          WHERE id = ?
          LIMIT 1
        `,
        [item.product_id]
      );

      if (!product) {
        return { success: false, reason: "product_not_found" };
      }

      if (Number(product.stock) < Number(item.qty)) {
        return {
          success: false,
          reason: "insufficient_stock",
          productName: product.nama_produk
        };
      }
    }
  }

  return { success: true, transaction };
};

exports.payTransaction = async ({ transactionId, cashierId, paymentMethod }) => {
  const connection = await getConnection();
  try {
    await beginTransaction(connection);

    const transaction = await getSingle(
      `
        SELECT id, invoice, status, stock_deducted, grand_total
        FROM transactions
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
      `,
      [transactionId],
      connection
    );

    if (!transaction) {
      await rollback(connection);
      return { success: false, reason: "not_found" };
    }

    if (!PAYABLE_STATUSES.includes(transaction.status)) {
      await rollback(connection);
      return { success: false, reason: "invalid_status", transaction };
    }

    const successfulPayment = await getSingle(
      `
        SELECT id, amount
        FROM payments
        WHERE transaction_id = ?
          AND method = ?
          AND status = 'success'
        ORDER BY paid_at DESC, id DESC
        LIMIT 1
        FOR UPDATE
      `,
      [transactionId, paymentMethod],
      connection
    );

    if (!successfulPayment || Number(successfulPayment.amount) !== Number(transaction.grand_total)) {
      await rollback(connection);
      return { success: false, reason: "payment_not_success" };
    }

    if (Number(transaction.stock_deducted || 0) === 0) {
      const inventory = await consumeInventory(connection, transactionId);
      if (!inventory.success) {
        await rollback(connection);
        return { success: false, reason: inventory.reason === "reservation_missing" ? "reservation_missing" : "insufficient_stock" };
      }
    }

    const updateResult = await queryOn(
      connection,
      `
        UPDATE transactions
        SET status = 'paid', cashier_id = ?, payment_method = ?, stock_deducted = 1
        WHERE id = ? AND status IN ('approved', 'pending_payment')
      `,
      [cashierId, paymentMethod, transactionId]
    );
    if (updateResult.affectedRows !== 1) {
      await rollback(connection);
      return { success: false, reason: "invalid_status" };
    }

    const payload = JSON.stringify({
      transactionId: transaction.id,
      invoice: transaction.invoice,
      amount: Number(transaction.grand_total),
      paymentMethod
    });
    await queryOn(
      connection,
      `
        INSERT INTO outbox_events (aggregate_type, aggregate_id, event_type, payload, idempotency_key)
        VALUES
          ('invoice', ?, 'invoice.paid', ?, ?),
          ('invoice', ?, 'umkm.insight.invoice_paid', ?, ?)
      `,
      [transaction.id, payload, `invoice.paid:${transaction.id}`, transaction.id, payload, `umkm.insight.invoice_paid:${transaction.id}`]
    );

    await commit(connection);
    return { success: true };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.settleSmartBankPayment = async ({ transactionId, cashierId, payment }) => {
  const connection = await getConnection();
  try {
    await beginTransaction(connection);
    const transaction = await getSingle(
      `
        SELECT id, invoice, status, stock_deducted, grand_total
        FROM transactions
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
      `,
      [transactionId],
      connection
    );
    if (!transaction) {
      await rollback(connection);
      return { success: false, reason: "not_found" };
    }
    if (!PAYABLE_STATUSES.includes(transaction.status)) {
      await rollback(connection);
      return { success: false, reason: "invalid_status", transaction };
    }
    if (!Number.isSafeInteger(payment.amount) || payment.amount <= 0 || payment.amount !== Number(transaction.grand_total)) {
      await rollback(connection);
      return { success: false, reason: "amount_mismatch" };
    }

    const requestFingerprint = buildPaymentFingerprint({
      transactionId,
      provider: "smartbank",
      method: "smartbank",
      amount: payment.amount
    });
    await queryOn(
      connection,
      `
        INSERT INTO payments (
          transaction_id, provider, method, status, amount, payment_request_id,
          provider_reference, idempotency_key, request_fingerprint,
          provider_reference_key, response_code, response_body, cashier_id, paid_at, created_at
        )
        VALUES (?, 'smartbank', 'smartbank', 'success', ?, ?, ?, ?, ?, ?, 200, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
          id = LAST_INSERT_ID(id),
          status = IF(request_fingerprint = VALUES(request_fingerprint), 'success', status),
          payment_request_id = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(payment_request_id), payment_request_id),
          provider_reference = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(provider_reference), provider_reference),
          provider_reference_key = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(provider_reference_key), provider_reference_key),
          response_code = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(response_code), response_code),
          response_body = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(response_body), response_body),
          paid_at = IF(request_fingerprint = VALUES(request_fingerprint), VALUES(paid_at), paid_at)
      `,
      [
        transactionId,
        payment.amount,
        payment.paymentRequestId,
        payment.providerReference,
        payment.idempotencyKey,
        requestFingerprint,
        payment.providerReference,
        payment.responseBody,
        cashierId
      ]
    );
    const storedPayment = await getSingle(
      "SELECT id, request_fingerprint FROM payments WHERE idempotency_key = ? LIMIT 1 FOR UPDATE",
      [payment.idempotencyKey],
      connection
    );
    if (!storedPayment || storedPayment.request_fingerprint !== requestFingerprint) {
      await rollback(connection);
      return { success: false, reason: "idempotency_conflict" };
    }

    if (Number(transaction.stock_deducted || 0) === 0) {
      const inventory = await consumeInventory(connection, transactionId);
      if (!inventory.success) {
        await rollback(connection);
        return { success: false, reason: inventory.reason === "reservation_missing" ? "reservation_missing" : "insufficient_stock" };
      }
    }

    const updateResult = await queryOn(
      connection,
      `
        UPDATE transactions
        SET status = 'paid', cashier_id = ?, payment_method = 'smartbank', stock_deducted = 1
        WHERE id = ? AND status IN ('approved', 'pending_payment')
      `,
      [cashierId, transactionId]
    );
    if (updateResult.affectedRows !== 1) {
      await rollback(connection);
      return { success: false, reason: "invalid_status" };
    }

    const payload = JSON.stringify({
      transactionId: transaction.id,
      invoice: transaction.invoice,
      amount: payment.amount,
      paymentMethod: "smartbank"
    });
    await queryOn(
      connection,
      `
        INSERT INTO outbox_events (aggregate_type, aggregate_id, event_type, payload, idempotency_key)
        VALUES
          ('invoice', ?, 'invoice.paid', ?, ?),
          ('invoice', ?, 'umkm.insight.invoice_paid', ?, ?)
      `,
      [transaction.id, payload, `invoice.paid:${transaction.id}`, transaction.id, payload, `umkm.insight.invoice_paid:${transaction.id}`]
    );

    await commit(connection);
    return { success: true };
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.getManagerKpis = async (filter) => {
  const dateCondition = getManagerDateFilter(filter);

  return getSingle(
    `
      SELECT
        COALESCE(SUM(t.grand_total), 0) AS total_sales,
        COUNT(*) AS total_transactions,
        COALESCE(SUM(t.fee), 0) AS total_fee,
        COALESCE(AVG(t.grand_total), 0) AS average_transaction
      FROM transactions t
      WHERE t.status = 'paid' AND ${dateCondition}
    `
  );
};

exports.countManagerRecentTransactions = async (filter) => {
  const dateCondition = getManagerDateFilter(filter);

  const result = await getSingle(
    `
      SELECT COUNT(*) AS total
      FROM transactions t
      WHERE ${dateCondition}
    `
  );

  return Number(result?.total || 0);
};

exports.getManagerRecentTransactions = async (filter, limit = 10, offset = 0) => {
  const dateCondition = getManagerDateFilter(filter);

  return query(
    `
      SELECT
        t.id,
        t.invoice,
        t.grand_total,
        t.payment_method,
        t.status,
        t.created_at,
        u.nama AS customer_name,
        u.email AS customer_email,
        c.nama AS cashier_name
      FROM transactions t
      LEFT JOIN users u ON u.id = t.user_id
      LEFT JOIN users c ON c.id = t.cashier_id
      WHERE ${dateCondition}
      ORDER BY t.created_at DESC, t.id DESC
      LIMIT ?
      OFFSET ?
    `,
    [limit, offset]
  );
};

exports.getManagerReportTransactions = async (filter) => {
  const dateCondition = getManagerDateFilter(filter);

  return query(
    `
      SELECT
        t.id,
        t.invoice,
        t.grand_total,
        t.fee,
        t.payment_method,
        t.status,
        t.created_at,
        u.nama AS customer_name,
        u.email AS customer_email,
        c.nama AS cashier_name
      FROM transactions t
      LEFT JOIN users u ON u.id = t.user_id
      LEFT JOIN users c ON c.id = t.cashier_id
      WHERE ${dateCondition}
      ORDER BY t.created_at DESC, t.id DESC
    `
  );
};

exports.getManagerCashierPerformance = async (filter) => {
  const dateCondition = getManagerDateFilter(filter);

  return query(
    `
      SELECT
        t.cashier_id,
        COALESCE(u.nama, u.email, 'Kasir') AS cashier_name,
        COUNT(*) AS total_transactions,
        COALESCE(SUM(t.grand_total), 0) AS total_sales,
        COALESCE(SUM(t.fee), 0) AS total_fee
      FROM transactions t
      LEFT JOIN users u ON u.id = t.cashier_id
      WHERE t.status = 'paid'
        AND t.cashier_id IS NOT NULL
        AND ${dateCondition}
      GROUP BY t.cashier_id, u.nama, u.email
      ORDER BY total_sales DESC, total_transactions DESC
    `
  );
};

exports.getManagerSalesChart = async (filter) => {
  const dateCondition = getManagerDateFilter(filter);
  const chartGroup = getManagerChartGroupConfig(filter);

  return query(
    `
      SELECT
        ${chartGroup.selectExpr} AS ${chartGroup.alias},
        COALESCE(SUM(t.grand_total), 0) AS total_sales
      FROM transactions t
      WHERE t.status = 'paid'
        AND ${dateCondition}
      GROUP BY ${chartGroup.groupExpr}
      ORDER BY ${chartGroup.alias} ASC
    `
  );
};
