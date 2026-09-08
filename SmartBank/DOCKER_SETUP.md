# 🐳 SmartBank Docker Setup Guide

Panduan lengkap deploy SmartBank CBDC stack via Docker Compose — untuk **local testing** dan **production hosting**.

> Stack: MySQL 8 + Central-Bank (NestJS) + Wallet (Express) + Gateway (Express) + Frontend (Next.js)

---

## 📑 Daftar Isi

- [Arsitektur & Port](#-arsitektur--port)
- [Prasyarat](#-prasyarat)
- [Quick Start (5 menit)](#-quick-start-5-menit)
- [Environment Variables](#-environment-variables)
- [Service Detail](#-service-detail)
  - [MySQL](#mysql)
  - [Central-Bank](#central-bank)
  - [Wallet](#wallet)
  - [Gateway](#gateway)
  - [Frontend](#frontend)
- [Prisma Migration di Docker](#-prisma-migration-di-docker)
- [Command Reference](#-command-reference)
- [Troubleshooting](#-troubleshooting)
- [Production Hardening](#-production-hardening)

---

## 🏗 Arsitektur & Port

```
┌─────────────────────────────────────────────────────────┐
│  Host (laptop / VPS)                                    │
│                                                         │
│  Browser ──► :3001 Frontend (Next.js)                   │
│                   │                                     │
│                   ▼                                     │
│              :4000 Gateway (Express) ◄── single entry   │
│                   │                                     │
│          ┌────────┴────────┐                            │
│          ▼                 ▼                            │
│   :3000 Central-Bank   :6969 Wallet                     │
│   (NestJS+Prisma)      (Express+mysql2)                 │
│          │                 │                            │
│          └────────┬────────┘                            │
│                   ▼                                     │
│              :3301 MySQL 8.0  ◄── data layer            │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

| Service       | Tech              | Container Port | Host Port | Network |
|---------------|-------------------|----------------|-----------|---------|
| MySQL         | mysql:8.0         | 3301           | 3301      | internal |
| Central-Bank  | NestJS + Prisma   | 3000           | 3000      | internal |
| Wallet        | Express + mysql2  | 6969           | 6969      | internal |
| Gateway       | Express           | 4000           | 4000      | internal |
| Frontend      | Next.js 14        | 3001           | 3001      | public  |

Internal services (MySQL, Central-Bank, Wallet, Gateway) hanya expose di `smartbank` Docker network. Frontend expose ke host karena diakses browser.

---

## 📋 Prasyarat

- **Docker Desktop** (Windows/Mac) atau **Docker Engine + Compose v2** (Linux)
  - Minimum: Docker 24+, Compose v2.20+
  - Test: `docker --version && docker compose version`
- **Port tersedia di host**: `3000`, `3001`, `3301`, `4000`, `6969`
  - Cek: `netstat -an | grep -E "3000|3001|3301|4000|6969"` (Win: `netstat -an | findstr ...`)
- **RAM minimum 4 GB** (MySQL + 4 Node services)
- **CPU 2 core** direkomendasikan (cold start compose ~30-60 detik)

---

## 🚀 Quick Start (5 menit)

### 1. Clone & masuk folder

```bash
cd C:\CODING\IRC\SmartBank   # atau path Anda
```

### 2. Copy env template

```bash
cp .env.example .env
```

Edit `.env` di text editor. **Minimum yang WAJIB diisi** (lihat section [Environment Variables](#-environment-variables) untuk lengkap):

```env
MYSQL_ROOT_PASSWORD=root_password_anda_min_8_chars
MYSQL_USER=central_bank
MYSQL_PASSWORD=db_password_anda_min_8_chars
JWT_SECRET=jwt_secret_anda_min_32_chars_random
```

Generate JWT secret aman:

```bash
# PowerShell
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 48 | % {[char]$_})
```

### 3. Build & start semua service

```bash
docker compose up -d --build
```

Output yang diharapkan:

```
[+] Running 6/6
 ✔ Network smartbank_smartbank      Created
 ✔ Volume smartbank_mysql_data      Created
 ✔ Container smartbank-mysql        Started
 ✔ Container smartbank-central-bank Started
 ✔ Container smartbank-wallet       Started
 ✔ Container smartbank-gateway      Started
 ✔ Container smartbank-frontend     Started
```

### 4. Tunggu healthcheck pass (~30-60 detik)

```bash
docker compose ps
```

Semua harus `STATUS: Up (healthy)`. Tunggu sampai Central-Bank siap — **ini waktu Prisma migration + seed berjalan**.

### 5. Verifikasi

```bash
# Cek health endpoint
curl http://localhost:4000/health
# → {"status":"ok"}

# Cek central-bank
curl http://localhost:3000/api/v1/health
# → {"status":"ok","service":"central-bank-core"}

# Buka frontend di browser
start http://localhost:3001
```

### 6. Login dengan akun dummy

| Role   | Email                | Password |
|--------|----------------------|----------|
| Teller | `teller@test.com`    | `password` |
| Manager| `manager@test.com`   | `password` |
| Admin  | `admin@test.com`     | `password` |

User retail harus register manual via UI `/register`.

---

## 🔐 Environment Variables

Semua env didefinisikan di `.env` di root. Docker Compose baca via `${VAR}` syntax.

### Wajib (minimum)

```env
# MySQL credentials
MYSQL_ROOT_PASSWORD=<password>
MYSQL_USER=central_bank
MYSQL_PASSWORD=<password>

# JWT (HARUS SAMA untuk Central-Bank + Wallet + Gateway!)
JWT_SECRET=<min-32-chars-random>
JWT_ISSUER=smartbank
JWT_AUDIENCE=smartbank-clients
```

### Opsional (ada default)

```env
# Database
MYSQL_DATABASE=central_bank_core

# Central-Bank monetary policy
CB_TOTAL_MONEY_SUPPLY=1000000000
CB_INITIAL_USER_BALANCE=50000
CB_RESERVE_MINIMUM_BPS=9800

# Wallet
WALLET_JWT_ACCESS_EXPIRES=3600
WALLET_COOLDOWN_SECONDS=10
ENABLE_STAFF_SEED=true

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3001,http://localhost:4000,http://localhost:6969
NEXT_PUBLIC_API_BASE_URL=http://localhost:4000
```

---

## 🔍 Service Detail

### MySQL

```yaml
image: mysql:8.0
command: --default-authentication-plugin=mysql_native_password --port=3301
ports: ["3301:3301"]
volumes: [mysql_data:/var/lib/mysql]
```

- **Volume `mysql_data`** persist data antar restart container
- **Port 3301** (bukan default 3306) — untuk hindari konflik Laragon/XAMPP lokal
- **Healthcheck** = `mysqladmin ping` setiap 5s, max 20 retry
- Akses dari host: `mysql -h 127.0.0.1 -P 3301 -u central_bank -p central_bank_core`

### Central-Bank

```yaml
build: ./Central-Bank
DATABASE_URL: mysql://central_bank:${MYSQL_PASSWORD}@mysql:3301/central_bank_core
```

- **Multi-stage Dockerfile**: build → runtime (alpine, node user)
- **CMD**: `prisma migrate deploy && seed.js && node main.js`
- Lihat [Prisma Migration di Docker](#-prisma-migration-di-docker) untuk detail flow

### Wallet

```yaml
build: ./Wallet
DB_HOST: mysql
DB_PORT: 3301
USE_IN_MEMORY_DB: "false"
```

- **Raw mysql2** (bukan Prisma)
- `USE_IN_MEMORY_DB: "false"` = pakai MySQL. Set `"true"` untuk test tanpa DB
- `MOCK_CENTRAL_BANK: "false"` = call real Central-Bank. Set `"true"` untuk test

### Gateway

```yaml
build: ./Gateway
CENTRAL_BANK_URL: http://central-bank:3000
WALLET_URL: http://wallet:6969
```

- Stateless proxy, tidak ada DB
- Rate limiting, CORS validation, request ID

### Frontend

```yaml
build: ./frontend
NEXT_PUBLIC_API_BASE_URL: ${NEXT_PUBLIC_API_BASE_URL:-http://localhost:4000}
```

- Next.js production build, dijalankan sebagai standalone server
- `NEXT_PUBLIC_API_BASE_URL` di-bake saat build (NEXT_PUBLIC_ prefix = exposed to browser)

---

## 🗄 Prisma Migration di Docker

Central-Bank adalah satu-satunya service yang pakai Prisma. Migration flow di Docker:

```
1. docker compose up -d mysql
   → mysql container start, healthcheck pass (~10-20s)

2. docker compose up -d central-bank
   → build stage sudah generate Prisma client di image
   → runtime CMD:
     a) prisma migrate deploy    → apply 11 migrations ke MySQL
     b) node dist-seed/seed.js   → seed system accounts (CENTRAL_RESERVE dll)
     c) exec node dist/main.js   → start NestJS app

3. healthcheck pass → service_healthy
   → wallet/gateway/frontend mulai start (depends_on)
```

### Monitoring Migration

```bash
# Lihat log Prisma saat boot
docker compose logs -f central-bank | grep -iE "migration|seed|prisma"

# Cek status di dalam container
docker compose exec central-bank npx prisma migrate status

# Lihat tabel yang sudah dibuat
docker compose exec mysql mysql -u central_bank -p central_bank_core -e "SHOW TABLES;"

# Expected tables (setelah init migration):
# users, wallet_accounts, monetary_policy_events, transactions,
# ledger_entries, payment_requests, fee_rules, loans,
# audit_logs, idempotency_keys, _prisma_migrations
```

### Tambah Migration Baru (dev workflow)

```bash
# 1. Edit schema.prisma di Central-Bank/prisma/

# 2. Generate migration file (otomatis deteksi diff)
docker compose exec central-bank npx prisma migrate dev --name add_new_feature
# → creates prisma/migrations/<timestamp>_add_new_feature/migration.sql

# 3. Rebuild image (karena migrations di-COPY ke image saat build)
docker compose build central-bank
docker compose up -d central-bank
# → CMD akan apply migration baru via migrate deploy
```

### Reset Database (CAUTION: drops all data)

```bash
# Option A: drop volume, recreate
docker compose down -v
docker compose up -d --build

# Option B: in-place reset (jaga image)
docker compose exec central-bank npx prisma migrate reset --force
# → drop all tables, re-apply all migrations, run seed
```

---

## 📋 Command Reference

### Lifecycle

```bash
docker compose up -d --build      # Build image + start semua
docker compose up -d              # Start tanpa rebuild
docker compose down               # Stop semua container
docker compose down -v            # Stop + hapus volume (DATA HILANG)
docker compose restart <service>  # Restart 1 service
docker compose pull               # Pull image terbaru (MySQL)
```

### Logs

```bash
docker compose logs -f                          # Semua service, follow
docker compose logs -f central-bank             # 1 service
docker compose logs --tail 100 central-bank     # Last 100 lines
docker compose logs -f central-bank 2>&1 | grep -i error
```

### Exec ke Container

```bash
# Central-Bank shell
docker compose exec central-bank sh

# MySQL CLI
docker compose exec mysql mysql -u central_bank -p central_bank_core

# Prisma operations
docker compose exec central-bank npx prisma studio   # buka GUI di :5555
docker compose exec central-bank npx prisma migrate status
```

### Rebuild Specific Service

```bash
docker compose build central-bank --no-cache
docker compose up -d central-bank
```

### Resource Usage

```bash
docker stats                    # Live CPU/RAM per container
docker system df                # Disk usage
docker system prune -a          # Cleanup unused (HATI-HATI)
```

---

## 🔧 Troubleshooting

### MySQL port 3301 sudah dipakai

```
Error: bind: address already in use
```

Fix: edit `docker-compose.yml:15` ganti `"3301:3301"` ke port lain (mis. `"3307:3301"`), lalu update `DATABASE_URL` di Central-Bank & Wallet env.

### Migration gagal di boot

```
prisma:migrate ERROR: P3009
migrate found failed migrations in the target database
```

Fix:

```bash
# Resolve manual
docker compose exec central-bank npx prisma migrate resolve --rolled-back <migration_name>
docker compose restart central-bank

# Atau full reset (DATA HILANG)
docker compose down -v
docker compose up -d --build
```

### Seed error: `ER_DUP_ENTRY` di restart kedua

`seed.ts` `upsertFeeRules` belum fully idempotent. Workaround:

```bash
# Skip seed (jalan manual setelah container up)
docker compose exec central-bank node dist/main.js  # skip seed

# Atau reset total
docker compose down -v
docker compose up -d --build
```

Fix permanen: edit `Central-Bank/prisma/seed.ts` line 117+ untuk pakai `upsert` bukan `create`.

### Frontend tidak bisa hit Gateway

```
Network Error / 502 Bad Gateway
```

Cek:

```bash
docker compose ps gateway                  # status healthy?
docker compose logs gateway | tail -50     # error log
curl http://localhost:4000/health          # test dari host
```

Pastikan `NEXT_PUBLIC_API_BASE_URL` di `.env` benar (bukan `http://gateway:4000` — itu nama internal Docker, browser host tidak bisa resolve).

### Container restart loop

```bash
docker compose logs --tail 200 central-bank
```

Cari `Error:` atau `EADDRINUSE`. Biasanya konflik port atau env var salah.

### Out of disk space

```bash
docker system df                    # lihat usage
docker system prune -a --volumes    # HATI-HATI: hapus semua unused
```

### Reset total (factory reset)

```bash
docker compose down -v              # hapus containers + volumes
docker compose up -d --build        # rebuild dari nol
```

---

## 🔒 Production Hardening

> ⚠️ Konfigurasi di `docker-compose.yml` saat ini cocok untuk **demo & staging**. Untuk production, tambahkan:

### 1. Secrets Management

Jangan commit `.env` ke git. Gunakan Docker secrets atau vault:

```yaml
services:
  central-bank:
    secrets:
      - jwt_secret
      - db_password

secrets:
  jwt_secret:
    external: true
  db_password:
    external: true
```

### 2. HTTPS / Reverse Proxy

Tambah Nginx/Traefik di depan frontend:

```yaml
services:
  nginx:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./certs:/etc/nginx/certs
    depends_on: [frontend]
```

### 3. Database Backup

```bash
# Manual backup
docker compose exec mysql mysqldump -u root -p central_bank_core > backup_$(date +%F).sql

# Restore
cat backup_2026-06-23.sql | docker compose exec -T mysql mysql -u root -p central_bank_core
```

Cron backup harian recommended untuk production.

### 4. Resource Limits

Tambah di setiap service:

```yaml
services:
  central-bank:
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          cpus: '0.25'
          memory: 128M
```

### 5. Logging

Default `json-file` driver isi disk cepat. Switch ke `json-file` dengan rotation atau external driver:

```yaml
services:
  central-bank:
    logging:
      driver: "json-file"
      options:
        max-size: "10m"
        max-file: "3"
```

### 6. Healthcheck di Orchestrator

Untuk Kubernetes/Swarm, expose health endpoint:
- Central-Bank: `GET /api/v1/health`
- Gateway: `GET /health`
- Frontend: `GET /login` (200 OK)

### 7. Image Registry

Push image ke registry (Docker Hub, GHCR, ECR):

```bash
docker tag smartbank-central-bank:latest your-registry/smartbank-central-bank:v1.0
docker push your-registry/smartbank-central-bank:v1.0
```

Update `docker-compose.yml` ke `image: your-registry/...` bukan `build: ./...`.

---

## 📞 Support

- Issues: cek log dulu (`docker compose logs`)
- Reset: `docker compose down -v && docker compose up -d --build`
- Update: pull latest, rebuild image, restart

Lihat juga:
- [`README.md`](./README.md) — overview proyek
- [`Central-Bank/README.md`](./Central-Bank/README.md) — Prisma migration detail
- [`dokumentasi-lokal.md`](./dokumentasi-lokal.md) — setup tanpa Docker (Laragon)
