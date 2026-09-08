const fs = require('fs');
const path = require('path');
const { spawnSync } = require('child_process');
const dotenv = require('dotenv');

const connectorRoot = path.resolve(__dirname, '..');
dotenv.config({ path: path.resolve(connectorRoot, '../.env') });
dotenv.config({ path: path.join(connectorRoot, '.env.local'), override: true });

function findMysqlClient() {
  if (process.env.MYSQL_BIN && fs.existsSync(process.env.MYSQL_BIN)) {
    return process.env.MYSQL_BIN;
  }

  const laragonMysqlRoot = 'C:/laragon/bin/mysql';
  if (fs.existsSync(laragonMysqlRoot)) {
    for (const directory of fs.readdirSync(laragonMysqlRoot).sort().reverse()) {
      const candidate = path.join(laragonMysqlRoot, directory, 'bin', 'mysql.exe');
      if (fs.existsSync(candidate)) return candidate;
    }
  }

  return 'mysql';
}

const user = process.env.MYSQL_USER;
const password = process.env.MYSQL_PASSWORD;
const rootPassword = process.env.MYSQL_ROOT_PASSWORD;
const host = process.env.CONNECTOR_DATABASE_HOST ?? '127.0.0.1';
const port = process.env.CONNECTOR_DATABASE_PORT ?? '3306';
const database = process.env.CONNECTOR_DATABASE_NAME ?? 'connector_db';

if (!user || !/^[A-Za-z0-9_]+$/.test(user) || password === undefined) {
  console.error('MYSQL_USER or MYSQL_PASSWORD in ../.env is missing or invalid.');
  process.exit(1);
}
if (!/^[A-Za-z0-9_]+$/.test(database)) {
  console.error('CONNECTOR_DATABASE_NAME must contain only letters, numbers, or underscores.');
  process.exit(1);
}

const quote = (value) => value.replaceAll("'", "''");
const sql = [
  `CREATE DATABASE IF NOT EXISTS \`${database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`,
  `CREATE USER IF NOT EXISTS '${quote(user)}'@'${quote(host)}' IDENTIFIED WITH mysql_native_password BY '${quote(password)}'`,
  `ALTER USER '${quote(user)}'@'${quote(host)}' IDENTIFIED WITH mysql_native_password BY '${quote(password)}'`,
  `GRANT ALL PRIVILEGES ON \`${database}\`.* TO '${quote(user)}'@'${quote(host)}'`,
  'FLUSH PRIVILEGES',
].join('; ') + ';';

const mysql = findMysqlClient();
const args = ['-h', host, '-P', port, '-u', 'root', '--execute', sql];

function execute(extraEnv = {}) {
  return spawnSync(mysql, args, {
    cwd: connectorRoot,
    env: { ...process.env, ...extraEnv },
    stdio: 'inherit',
  });
}

let result = execute();
if (result.status !== 0 && rootPassword) {
  result = execute({ MYSQL_PWD: rootPassword });
}

if (result.error) {
  console.error(`Unable to run MySQL client: ${result.error.message}`);
  process.exit(1);
}
if (result.status !== 0) process.exit(result.status ?? 1);

console.log(`Local database ${database} and user ${user} are ready at ${host}:${port}.`);
