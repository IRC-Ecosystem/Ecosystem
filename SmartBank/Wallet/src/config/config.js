import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
// Load .env next to this file's package root (Wallet/.env) regardless of CWD.
dotenv.config({ path: path.resolve(__dirname, '..', '..', '.env') });
// Fallback to default location if not found.
if (!process.env.JWT_SECRET) {
  dotenv.config();
}

// C1: hard-fail bila JWT_SECRET hilang, terlalu pendek, atau nilai default/ter-leaked.
const KNOWN_BAD_SECRETS = [
  'change-me-for-development',
  'supersecretkey_change_me_2026',
  'laragon_local_dev_secret_min_32_chars_abcdef123456',
  'change_me_jwt_secret_min_32_chars',
];
if (!process.env.JWT_SECRET || process.env.JWT_SECRET.length < 32 || KNOWN_BAD_SECRETS.includes(process.env.JWT_SECRET)) {
  throw new Error('JWT_SECRET wajib di-set, >=32 karakter, dan bukan nilai default/ter-leaked.');
}

export const config = {
  port: parseInt(process.env.PORT || '6969', 10),
  nodeEnv: process.env.NODE_ENV || 'development',
  trustProxy: process.env.TRUST_PROXY === 'true' ? 'loopback' : process.env.TRUST_PROXY || false,
  jwt: {
    secret: process.env.JWT_SECRET,
    accessExpires: parseInt(process.env.JWT_ACCESS_EXPIRES || '3600', 10),
    refreshExpires: parseInt(process.env.JWT_REFRESH_EXPIRES || '604800', 10),
    issuer: process.env.JWT_ISSUER || 'smartbank',
    audience: process.env.JWT_AUDIENCE || 'smartbank-clients',
  },
  db: {
    host: process.env.DB_HOST ?? '127.0.0.1',
    port: parseInt(process.env.DB_PORT ?? '3306', 10),
    user: process.env.DB_USER ?? 'central_bank',
    password: process.env.DB_PASSWORD ?? 'central_bank_password',
    name: process.env.DB_NAME ?? 'central_bank_core',
    useInMemory: process.env.USE_IN_MEMORY_DB === 'true',
  },
  centralBank: {
    url: process.env.CENTRAL_BANK_CORE_URL || 'http://localhost:3000',
    serviceToken: process.env.SERVICE_TOKEN,
  },
  cbdc: {
    cooldownSeconds: parseInt(process.env.COOLDOWN_SECONDS || '10', 10),
    dailyLimitCount: parseInt(process.env.DAILY_LIMIT_COUNT || '10', 10),
    maxTransferPerTx: parseInt(process.env.MAX_TRANSFER_PER_TX || '50000', 10),
  },
  cors: {
    allowedOrigins: process.env.CORS_ALLOWED_ORIGINS
      ? process.env.CORS_ALLOWED_ORIGINS.split(',').map((o) => o.trim())
      : ['http://localhost:3001', 'http://localhost:6969', 'http://localhost:5173'],
  },
};
if (!config.centralBank.serviceToken || config.centralBank.serviceToken.length < 32) throw new Error('SERVICE_TOKEN wajib di-set minimal 32 karakter.');
