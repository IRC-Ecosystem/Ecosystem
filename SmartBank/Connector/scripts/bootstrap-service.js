const bcrypt = require('bcrypt');
const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

const services = [
  ['WARUNGPOS', 'WarungPOS', process.env.BOOTSTRAP_POS_API_KEY],
  ['MARKETPLACE', 'PasarKita', process.env.BOOTSTRAP_MARKETPLACE_API_KEY],
  ['SUPPLIERHUB', 'SupplierHub', process.env.BOOTSTRAP_SUPPLIERHUB_API_KEY],
].filter(([, , apiKey]) => apiKey);

async function main() {
  for (const [serviceName, displayName, apiKey] of services) {
    const service = await prisma.service.upsert({
      where: { service_name: serviceName },
      update: {},
      create: { service_name: serviceName, display_name: displayName },
    });
    const keys = await prisma.serviceApiKey.findMany({ where: { service_id: service.id, status: 'ACTIVE' } });
    const exists = await Promise.any(keys.map((key) => bcrypt.compare(apiKey, key.key_hash))).catch(() => false);
    if (!exists) await prisma.serviceApiKey.create({ data: {
      service_id: service.id,
      key_prefix: apiKey.substring(0, 8),
      key_hash: await bcrypt.hash(apiKey, 12),
      label: 'docker-bootstrap',
    } });
  }
}

main().finally(() => prisma.$disconnect());
