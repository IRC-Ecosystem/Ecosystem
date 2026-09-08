import { z } from 'zod';
import dotenv from 'dotenv';
import path from 'path';

const connectorRoot = path.resolve(__dirname, '../..');
const workspaceRoot = path.resolve(connectorRoot, '..');

// Load shared values first, then Connector-specific values. Existing process
// variables (for example those injected by Docker) always take precedence.
dotenv.config({ path: path.join(workspaceRoot, '.env') });
dotenv.config({ path: path.join(connectorRoot, '.env'), override: true });
dotenv.config({ path: path.join(connectorRoot, '.env.local'), override: true });

function databaseUrlFromGlobalMysql(): string | undefined {
  const user = process.env.MYSQL_USER;
  const password = process.env.MYSQL_PASSWORD;
  if (!user || password === undefined) return undefined;

  const host = process.env.CONNECTOR_DATABASE_HOST ?? '127.0.0.1';
  const port = process.env.CONNECTOR_DATABASE_PORT ?? '3306';
  const database = process.env.CONNECTOR_DATABASE_NAME ?? 'connector_db';
  return `mysql://${encodeURIComponent(user)}:${encodeURIComponent(password)}@${host}:${port}/${database}`;
}

const resolvedDatabaseUrl =
  process.env.CONNECTOR_DATABASE_URL ??
  databaseUrlFromGlobalMysql() ??
  process.env.DATABASE_URL;

// Prisma Client reads DATABASE_URL directly from process.env at runtime.
if (resolvedDatabaseUrl) process.env.DATABASE_URL = resolvedDatabaseUrl;

const envSchema = z.object({
  PORT: z.string().default('5000'),
  NODE_ENV: z.string().default('development'),
  DATABASE_URL: z.string().min(1),
  CENTRAL_BANK_URL: z.string().min(1),
  CENTRAL_BANK_SERVICE_TOKEN: z.string().min(32),
  JWT_SECRET: z.string().min(32),
  RATE_LIMIT_DEFAULT_RPS: z.string().default('50'),
  ADMIN_API_KEY: z.string().min(32),
  AUDIT_HMAC_SECRET: z.string().min(32),
  ADMIN_ALLOWED_IPS: z.string().default('127.0.0.1,::1,172.*'),
});

export const env = envSchema.parse({
  PORT: process.env.CONNECTOR_PORT ?? process.env.PORT,
  NODE_ENV: process.env.CONNECTOR_NODE_ENV ?? process.env.NODE_ENV,
  DATABASE_URL: resolvedDatabaseUrl,
  CENTRAL_BANK_URL: process.env.CONNECTOR_CENTRAL_BANK_URL ?? process.env.CENTRAL_BANK_URL,
  CENTRAL_BANK_SERVICE_TOKEN:
    process.env.CONNECTOR_SERVICE_TOKEN ??
    process.env.SERVICE_TOKEN ??
    process.env.CENTRAL_BANK_SERVICE_TOKEN,
  JWT_SECRET: process.env.CONNECTOR_JWT_SECRET ?? process.env.JWT_SECRET,
  RATE_LIMIT_DEFAULT_RPS:
    process.env.CONNECTOR_RATE_LIMIT_DEFAULT_RPS ?? process.env.RATE_LIMIT_DEFAULT_RPS,
  ADMIN_API_KEY: process.env.CONNECTOR_ADMIN_KEY ?? process.env.ADMIN_API_KEY,
  AUDIT_HMAC_SECRET: process.env.CONNECTOR_AUDIT_HMAC_SECRET ?? process.env.AUDIT_HMAC_SECRET,
  ADMIN_ALLOWED_IPS: process.env.CONNECTOR_ADMIN_ALLOWED_IPS ?? process.env.ADMIN_ALLOWED_IPS,
});
