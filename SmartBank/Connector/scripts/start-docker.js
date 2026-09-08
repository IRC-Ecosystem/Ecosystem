const { spawnSync, spawn } = require('child_process');

const migrate = spawnSync(process.execPath, ['scripts/run-prisma.js', 'db', 'push', '--skip-generate'], {
  stdio: 'inherit',
  env: process.env,
});

if (migrate.status !== 0) process.exit(migrate.status || 1);

const bootstrap = spawnSync(process.execPath, ['scripts/bootstrap-service.js'], {
  stdio: 'inherit',
  env: process.env,
});
if (bootstrap.status !== 0) process.exit(bootstrap.status || 1);

const server = spawn(process.execPath, ['dist/server.js'], { stdio: 'inherit', env: process.env });
for (const signal of ['SIGTERM', 'SIGINT']) {
  process.on(signal, () => server.kill(signal));
}
server.on('exit', (code, signal) => {
  if (signal) process.kill(process.pid, signal);
  else process.exit(code ?? 1);
});
