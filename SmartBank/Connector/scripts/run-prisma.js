const { spawnSync } = require('child_process');
const path = require('path');
const dotenv = require('dotenv');

const connectorRoot = path.resolve(__dirname, '..');
const workspaceRoot = path.resolve(connectorRoot, '..');

dotenv.config({ path: path.join(workspaceRoot, '.env') });
dotenv.config({ path: path.join(connectorRoot, '.env'), override: true });
dotenv.config({ path: path.join(connectorRoot, '.env.local'), override: true });

function databaseUrlFromGlobalMysql() {
  const user = process.env.MYSQL_USER;
  const password = process.env.MYSQL_PASSWORD;
  if (!user || password === undefined) return undefined;

  const host = process.env.CONNECTOR_DATABASE_HOST ?? '127.0.0.1';
  const port = process.env.CONNECTOR_DATABASE_PORT ?? '3306';
  const database = process.env.CONNECTOR_DATABASE_NAME ?? 'connector_db';
  return `mysql://${encodeURIComponent(user)}:${encodeURIComponent(password)}@${host}:${port}/${database}`;
}

process.env.DATABASE_URL =
  process.env.CONNECTOR_DATABASE_URL ??
  databaseUrlFromGlobalMysql() ??
  process.env.DATABASE_URL;

if (!process.env.DATABASE_URL) {
  console.error(
    'DATABASE_URL is missing. Set CONNECTOR_DATABASE_URL in ../.env or .env.local.'
  );
  process.exit(1);
}

const prismaCli = require.resolve('prisma/build/index.js');
const result = spawnSync(process.execPath, [prismaCli, ...process.argv.slice(2)], {
  cwd: connectorRoot,
  env: process.env,
  stdio: 'inherit',
});

if (result.error) {
  console.error(result.error.message);
  process.exit(1);
}

process.exit(result.status ?? 1);
