import express from 'express';
import cors from 'cors';
import { createProxyMiddleware } from 'http-proxy-middleware';
import dotenv from 'dotenv';
import { jwtMiddleware } from './middleware/jwt.js';
import { auditRequests, createRateLimiter, requestContext, securityHeaders } from './middleware/security.js';

dotenv.config();

// C1: hard-fail bila JWT_SECRET hilang, terlalu pendek, atau nilai default/ter-leaked.
const KNOWN_BAD_SECRETS = [
  'change-me-for-development',
  'supersecretkey_change_me_2026',
  'laragon_local_dev_secret_min_32_chars_abcdef123456',
  'change_me_jwt_secret_min_32_chars',
];
const _gwSecret = process.env.JWT_SECRET ?? '';
if (!_gwSecret || _gwSecret.length < 32 || KNOWN_BAD_SECRETS.includes(_gwSecret)) {
  throw new Error('JWT_SECRET wajib di-set, >=32 karakter, dan bukan nilai default/ter-leaked.');
}

const app = express();
const PORT = process.env.PORT || 4000;

const CENTRAL_BANK_URL = process.env.CENTRAL_BANK_URL || 'http://localhost:3000';
const WALLET_URL = process.env.WALLET_URL || 'http://localhost:6969';

// SSRF Protection: Validate upstream URLs (L7: fail-closed, no warn-only)
try {
  const cbUrl = new URL(CENTRAL_BANK_URL);
  const walletUrl = new URL(WALLET_URL);
  const allowedHosts = ['localhost', '127.0.0.1', 'central-bank', 'wallet', 'api.smartbank.local'];
  if (!allowedHosts.includes(cbUrl.hostname) && !cbUrl.hostname.endsWith('.internal')) {
    throw new Error(`CENTRAL_BANK_URL host tidak diizinkan: ${cbUrl.hostname}`);
  }
  if (!allowedHosts.includes(walletUrl.hostname) && !walletUrl.hostname.endsWith('.internal')) {
    throw new Error(`WALLET_URL host tidak diizinkan: ${walletUrl.hostname}`);
  }
} catch (err) {
  throw new Error('Invalid CENTRAL_BANK_URL or WALLET_URL environment variable: ' + err.message);
}

const allowedOrigins = (process.env.CORS_ALLOWED_ORIGINS || 'http://localhost:3001,http://localhost:5173')
  .split(',')
  .map((origin) => origin.trim())
  .filter(Boolean);
const proxyErrorHandler = (err, req, res) => {
  console.error({ timestamp: new Date().toISOString(), request_id: req.id, action: 'proxy_upstream_error', error_type: err.name });
  res.status(502).json({
    success: false,
    data: null,
    error: { code: 'UPSTREAM_UNAVAILABLE', message: 'Service tujuan sementara tidak tersedia', details: {} },
    meta: { request_id: req.id || 'req_unknown', timestamp: new Date().toISOString() },
  });
};

// L8: strip internal headers dari upstream response (defense-in-depth).
const stripInternalHeaders = (proxyRes) => {
  delete proxyRes.headers['server'];
  delete proxyRes.headers['x-powered-by'];
  delete proxyRes.headers['x-debug'];
  delete proxyRes.headers['x-internal-version'];
  delete proxyRes.headers['x-runtime'];
};

const trustProxy = process.env.TRUST_PROXY;
app.set('trust proxy', trustProxy === 'true' ? 'loopback' : (trustProxy && trustProxy !== 'false' ? trustProxy : false));
app.use(requestContext);
app.use(cors({
  origin: (origin, callback) => {
    if (!origin || allowedOrigins.includes(origin)) return callback(null, true);
    return callback(new Error('Origin tidak diizinkan oleh kebijakan CORS'));
  },
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
  allowedHeaders: ['Authorization', 'Content-Type', 'Idempotency-Key', 'X-Request-Id', 'X-Wallet-Pin', 'x-request-id', 'x-wallet-pin'],
  maxAge: 86400,
}));
// Make sure Vary: Origin is explicitly handled
app.use((req, res, next) => {
  res.setHeader('Vary', 'Origin');
  next();
});

// M5: enforce max request body size at the edge via Content-Length header.
// Full streaming enforcement is delegated to upstream; this is defense-in-depth.
app.use((req, res, next) => {
  const contentLength = parseInt(req.headers['content-length'] || '0', 10);
  if (contentLength > 2_000_000) {
    return res.status(413).json({
      success: false,
      data: null,
      error: { code: 'PAYLOAD_TOO_LARGE', message: 'Request body melebihi 2 MB', details: {} },
      meta: { request_id: req.id || 'req_unknown', timestamp: new Date().toISOString() },
    });
  }
  next();
});

// M4: block path traversal di proxy routes (defense-in-depth, CB RolesGuard juga proteksi).
const BLOCK_TRAVERSAL = (req, res, next) => {
  if (req.path.includes('..')) {
    return res.status(400).json({
      success: false,
      data: null,
      error: { code: 'BAD_REQUEST', message: 'Path traversal tidak diizinkan', details: {} },
      meta: { request_id: req.id || 'req_unknown', timestamp: new Date().toISOString() },
    });
  }
  next();
};
app.use(securityHeaders);
app.use(auditRequests);
app.use(createRateLimiter({ windowMs: 60_000, limit: 100 }));
// L6: strip semua variant x-user-* / x-forwarded-* headers (defense-in-depth).
// Downstream CB/Wallet TIDAK membaca header ini; proteksi untuk masa depan.
app.use((req, _res, next) => {
  delete req.headers['x-user-id'];
  delete req.headers['x-user-role'];
  delete req.headers['x-user-roles'];
  delete req.headers['x-user-permissions'];
  delete req.headers['x-internal-user'];
  delete req.headers['x-forwarded-user'];
  delete req.headers['x-userid']; // no-hyphen variant
  next();
});

// Handle preflight OPTIONS before proxy
app.options('/api/bank', (req, res) => { res.status(204).end(); });
app.options('/api/wallet', (req, res) => { res.status(204).end(); });

// Parse bodies explicitly to validate size.
// IMPORTANT: body parsers must run AFTER the proxies. http-proxy-middleware
// needs the raw request stream to forward upstream. If express.json() runs
// first, it drains the body and the proxy hangs (30s timeout → 502).
// Proxy to Central Bank
app.use('/api/bank', BLOCK_TRAVERSAL, jwtMiddleware, createProxyMiddleware({
  target: CENTRAL_BANK_URL,
  changeOrigin: true,
  proxyTimeout: 30000,
  on: {
    error: proxyErrorHandler,
    proxyReq: (proxyReq) => proxyReq.setTimeout(30000),
    proxyRes: stripInternalHeaders,
  },
  pathRewrite: (path, req) => {
    // Same v3 quirk: mount path already stripped. Restore /api/v1 prefix
    // that Central-Bank uses.
    const rewritten = '/api/v1' + path;
    return rewritten;
  },
}));

// Proxy to Wallet
app.use('/api/wallet', BLOCK_TRAVERSAL, jwtMiddleware, createProxyMiddleware({
  target: WALLET_URL,
  changeOrigin: true,
  proxyTimeout: 30000,
  on: {
    error: proxyErrorHandler,
    proxyReq: (proxyReq) => proxyReq.setTimeout(30000),
    proxyRes: stripInternalHeaders,
  },
  pathRewrite: (path, req) => {
    // In http-proxy-middleware@3 the path passed in is ALREADY stripped of
    // the mount path. So when request was POST /api/wallet/v1/auth/login,
    // `path` here is just '/v1/auth/login'. Prepend the /api prefix that
    // Wallet expects.
    const rewritten = '/api' + path;
    return rewritten;
  },
}));

// Body parsers must be registered AFTER the proxies so that
// http-proxy-middleware@3 can forward the raw request body upstream.
// Otherwise the body is drained before the proxy reads it and the
// upstream connection waits 30s for content → 502 UPSTREAM_UNAVAILABLE.
app.use(express.json({ limit: '2mb' }));
app.use(express.urlencoded({ limit: '2mb', extended: false }));

// Health check
app.get('/health', (req, res) => {
  res.status(200).json({
    success: true,
    data: { status: 'OK', message: 'API Gateway is running' },
    error: null,
    meta: { request_id: req.id, timestamp: new Date().toISOString() },
  });
});

app.use((err, req, res, _next) => {
  console.error({ timestamp: new Date().toISOString(), request_id: req.id, action: 'gateway_error', error_type: err.name });
  res.status(500).json({
    success: false,
    data: null,
    error: { code: 'INTERNAL_SERVER_ERROR', message: 'Terjadi kesalahan sistem internal', details: {} },
    meta: { request_id: req.id || 'req_unknown', timestamp: new Date().toISOString() },
  });
});

const server = app.listen(PORT, () => {
  console.log(`🚀 API Gateway running on port ${PORT}`);
});

process.on('SIGTERM', () => { server.close(() => process.exit(0)); });
process.on('SIGINT', () => { server.close(() => process.exit(0)); });
