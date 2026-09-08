import crypto from 'node:crypto';
import mysql from 'mysql2/promise';

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'logistika',
  waitForConnections: true,
  connectionLimit: 10,
});
export const closePool = () => pool.end();

export const transitions = {
  created: ['assigned', 'cancelled'],
  assigned: ['dispatched', 'cancelled'],
  dispatched: ['in_transit', 'failed'],
  in_transit: ['delivered', 'failed'],
  failed: ['assigned', 'cancelled'],
  delivered: [],
  cancelled: [],
};

function json(res, status, body) {
  res.writeHead(status, { 'Content-Type': 'application/json', 'X-Content-Type-Options': 'nosniff' });
  res.end(JSON.stringify(body));
}

async function readBody(req) {
  const chunks = [];
  let size = 0;
  for await (const chunk of req) {
    size += chunk.length;
    if (size > 1_000_000) throw new Error('Payload terlalu besar');
    chunks.push(chunk);
  }
  return JSON.parse(Buffer.concat(chunks).toString('utf8') || '{}');
}

function requireApiKey(req) {
  const expected = process.env.LOGISTIKA_API_KEY || '';
  const provided = String(req.headers['x-api-key'] || '');
  return expected.length >= 32 && provided.length === expected.length && crypto.timingSafeEqual(Buffer.from(provided), Buffer.from(expected));
}

function callbackUrl(source) {
  if (source === 'supplierhub') return process.env.SUPPLIERHUB_CALLBACK_URL || '';
  if (source === 'marketplace') return process.env.MARKETPLACE_CALLBACK_URL || '';
  return '';
}

export function computeHmacSignature(eventId, payloadString, secret) {
  return crypto.createHmac('sha256', secret).update(`${eventId}.${payloadString}`).digest('hex');
}

export function calculateNextAttemptSeconds(attempts) {
  const base = Math.min(3600, 30 * Math.pow(2, attempts));
  const jitter = Math.floor(Math.random() * Math.min(10, base * 0.1));
  return base + jitter;
}

async function deliverCallback(shipment, eventType, eventId) {
  const url = callbackUrl(shipment.source);
  const secret = process.env.INTEGRATION_WEBHOOK_SECRET || '';
  if (!url || secret.length < 32) {
    throw new Error(`Callback URL/Secret untuk source "${shipment.source}" belum dikonfigurasi.`);
  }

  const payloadObj = {
    event: eventType,
    event_id: eventId,
    order_id: Number(shipment.order_id),
    tracking_reference: shipment.tracking_reference,
    shipment_status: shipment.status,
    occurred_at: new Date().toISOString(),
  };
  const payloadString = JSON.stringify(payloadObj);
  const signature = computeHmacSignature(eventId, payloadString, secret);

  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-B2BLink-Event-Id': eventId,
      'X-B2BLink-Signature': `sha256=${signature}`,
    },
    body: payloadString,
    signal: AbortSignal.timeout(10_000),
  });

  if (!response.ok) {
    throw new Error(`Callback ${shipment.source} gagal: HTTP ${response.status}`);
  }
}

export async function retryDeliveries() {
  const [rows] = await pool.query(
    'SELECT f.*, s.source, s.order_id, s.tracking_reference, s.status AS shipment_status FROM delivery_failures f JOIN shipments s ON s.id=f.shipment_id WHERE f.status IN ("pending", "failed") AND f.next_attempt_at<=NOW() AND f.attempts < f.max_attempts ORDER BY f.id LIMIT 25'
  );

  for (const failure of rows) {
    const shipment = {
      source: failure.source,
      order_id: failure.order_id,
      tracking_reference: failure.tracking_reference,
      status: failure.shipment_status,
    };
    try {
      await deliverCallback(shipment, failure.event_type, failure.event_id);
      await pool.execute('UPDATE delivery_failures SET status="published", resolved_at=NOW(), error_message=NULL WHERE id=?', [failure.id]);
    } catch (error) {
      const nextAttempts = failure.attempts + 1;
      const isDead = nextAttempts >= failure.max_attempts;
      const nextSec = calculateNextAttemptSeconds(failure.attempts);
      const nextStatus = isDead ? 'dead_letter' : 'failed';

      await pool.execute(
        'UPDATE delivery_failures SET attempts=?, status=?, error_message=?, next_attempt_at=DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id=?',
        [nextAttempts, nextStatus, error.message.slice(0, 500), nextSec, failure.id]
      );
    }
  }
}

async function createShipment(req, res) {
  const body = await readBody(req);
  const externalId = String(body.external_id || '').trim();
  const source = String(body.source || '').trim().toLowerCase();
  const orderId = Number(body.order_id);
  if (!/^[A-Za-z0-9._:-]{1,128}$/.test(externalId) || !['supplierhub', 'marketplace'].includes(source) || !Number.isSafeInteger(orderId) || orderId < 1 || !body.origin || !body.destination) {
    return json(res, 422, { success: false, error: { code: 'VALIDATION_ERROR', message: 'external_id, source, order_id, origin, destination tidak valid' } });
  }
  const trackingReference = `LGT-${crypto.randomBytes(8).toString('hex').toUpperCase()}`;

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();
    await connection.execute(
      'INSERT INTO shipments (external_id, source, order_id, tracking_reference, origin, destination, status) VALUES (?, ?, ?, ?, ?, ?, "created") ON DUPLICATE KEY UPDATE external_id=VALUES(external_id)',
      [externalId, source, orderId, trackingReference, String(body.origin).slice(0, 255), String(body.destination).slice(0, 255)]
    );
    const [[shipment]] = await connection.execute('SELECT * FROM shipments WHERE source=? AND external_id=?', [source, externalId]);
    await connection.execute('INSERT IGNORE INTO shipment_history (shipment_id, status, event_id) VALUES (?, "created", ?)', [shipment.id, `created:${shipment.id}`]);

    const eventId = crypto.randomUUID();
    await connection.execute(
      'INSERT IGNORE INTO delivery_failures (shipment_id, event_id, event_type, status, attempts, next_attempt_at) VALUES (?, ?, "shipment.created", "pending", 0, NOW())',
      [shipment.id, eventId]
    );

    await connection.commit();

    // Trigger immediate outbox delivery attempt
    retryDeliveries().catch(() => {});

    return json(res, 201, { success: true, data: shipment });
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function updateShipment(req, res, shipmentId) {
  const body = await readBody(req);
  const nextStatus = String(body.status || '');
  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();
    const [[shipment]] = await connection.execute('SELECT * FROM shipments WHERE id=? FOR UPDATE', [shipmentId]);
    if (!shipment) { await connection.rollback(); return json(res, 404, { success: false, error: { code: 'NOT_FOUND', message: 'Shipment tidak ditemukan' } }); }
    if (!(transitions[shipment.status] || []).includes(nextStatus)) { await connection.rollback(); return json(res, 409, { success: false, error: { code: 'INVALID_TRANSITION', message: `${shipment.status} ke ${nextStatus} tidak diizinkan` } }); }

    await connection.execute('UPDATE shipments SET status=?, updated_at=NOW() WHERE id=?', [nextStatus, shipmentId]);

    const eventId = crypto.randomUUID();
    await connection.execute('INSERT INTO shipment_history (shipment_id, status, event_id, metadata) VALUES (?, ?, ?, ?)', [shipmentId, nextStatus, eventId, JSON.stringify(body.metadata || {})]);

    await connection.execute(
      'INSERT INTO delivery_failures (shipment_id, event_id, event_type, status, attempts, next_attempt_at) VALUES (?, ?, "shipment.status_changed", "pending", 0, NOW())',
      [shipmentId, eventId]
    );

    await connection.commit();
    shipment.status = nextStatus;

    // Trigger immediate outbox delivery attempt
    retryDeliveries().catch(() => {});

    return json(res, 200, { success: true, data: shipment });
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

export async function handler(req, res) {
  try {
    if (req.method === 'GET' && req.url === '/health') {
      await pool.query('SELECT 1');
      return json(res, 200, { status: 'ok', service: 'logistika', database: 'connected' });
    }
    if (!requireApiKey(req)) return json(res, 401, { success: false, error: { code: 'UNAUTHORIZED', message: 'API key tidak valid' } });
    if (req.method === 'POST' && req.url === '/api/v1/shipments') return await createShipment(req, res);
    const detail = req.url.match(/^\/api\/v1\/shipments\/(\d+)$/);
    if (req.method === 'GET' && detail) { const [[shipment]]=await pool.execute('SELECT * FROM shipments WHERE id=?',[Number(detail[1])]); return shipment?json(res,200,{success:true,data:shipment}):json(res,404,{success:false,error:{code:'NOT_FOUND',message:'Shipment tidak ditemukan'}}); }
    const match = req.url.match(/^\/api\/v1\/shipments\/(\d+)\/status$/);
    if (req.method === 'PATCH' && match) return await updateShipment(req, res, Number(match[1]));
    return json(res, 404, { success: false, error: { code: 'NOT_FOUND', message: 'Route tidak ditemukan' } });
  } catch (error) {
    console.error(JSON.stringify({ event: 'logistika_request_failed', error: error.message }));
    return json(res, 500, { success: false, error: { code: 'INTERNAL_ERROR', message: 'Terjadi kesalahan internal' } });
  }
}
