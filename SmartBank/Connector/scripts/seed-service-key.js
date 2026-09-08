const path = require('path');
const crypto = require('crypto');
const bcrypt = require('bcrypt');
const dotenv = require('dotenv');
const { PrismaClient } = require('@prisma/client');

const connectorRoot = path.resolve(__dirname, '..');
const workspaceRoot = path.resolve(connectorRoot, '..');

dotenv.config({ path: path.join(workspaceRoot, '.env') });
dotenv.config({ path: path.join(connectorRoot, '.env'), override: true });
dotenv.config({ path: path.join(connectorRoot, '.env.local'), override: true });

if (!process.env.DATABASE_URL) {
  const user = process.env.MYSQL_USER;
  const password = process.env.MYSQL_PASSWORD;
  const host = process.env.CONNECTOR_DATABASE_HOST ?? '127.0.0.1';
  const port = process.env.CONNECTOR_DATABASE_PORT ?? '3306';
  const database = process.env.CONNECTOR_DATABASE_NAME ?? 'connector_db';

  if (user && password !== undefined) {
    process.env.DATABASE_URL = `mysql://${encodeURIComponent(user)}:${encodeURIComponent(password)}@${host}:${port}/${database}`;
  }
}

if (!process.env.DATABASE_URL) {
  console.error('DATABASE_URL is missing. Run npm run db:setup or set CONNECTOR_DATABASE_URL first.');
  process.exit(1);
}

const prisma = new PrismaClient();

const serviceName = (process.argv[2] || 'MARKETPLACE').trim().toUpperCase();
const displayName = process.argv[3] || serviceName;
const label = process.argv[4] || 'local-dev';

async function main() {
  const plainKey = `sbk_${crypto.randomBytes(32).toString('hex')}`;
  const keyPrefix = plainKey.substring(0, 8);
  const keyHash = await bcrypt.hash(plainKey, 10);

  const service = await prisma.service.upsert({
    where: { service_name: serviceName },
    create: {
      service_name: serviceName,
      display_name: displayName,
    },
    update: {
      display_name: displayName,
      status: 'ACTIVE',
    },
  });

  await prisma.serviceApiKey.create({
    data: {
      service_id: service.id,
      key_prefix: keyPrefix,
      key_hash: keyHash,
      label,
    },
  });

  console.log(`Service ${serviceName} is ready.`);
  console.log(`API key: ${plainKey}`);
  console.log('Store this key in the sister app. It cannot be recovered later.');
}

main()
  .catch((error) => {
    console.error(error);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
