const db = require("../config/db");

const queryOn = (client, sql, values = []) => new Promise((resolve, reject) => {
  client.query(sql, values, (error, results) => error ? reject(error) : resolve(results));
});

const getConnection = () => new Promise((resolve, reject) => {
  db.getConnection((error, connection) => error ? reject(error) : resolve(connection));
});

const begin = (connection) => new Promise((resolve, reject) => connection.beginTransaction((error) => error ? reject(error) : resolve()));
const commit = (connection) => new Promise((resolve, reject) => connection.commit((error) => error ? reject(error) : resolve()));
const rollback = (connection) => new Promise((resolve) => connection.rollback(() => resolve()));

exports.claimBatch = async (limit = 20) => {
  const connection = await getConnection();
  try {
    await begin(connection);
    const rows = await queryOn(
      connection,
      `
        SELECT id, event_type, payload, attempts, max_attempts
        FROM outbox_events
        WHERE (status = 'pending' AND next_attempt_at <= NOW())
           OR (status = 'processing' AND locked_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE))
        ORDER BY id
        LIMIT ?
        FOR UPDATE SKIP LOCKED
      `,
      [Math.max(1, Math.min(Number(limit) || 20, 100))]
    );
    if (rows.length) {
      await queryOn(
        connection,
        "UPDATE outbox_events SET status = 'processing', locked_at = NOW() WHERE id IN (?)",
        [rows.map((row) => row.id)]
      );
    }
    await commit(connection);
    return rows.map((row) => ({
      ...row,
      payload: typeof row.payload === "string" ? JSON.parse(row.payload) : row.payload
    }));
  } catch (error) {
    await rollback(connection);
    throw error;
  } finally {
    connection.release();
  }
};

exports.markPublished = (id) => queryOn(
  db,
  "UPDATE outbox_events SET status = 'published', published_at = NOW(), locked_at = NULL, last_error = NULL WHERE id = ? AND status = 'processing'",
  [id]
);

exports.markFailed = (id, { deadLetter, nextAttemptAt, error }) => queryOn(
  db,
  `
    UPDATE outbox_events
    SET status = '${deadLetter ? "dead_letter" : "pending"}',
        attempts = attempts + 1,
        next_attempt_at = ?,
        locked_at = NULL,
        last_error = ?
    WHERE id = ? AND status = 'processing'
  `,
  [nextAttemptAt, String(error).slice(0, 2000), id]
);
