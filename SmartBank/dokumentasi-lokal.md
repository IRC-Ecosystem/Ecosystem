# Tutorial Menjalankan SmartBank di Lokal (Laragon + Terminal)

> Panduan lengkap untuk menjalankan SmartBank CBDC Ecosystem secara lokal menggunakan **Laragon** (MySQL) + terminal, **tanpa Docker**.

---

## Daftar Isi

1. [Arsitektur Lokal](#arsitektur-lokal)
2. [Prasyarat](#prasyarat)
3. [Langkah 0 — Install Dependencies](#langkah-0--install-dependencies)
4. [Langkah 1 — Konfigurasi Environment](#langkah-1--konfigurasi-environment)
5. [Langkah 2 — Buat Database MySQL](#langkah-2--buat-database-mysql)
6. [Langkah 3 — Migrate + Seed Central-Bank](#langkah-3--migrate--seed-central-bank)
7. [Langkah 4 — Buat Tabel Cache Wallet](#langkah-4--buat-tabel-cache-wallet)
8. [Langkah 5 — Jalankan 4 Services](#langkah-5--jalankan-4-services)
9. [Langkah 6 — Smoke Test API](#langkah-6--smoke-test-api)
10. [Langkah 7 — Akses Frontend](#langkah-7--akses-frontend)
11. [Fitur Baru di Branch Ini](#fitur-baru-di-branch-ini)
12. [Troubleshooting](#troubleshooting)
13. [Reset Environment](#reset-environment)

---

## Arsitektur Lokal

```
┌──────────────────────────────────────────────────────────────────┐
│ Browser: http://localhost:3001                                   │
└────────────────────┬─────────────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│ Frontend (Next.js 16 + Turbopack)     PORT 3001                  │
│  - Landing page (3D asset, glassmorphism)                        │
│  - Retail / Teller / Manager / Admin dashboards                  │
└────────────────────┬─────────────────────────────────────────────┘
                     │ fetch NEXT_PUBLIC_API_BASE_URL
                     ▼
┌──────────────────────────────────────────────────────────────────┐
│ API Gateway (Express + http-proxy-middleware)  PORT 4000         │
│   /api/bank   →  Central-Bank                                    │
│   /api/wallet →  Wallet                                          │
└─────┬─────────────────────────────────────┬──────────────────────┘
      │                                     │
      ▼                                     ▼
┌──────────────────────────┐    ┌──────────────────────────────────┐
│ Central-Bank Core        │    │ Wallet (Express + mysql2)        │
│ (NestJS + Prisma)        │    │  PORT 6969                       │
│  PORT 3000               │    │                                  │
│  Modules:                │    └──────┬───────────────────────────┘
│   - central-bank         │           │
│   - settlement           │           │
│   - fees (CRUD)          │           │
│   - audit (log)          │           │
│   - algorithms           │           │
│     (BFS/DFS/KMP/Greedy) │           │
└──────┬───────────────────┘           │
       │                               │
       │  MySQL 127.0.0.1:3306         │  MySQL 127.0.0.1:3306
       │  database: central_bank_core  │  (tabel cache tambahan)
       ▼                               ▼
┌──────────────────────────────────────────────────────────────────┐
│ Laragon MySQL 8.x (root, tanpa password)                         │
└──────────────────────────────────────────────────────────────────┘
```

Empat service yang harus jalan bersamaan:

| # | Service      | Port | Path                | Command           |
|---|--------------|------|---------------------|-------------------|
| 1 | Central-Bank | 3000 | `Central-Bank/`     | `pnpm start:dev`  |
| 2 | Wallet       | 6969 | `Wallet/`           | `pnpm dev`        |
| 3 | Gateway      | 4000 | `Gateway/`          | `pnpm dev`        |
| 4 | Frontend     | 3001 | `frontend/`         | `pnpm dev`        |

---

## Prasyarat

Pastikan tools berikut sudah ter-install:

| Tool          | Versi Minimum | Cara Cek         | Cara Install                                                                                  |
|---------------|---------------|------------------|------------------------------------------------------------------------------------------------|
| **Node.js**   | 20.x          | `node -v`        | Download dari [nodejs.org](https://nodejs.org)                                                  |
| **pnpm**      | 10.x          | `pnpm -v`        | `npm i -g pnpm`                                                                                 |
| **Laragon**   | 6+            | Buka Laragon     | Download dari [laragon.org](https://laragon.org/download/)                                      |
| **MySQL**     | 8.x           | via Laragon      | Otomatis ter-install via Laragon (klik **Start All** di Laragon)                                |
| **Git Bash**  | -             | `bash --version` | Sudah ada di Windows 10/11 atau via Laragon Terminal                                           |

### Rekomendasi Hardware (untuk Frontend dengan 3D WebGL)

| Resource | Minimum | Rekomendasi |
|---|---|---|
| RAM | 8 GB | 16 GB |
| GPU | Integrated | Diskrit (untuk three.js / WebGL 3D) |
| Disk | 5 GB free | 10 GB free |

### Verifikasi awal (wajib)

Buka terminal Laragon / Git Bash, jalankan:

```bash
node -v
pnpm -v
mysql --version
```

Lalu buka **Laragon → Start All**. Pastikan:
- Apache : hijau
- **MySQL : hijau** ← ini wajib

Cek MySQL hidup di `127.0.0.1:3306`:

```bash
mysql -u root -e "SELECT VERSION();" 2>&1
```

Laragon default: `root` user, **tanpa password**. Kalau MySQL Laragon pernah diset password, sesuaikan `DB_PASSWORD` di file `.env`.

---

## Langkah 0 — Install Dependencies

Buka terminal (Git Bash / Laragon Terminal), lalu:

```bash
cd "C:/CODING/RPL 2/SmartBank"

# Install di tiap service
cd Central-Bank && pnpm install && cd ..
cd Wallet       && pnpm install && cd ..
cd Gateway      && pnpm install && cd ..
cd frontend     && pnpm install && cd ..

echo "✅ Install selesai"
```

Kalau ada service yang sebelumnya pernah di-install dengan `npm`, boleh hapus `node_modules` dan `package-lock.json` di service itu, lalu ulangi `pnpm install`.

### Catatan: Frontend pakai WebGL 3D

Frontend sekarang mengandung **three.js** + `@react-three/fiber` + `@react-three/drei` (untuk aset 3D landing page). Ini otomatis ter-install via `pnpm install`. Build size bertambah ~500KB — normal.

---

## Langkah 1 — Konfigurasi Environment

Setiap service punya file `.env` di root foldernya. Buat / edit sesuai panduan di bawah.

### 1.1 `Central-Bank/.env`

```env
# Database
DATABASE_URL=mysql://root:@127.0.0.1:3306/central_bank_core

# Server
PORT=3000
NODE_ENV=development

# JWT (HARUS SAMA dengan Wallet & Gateway!)
JWT_SECRET=<GANTI_DENGAN_SECRET_RANDOM_MIN_32_CHAR_di_file_.env_lokal>
JWT_ISSUER=smartbank
JWT_AUDIENCE=smartbank-clients

# Monetary policy
TOTAL_MONEY_SUPPLY=1000000000
INITIAL_USER_BALANCE=50000

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3001,http://localhost:5173,http://localhost:6969
```

### 1.2 `Wallet/.env`

```env
# Server
PORT=6969
NODE_ENV=development

# JWT (sama dengan Central-Bank & Gateway)
JWT_SECRET=<GANTI_DENGAN_SECRET_RANDOM_MIN_32_CHAR_di_file_.env_lokal>
JWT_ISSUER=smartbank
JWT_AUDIENCE=smartbank-clients
JWT_ACCESS_EXPIRES=3600
JWT_REFRESH_EXPIRES=604800

# Database (Laragon MySQL, root tanpa password)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=central_bank_core

# Central-Bank integration
CENTRAL_BANK_CORE_URL=http://localhost:3000
MOCK_CENTRAL_BANK=false
ENABLE_STAFF_SEED=true

# CBDC policy
COOLDOWN_SECONDS=10
DAILY_LIMIT_COUNT=10
MAX_TRANSFER_PER_TX=50000

# CORS
CORS_ALLOWED_ORIGINS=http://localhost:3001,http://localhost:6969,http://localhost:5173
```

> **Penting:** `MOCK_CENTRAL_BANK=false` artinya Wallet HTTP-call **beneran** ke Central-Bank. Kalau `true`, Wallet pakai in-memory mock (tidak connect DB, fitur terbatas).

### 1.3 `Gateway/.env`

```env
# Server
PORT=4000

# JWT (sama dengan Central-Bank & Wallet)
JWT_SECRET=<GANTI_DENGAN_SECRET_RANDOM_MIN_32_CHAR_di_file_.env_lokal>

# Upstream services
CENTRAL_BANK_URL=http://localhost:3000
WALLET_URL=http://localhost:6969

# CORS — asal frontend
CORS_ALLOWED_ORIGINS=http://localhost:3001,http://localhost:5173

# Network
TRUST_PROXY=false
```

### 1.4 `frontend/.env.local`

```env
NEXT_PUBLIC_API_BASE_URL=http://localhost:4000
```

> `frontend/.env.local` **tidak di-commit** ke git (standar Next.js). Buat manual.

---

## Langkah 2 — Buat Database MySQL

Buka terminal MySQL Laragon (klik kanan Laragon tray → **MySQL → MySQL console**), atau terminal biasa:

```bash
mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS central_bank_core
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SHOW DATABASES;
SQL
```

Harus muncul `central_bank_core` di daftar database.

---

## Langkah 3 — Migrate + Seed Central-Bank

Ada 2 opsi: **Migrate** (kalau ada migration file) atau **db push** (sync schema langsung, tanpa migration file — lebih cepat).

### Opsi A — Prisma Migrate (untuk database fresh)

```bash
cd "C:/CODING/RPL 2/SmartBank/Central-Bank"

# Terapkan semua migration yang ada
npx prisma migrate deploy

# Generate Prisma Client
npx prisma generate
```

### Opsi B — Prisma DB Push (sync schema langsung, lebih cepat)

```bash
cd "C:/CODING/RPL 2/SmartBank/Central-Bank"

# Sync schema.prisma ke database tanpa migration file
npx prisma db push

# Generate Prisma Client
npx prisma generate
```

> **Opsi B cocok** kalau schema sudah berubah (misal ada model `FeeConfiguration` baru) dan kamu belum buat migration file-nya. `db push` akan drop tabel cache yang tidak ada di schema (`wallet_accounts_cache`) — aman karena cuma cache.

### Seed data awal

```bash
node prisma/seed.js
```

Output yang diharapkan:

```
✅ Database migrated successfully
✅ Central reserve account created
✅ Staff seeded: teller@test.com
✅ Staff seeded: manager@test.com
✅ Staff seeded: admin@test.com
```

Verifikasi:

```bash
mysql -u root central_bank_core -e "SELECT id, email, role FROM users;"
```

Harus muncul 3 user staff: `teller@test.com`, `manager@test.com`, `admin@test.com`.

---

## Langkah 4 — Buat Tabel Cache Wallet

Wallet butuh 3 tabel tambahan di database yang sama: `users` (cache), `wallet_accounts_cache`, `loans`. Jalankan SQL berikut:

```bash
mysql -u root central_bank_core <<'SQL'

-- Tabel users (read-cache untuk login lokal Wallet)
CREATE TABLE IF NOT EXISTS users (
  id VARCHAR(36) PRIMARY KEY,
  name VARCHAR(191) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  phone VARCHAR(50) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  pin_hash VARCHAR(255) NULL,
  kyc_tier ENUM('BASIC','VERIFIED') DEFAULT 'BASIC',
  status ENUM('ACTIVE','SUSPENDED') DEFAULT 'ACTIVE',
  role ENUM('WALLET_USER','TELLER','MANAGER','CENTRAL_BANK_ADMIN') NOT NULL DEFAULT 'WALLET_USER',
  created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3),
  updated_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB;

-- Tabel cache wallet account (read-model)
CREATE TABLE IF NOT EXISTS wallet_accounts_cache (
  wallet_id VARCHAR(36) PRIMARY KEY,
  user_id VARCHAR(36) NOT NULL,
  available_balance BIGINT NOT NULL DEFAULT 0,
  hold_balance BIGINT NOT NULL DEFAULT 0,
  currency VARCHAR(10) DEFAULT 'CBDC_IDR',
  updated_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3) ON UPDATE CURRENT_TIMESTAMP(3),
  INDEX idx_user (user_id)
) ENGINE=InnoDB;

-- Tabel loans
CREATE TABLE IF NOT EXISTS loans (
  id VARCHAR(36) PRIMARY KEY,
  wallet_id VARCHAR(36) NOT NULL,
  principal BIGINT NOT NULL,
  total_due BIGINT NOT NULL,
  paid_amount BIGINT DEFAULT 0,
  status ENUM('DISBURSED','PARTIAL_PAID','PAID') DEFAULT 'DISBURSED',
  created_at TIMESTAMP(3) DEFAULT CURRENT_TIMESTAMP(3)
) ENGINE=InnoDB;

SHOW TABLES;
SQL
```

Expected output:

```
+----------------------------+
| Tables_in_central_bank_core |
+----------------------------+
| audit_logs                 |
| idempotency_records        |
| loans                      |
| transactions               |
| users                      |
| wallet_accounts            |
| wallet_accounts_cache      |
+----------------------------+
```

---

## Langkah 5 — Jalankan 4 Services

Buka **4 tab terminal terpisah** (Laragon Terminal / Git Bash / VS Code Terminal). Jalankan **satu service per tab**, dan **jangan tutup tab** sampai selesai testing.

> **Urutan penting:** Central-Bank & Wallet butuh MySQL, jadi pastikan Laragon MySQL hijau dulu. Frontend & Gateway tidak butuh DB langsung.

### Tab 1 — Central-Bank (port 3000)

```bash
cd "C:/CODING/RPL 2/SmartBank/Central-Bank"
pnpm run start:dev
```

Tunggu sampai muncul:

```
[Nest] XXXX  - 06/18/2026, X:XX:XX PM     LOG [NestApplication] Nest application successfully started
Mapped {/api/v1/users/:id/wallet, GET} route
Mapped {/api/v1/wallets/me/balance, GET} route
...
Application is running on: http://localhost:3000/api/v1
```

### Tab 2 — Wallet (port 6969)

```bash
cd "C:/CODING/RPL 2/SmartBank/Wallet"
pnpm run dev
```

Tunggu sampai muncul banner:

```
✅ Wallet connected to MySQL successfully
🏦 SMARTBANK WALLET BACKEND - Tier-2 CBDC Provider
🟢 Server Status : ONLINE
🌐 Local URL    : http://localhost:6969
```

### Tab 3 — Gateway (port 4000)

```bash
cd "C:/CODING/RPL 2/SmartBank/Gateway"
pnpm run dev
```

Tunggu sampai:

```
🚀 API Gateway running on port 4000
```

### Tab 4 — Frontend (port 3001)

```bash
cd "C:/CODING/RPL 2/SmartBank/frontend"

# WAJIB di Windows: naikkan memory limit Node (Turbopack butuh banyak RAM)
NODE_OPTIONS=--max-old-space-size=8192 pnpm run dev
```

> **Penting untuk Windows:** Selalu set `NODE_OPTIONS=--max-old-space-size=8192` sebelum `pnpm dev`. Kalau tidak, Turbopack bisa crash dengan `memory allocation of 16777216 bytes failed` (exit code 3221226505).
>
> PowerShell: `$env:NODE_OPTIONS="--max-old-space-size=8192"`
> CMD: `set NODE_OPTIONS=--max-old-space-size=8192`
> Git Bash: `NODE_OPTIONS=--max-old-space-size=8192 pnpm dev`

Tunggu sampai:

```
▲ Next.js 16.x.x
- Local:        http://localhost:3001
✓ Ready in XXXms
```

---

## Langkah 6 — Smoke Test API

Buka **tab terminal ke-5** (atau pakai Postman / Insomnia).

### 6.1 Health Check Semua Service

```bash
echo "=== Central-Bank (port 3000) ==="
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost:3000/api/v1/health

echo "=== Gateway (port 4000) ==="
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost:4000/health

echo "=== Wallet (port 6969) ==="
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost:6969/

echo "=== Frontend (port 3001) ==="
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://localhost:3001
```

Semua harus **`HTTP 200`**.

### 6.2 E2E Test: Register → Login → Balance → Transaksi

**Register user baru** (user retail akan otomatis dapat wallet + saldo awal 50.000 CBDC):

```bash
curl -X POST http://localhost:4000/api/wallet/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Budi Santoso",
    "email":"budi@test.com",
    "phone":"+6281234567890",
    "password":"rahasia123",
    "pin":"123456"
  }'
```

Expected response:

```json
{
  "success": true,
  "data": {
    "userId": "f10499bb-...",
    "name": "Budi Santoso",
    "email": "budi@test.com",
    "walletId": "144a54e5-...",
    "initialBalance": 50000,
    "role": "WALLET_USER"
  }
}
```

> **Catatan:** Format `phone` harus `+62xxx` (kode negara). Contoh: `+6281234567890`. Kalau pakai `08xxx` akan ditolak dengan error "Format nomor telepon tidak valid".

**Login** dan simpan token ke variabel:

```bash
TOK=$(curl -s -X POST http://localhost:4000/api/wallet/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"budi@test.com","password":"rahasia123"}' \
  | python -c "import sys,json;print(json.load(sys.stdin)['data']['accessToken'])")

echo "Token (first 50 chars): ${TOK:0:50}..."
```

**Cek saldo:**

```bash
curl -H "Authorization: Bearer $TOK" \
  http://localhost:4000/api/wallet/v1/wallets/me/balance
```

**Cek riwayat transaksi:**

```bash
curl -H "Authorization: Bearer $TOK" \
  http://localhost:4000/api/wallet/v1/wallets/me/transactions
```

### 6.3 Test Admin Endpoints (Issuance, Burn, Fee, Audit)

Login sebagai admin dulu:

```bash
ADM=$(curl -s -X POST http://localhost:4000/api/bank/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}' \
  | python -c "import sys,json;print(json.load(sys.stdin)['data']['accessToken'])")

echo "Admin token: ${ADM:0:50}..."
```

**Supply monitor:**

```bash
curl -H "Authorization: Bearer $ADM" \
  http://localhost:4000/api/bank/central-bank/supply
```

**Issuance (cetak CBDC ke wallet):**

```bash
curl -X POST http://localhost:4000/api/bank/central-bank/issuance \
  -H "Authorization: Bearer $ADM" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: test-issuance-001" \
  -d '{
    "target_wallet_id":"<WALLET_ID>",
    "amount":"50000",
    "reason_code":"MONETARY_EXPANSION",
    "note":"Test issuance lokal"
  }'
```

**Burn (musnahkan CBDC):**

```bash
curl -X POST http://localhost:4000/api/bank/central-bank/burn \
  -H "Authorization: Bearer $ADM" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: test-burn-001" \
  -d '{
    "source_wallet_id":"<WALLET_ID>",
    "amount":"10000",
    "reason_code":"MONETARY_CONTRACTION"
  }'
```

**List fee configurations:**

```bash
curl -H "Authorization: Bearer $ADM" \
  http://localhost:4000/api/bank/central-bank/fees
```

**Upsert fee config:**

```bash
curl -X PUT http://localhost:4000/api/bank/central-bank/fees \
  -H "Authorization: Bearer $ADM" \
  -H "Content-Type: application/json" \
  -d '{
    "type":"TRANSFER",
    "mode":"PERCENT",
    "value":"150",
    "min_fee":"500",
    "max_fee":"25000",
    "is_active":true
  }'
```

**Browse audit log:**

```bash
curl -H "Authorization: Bearer $ADM" \
  "http://localhost:4000/api/bank/central-bank/audit-logs?limit=10"
```

### 6.4 Test Algorithm Endpoints (BFS/DFS/KMP/Greedy)

Module algoritma menyediakan 7 endpoint demo di `Central-Bank`:

```bash
# Info semua algoritma
curl http://localhost:3000/api/v1/algorithms/info

# KMP search — cari "admin_correction" di dalam teks
curl -X POST http://localhost:3000/api/v1/algorithms/kmp/search \
  -H "Content-Type: application/json" \
  -d '{"text":"ADMIN_CORRECTION retry then ADMIN_CORRECTION again","pattern":"admin_correction"}'

# KMP prefix table
curl -X POST http://localhost:3000/api/v1/algorithms/kmp/prefix \
  -H "Content-Type: application/json" \
  -d '{"pattern":"ABABC"}'

# BFS trace graf wallet
curl -X POST http://localhost:3000/api/v1/algorithms/graph/trace \
  -H "Content-Type: application/json" \
  -d '{"graph":{"A":["B","C"],"B":["D"],"C":["D"],"D":[]},"start":"A","maxDepth":3}'

# BFS shortest path
curl -X POST http://localhost:3000/api/v1/algorithms/graph/shortest-path \
  -H "Content-Type: application/json" \
  -d '{"graph":{"A":["B","C"],"B":["D"],"C":["D"],"D":[]},"start":"A","target":"D"}'

# DFS find chains A → D
curl -X POST http://localhost:3000/api/v1/algorithms/graph/chains \
  -H "Content-Type: application/json" \
  -d '{"graph":{"A":["B","C"],"B":["D"],"C":["D"],"D":[]},"start":"A","target":"D"}'

# Greedy prioritize
curl -X POST http://localhost:3000/api/v1/algorithms/greedy/prioritize \
  -H "Content-Type: application/json" \
  -d '{"candidates":[
    {"id":"tx-low","amount":1000,"kycTier":"VERIFIED","createdAt":1700000000000,"failedPinCount":0,"isNewWallet":false,"burstTransfer":false},
    {"id":"tx-high","amount":50000000,"kycTier":"BASIC","createdAt":'"$(date +%s)"'000,"failedPinCount":5,"isNewWallet":true,"burstTransfer":true}
  ]}'
```

### 6.5 Test Login Staff (Teller / Manager / Admin)

Staff tidak punya wallet customer, jadi login berhasil tapi **tidak bisa transaksi**:

```bash
# Teller
curl -X POST http://localhost:4000/api/wallet/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"teller@test.com","password":"password"}'

# Manager
curl -X POST http://localhost:4000/api/wallet/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@test.com","password":"password"}'

# Admin
curl -X POST http://localhost:4000/api/wallet/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}'
```

### 6.6 Test Transfer P2P (opsional, butuh 2 user retail)

Daftarkan user kedua, lalu transfer dari user pertama ke user kedua:

```bash
curl -X POST http://localhost:4000/api/wallet/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Siti Aminah",
    "email":"siti@test.com",
    "phone":"+6289876543210",
    "password":"rahasia123",
    "pin":"654321"
  }'

SITI_WALLET=$(curl -s -X POST http://localhost:4000/api/wallet/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"siti@test.com","password":"rahasia123"}' \
  | python -c "import sys,json;print(json.load(sys.stdin)['data']['user']['walletId'])")

curl -X POST http://localhost:4000/api/wallet/v1/transfers \
  -H "Authorization: Bearer $TOK" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: test-tx-001" \
  -d "{
    \"to_wallet_id\":\"$SITI_WALLET\",
    \"amount\":10000,
    \"note\":\"Test transfer Budi ke Siti\"
  }"
```

---

## Langkah 7 — Akses Frontend

Buka browser:

```
http://localhost:3001
```

Anda akan melihat **landing page SmartBank CBDC Ecosystem** yang sudah di-redesign dengan:
- **3D credit card** interaktif (magnetic tilt pada hover)
- **Glassmorphism** + mesh gradient background
- **Kinetic marquee** bottom band (infinite scroll)
- **Magnetic CTA buttons** (narik ke cursor)
- **Stagger animation** saat load

### Akun untuk Testing

| Role     | Email             | Password    | Wallet? | Bisa Transaksi? |
|----------|-------------------|-------------|---------|-----------------|
| Retail   | `budi@test.com`   | `rahasia123` | ✅ Ya (50.000 CBDC) | ✅ |
| Retail   | `siti@test.com`   | `rahasia123` | ✅ Ya (50.000 CBDC) | ✅ |
| Teller   | `teller@test.com` | `password`   | ❌ Tidak | ❌ (dashboard teller) |
| Manager  | `manager@test.com`| `password`   | ❌ Tidak | ❌ (dashboard manager) |
| Admin    | `admin@test.com`  | `password`   | ❌ Tidak | ❌ (dashboard admin) |

> **Login via UI:** klik tombol **Login** di landing page → masukkan email & password. Staff akan diarahkan ke dashboard sesuai role; retail akan diarahkan ke wallet.

---

## Fitur Baru di Branch Ini

Branch `feat/central-bank-wallet-major-updates` menambahkan:

### 1. 6 Route Admin Baru

Login sebagai `admin@test.com` → sidebar kiri punya 7 menu:

| Route | Fitur |
|---|---|
| `/admin` | Admin Dashboard (supply + quick actions + activity pulse) |
| `/admin/issuance` | Cetak CBDC baru ke wallet target |
| `/admin/burn` | Musnahkan CBDC dari wallet ke sink |
| `/admin/reversal` | Balikkan transaksi SETTLED |
| `/admin/fee` | CRUD fee config (FLAT/PERCENT per tx type) |
| `/admin/ledger` | Browse ledger entries (filter account/tx) |
| `/admin/audit` | Browse audit log (search + service filter + expand row) |

### 2. Module Algoritma (Modul Praktikum 2026)

Module baru di `Central-Bank/src/modules/algorithms/`:

| File | Algoritma | Kompleksitas |
|---|---|---|
| `bfs-dfs.service.ts` | BFS + DFS (transaction tracing) | O(V + E) |
| `kmp.service.ts` | KMP (string matching) | O(n + m) |
| `greedy.service.ts` | Greedy (audit priority scoring) | O(n log n) |

**18 unit test** lulus (`test/algorithms.spec.ts`).

### 3. Redesign Frontend

- **Landing page** — hero dengan 3D card, glassmorphism, magnetic CTAs, kinetic marquee, animated mesh background
- **Login & Register** — split-screen dengan 3D card di panel kiri + glassmorphic form di kanan
- **Retail dashboard** — asymmetric bento dengan 3D debit card + magnetic quick actions + chart
- **Admin dashboard** — supply hero dengan animated SVG gauges + quick action grid + activity pulse + invariant detail card
- **AppShell sidebar** — premium dengan active indicator bar + animated icons + breadcrumb topbar

### 4. Database Schema Baru

| Model | Fungsi |
|---|---|
| `FeeConfiguration` | Konfigurasi fee per jenis transaksi (FLAT/PERCENT) |
| `FeeMode` enum | FLAT \| PERCENT |

Plus enum baru di `TransactionType`: `ISSUANCE`, `BURN`. Dan di `MonetaryPolicyEventType`: `ISSUANCE`, `BURN`.

### 5. Dependency Frontend Baru

```json
"@react-three/drei": "^10.7.7",
"@react-three/fiber": "^9.6.1",
"three": "^0.184.0"
```

Dipakai untuk aset 3D landing page (CreditCard3D, WebGL canvas).

---

## Troubleshooting

### ❌ Frontend crash: `memory allocation of 16777216 bytes failed` (exit 3221226505)

Turbopack (Rust) kehabisan memory. Fix:

```bash
# Set memory limit Node sebelum run dev
# PowerShell:
$env:NODE_OPTIONS="--max-old-space-size=8192"
# CMD:
set NODE_OPTIONS=--max-old-space-size=8192
# Git Bash:
NODE_OPTIONS=--max-old-space-size=8192 pnpm dev

# Lalu restart dev server
cd frontend && pnpm dev
```

### ❌ Route admin 404 setelah tambah page baru

Cache `.next` lama. Fix:

```bash
cd "C:/CODING/RPL 2/SmartBank/frontend"
rm -rf .next node_modules/.cache
NODE_OPTIONS=--max-old-space-size=8192 pnpm dev
```

Next.js 16 + Turbopack butuh clean cache saat menambah route baru.

### ❌ MySQL "Access denied for user 'root'@'localhost' (using password: YES)"

Laragon default: `root` tanpa password. Cek:
```bash
mysql -u root -e "SELECT 1;"
```
Kalau butuh password, set di `Central-Bank/.env` → `DATABASE_URL=mysql://root:PASSWORD@127.0.0.1:3306/...` dan `Wallet/.env` → `DB_PASSWORD`.

### ❌ Wallet gagal konek MySQL setelah edit `.env`

Wallet pakai `dotenv` dengan path eksplisit ke `Wallet/.env`. Pastikan path benar. Cek `Wallet/src/config/config.js`:
```js
dotenv.config({ path: path.resolve(__dirname, '..', '..', '.env') });
```

### ❌ "Unknown column 'phone' in 'field list'" saat seed staff

Schema Prisma sudah punya `phone` & `pin_hash` tapi migration belum diterapkan:
```bash
cd Central-Bank
npx prisma migrate deploy
# atau
npx prisma db push
```

### ❌ Prisma error: "Migration failed — database needs reset"

Schema berubah (ada model baru seperti `FeeConfiguration`) tapi migration lama konflik. Fix:

```bash
cd Central-Bank

# Opsi A: Sync schema tanpa migration (cepat, data cache hilang tapi data utama aman)
npx prisma db push

# Opsi B: Bikin migration file tanpa apply (review dulu)
npx prisma migrate dev --create-only --name add_fee_configurations
# Lalu apply manual
npx prisma migrate deploy
```

### ❌ Gateway return 502 UPSTREAM_UNAVAILABLE (timeout 30s)

**Penyebab:** body parser (`express.json()`) jalan sebelum proxy → request body ke-drain. **Sudah diperbaiki** di `Gateway/server.js`. Pastikan edit sudah ter-apply (Gateway pakai `node --watch` jadi auto-reload).

### ❌ Login via Gateway return 404 "Rute tidak ditemukan"

**Penyebab:** `http-proxy-middleware@3` strip mount path sebelum `pathRewrite`. **Sudah diperbaiki** dengan callback form. Cek `Gateway/server.js` line ~107:
```js
pathRewrite: (path, req) => '/api' + path
```

### ❌ Login return 500 TypeError (getWalletByUserId is not a function)

**Sudah diperbaiki** dengan menambah:
- Endpoint `GET /api/v1/users/:id/wallet` di Central-Bank (`wallets.controller.ts`)
- Service token guard (`common/service-token.guard.ts`)
- Method `getWalletByUserId` di `Wallet/src/services/centralBank.service.js`

Pastikan Central-Bank sudah restart setelah edit.

### ❌ Frontend "next is not recognized"

```bash
cd frontend && pnpm install
```

### ❌ Frontend error "Multiple lockfiles"

Warning saja, tidak menggangu. Untuk silence, set di `frontend/next.config.ts`:
```ts
const nextConfig = { turbopack: { root: __dirname } };
```

### ❌ Port sudah dipakai (EADDRINUSE)

```bash
# Windows: cek process di port tertentu
netstat -ano | grep ":3000 " | grep LISTENING
# Kill PID
taskkill /F /PID <PID>
```

### ❌ TS error: Type 'unknown' is not assignable to type 'ReactNode'

Type narrowing untuk `metadata` (AuditLog). Fix:
```tsx
// Sebelum
{log.metadata && (<details>...</details>)}

// Sesudah
{log.metadata !== null && log.metadata !== undefined && (<details>...</details>)}
```

### ❌ TS error: "HTMLAnchorElement | null is not assignable to HTMLDivElement"

Ref type mismatch. Fix dengan pakai `HTMLElement` (lebih luas):
```tsx
const ref = useState<HTMLElement | null>(null);
```

### ❌ Build TypeScript lambat / hang di Windows

Turbopack + tsc lambat di Windows. Workaround:
```bash
# Skip type check, langsung start dev
pnpm dev

# Atau lint saja (lebih cepat dari full build)
pnpm lint

# Atau pakai SWC bukan TSC (kalau ada)
```

---

## Reset Environment

### Reset Database (hapus semua data, ulang dari nol)

```bash
mysql -u root -e "DROP DATABASE central_bank_core; CREATE DATABASE central_bank_core CHARACTER SET utf8mb4;"
cd "C:/CODING/RPL 2/SmartBank/Central-Bank"

# Opsi A: Migration
npx prisma migrate deploy

# Opsi B: DB push (lebih cepat, sync schema langsung)
npx prisma db push

# Generate Prisma Client
npx prisma generate

# Seed
node prisma/seed.js
```

### Reset Node Modules (kalau ada konflik dependency)

```bash
cd <service>
rm -rf node_modules pnpm-lock.yaml
pnpm install
```

### Reset Frontend Cache (`.next` folder)

```bash
cd "C:/CODING/RPL 2/SmartBank/frontend"
rm -rf .next node_modules/.cache

# Restart dev dengan memory limit tinggi
NODE_OPTIONS=--max-old-space-size=8192 pnpm dev
```

### Stop Semua Service

Di masing-masing tab terminal yang menjalankan service, tekan **Ctrl+C**.

Atau sekaligus (Windows):
```bash
taskkill /F /IM node.exe
```

> ⚠️ Perintah di atas mematikan **semua** proses Node.js, termasuk yang bukan SmartBank. Hati-hati kalau ada project lain jalan.

---

## Ringkasan Alur Startup

```
Laragon Start All (MySQL hijau)
      ↓
[Tab 1] cd Central-Bank && pnpm start:dev       (port 3000)
[Tab 2] cd Wallet       && pnpm dev             (port 6969)
[Tab 3] cd Gateway      && pnpm dev             (port 4000)
[Tab 4] cd frontend     && NODE_OPTIONS=--max-old-space-size=8192 pnpm dev
                                                    (port 3001)
      ↓
[Tab 5] curl health checks (semua HTTP 200)
      ↓
Buka browser http://localhost:3001
      ↓
Login dengan admin@test.com / password
      ↓
Sidebar: 7 menu admin baru (Supply, Issuance, Burn, Reversal, Fee, Ledger, Audit)
```

---

**Dokumentasi ini dibuat untuk menjalankan SmartBank CBDC Ecosystem secara lokal tanpa Docker. Untuk testing otomatis, lihat `AUDIT_REPORT.md`. Untuk analisis algoritma, lihat dokumen `Analisis Algoritma yang Cocok untuk SmartBank`.**
