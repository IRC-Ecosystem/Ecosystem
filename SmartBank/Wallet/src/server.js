import app from './app.js';
import { config } from './config/config.js';
import { db } from './config/database.js';
import { runMigrations } from './database/migrate.js';

const PORT = config.port;

async function start() {
  await runMigrations();
  app.listen(PORT, () => {
  console.log(`
  ======================================================
  🏦 SMARTBANK WALLET BACKEND - Tier-2 CBDC Provider
  ======================================================
  🟢 Server Status : ONLINE
  🌐 Local URL    : http://localhost:${PORT}
  🛠️ Environment  : ${config.nodeEnv}
  📦 Database Type: ${db.getDatabaseType()}
  🔒 JWT Status   : SECURED
  Central Bank    : REAL HTTP INTEGRATION
  ======================================================
  `);
  });
}

void start();
