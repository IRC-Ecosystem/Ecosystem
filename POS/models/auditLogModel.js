const db = require("../config/db");

const query = (sql, values = []) => new Promise((resolve, reject) => {
  db.query(sql, values, (error, results) => error ? reject(error) : resolve(results));
});

const stringifyJson = (value) => {
  if (value === undefined || value === null) return null;
  return JSON.stringify(value);
};

exports.create = async ({
  user = null,
  action,
  entityType,
  entityId = null,
  beforeData = null,
  afterData = null,
  reason = null,
  req = null
}) => {
  if (!action || !entityType) return null;

  const ipAddress = req?.headers?.["x-forwarded-for"]?.split(",")[0]?.trim() || req?.socket?.remoteAddress || req?.ip || null;
  const userAgent = req?.headers?.["user-agent"] || null;

  const result = await query(
    `
      INSERT INTO audit_logs (
        user_id, user_role, action, entity_type, entity_id,
        before_data, after_data, reason, ip_address, user_agent, created_at
      )
      VALUES (?, ?, ?, ?, ?, CAST(? AS JSON), CAST(? AS JSON), ?, ?, ?, NOW())
    `,
    [
      user?.id || null,
      user?.role || null,
      action,
      entityType,
      entityId === null || entityId === undefined ? null : String(entityId),
      stringifyJson(beforeData),
      stringifyJson(afterData),
      reason || null,
      ipAddress,
      userAgent
    ]
  );

  return result.insertId;
};

exports.getRecent = async ({ limit = 100, offset = 0 } = {}) => {
  return query(
    `
      SELECT
        a.id,
        a.user_id,
        a.user_role,
        a.action,
        a.entity_type,
        a.entity_id,
        a.before_data,
        a.after_data,
        a.reason,
        a.ip_address,
        a.user_agent,
        a.created_at,
        u.nama AS user_name,
        u.email AS user_email
      FROM audit_logs a
      LEFT JOIN users u ON u.id = a.user_id
      ORDER BY a.created_at DESC, a.id DESC
      LIMIT ?
      OFFSET ?
    `,
    [limit, offset]
  );
};
