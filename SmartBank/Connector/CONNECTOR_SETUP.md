# SmartBank Connector Setup

Connector adalah gateway server-to-server untuk aplikasi saudara seperti POS dan Marketplace. Browser tidak boleh mengirim API key Connector.

## Konsep

- Setiap aplikasi saudara memiliki `service_name` dan API key sendiri.
- Pembeli dan penerima dana ditautkan memakai OTP ke external ID aplikasi.
- Pembayaran memakai PIN pembeli dan `Idempotency-Key` stabil per invoice/order.
- API admin hanya untuk provisioning service dan rotasi API key.

Endpoint utama:

```text
Health:       GET  /health
Admin:        /v1/connect/admin
User linking: /v1/connect/users
Payment:      POST /v1/connect/payment-requests
```

## Setup Lokal

Prasyarat: Node.js 20+, MySQL/Laragon aktif di `127.0.0.1:3306`, serta Central Bank aktif di `http://127.0.0.1:3000`.

1. Konfigurasi `SmartBank/.env`:

```env
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_ROOT_PASSWORD=
JWT_SECRET=<minimal-32-karakter>
SERVICE_TOKEN=<minimal-32-karakter>
CONNECTOR_ADMIN_KEY=<minimal-32-karakter-random>
CONNECTOR_AUDIT_HMAC_SECRET=<minimal-32-karakter-random>
CONNECTOR_DATABASE_HOST=127.0.0.1
CONNECTOR_DATABASE_PORT=3306
CONNECTOR_DATABASE_NAME=connector_db
CONNECTOR_CENTRAL_BANK_URL=http://127.0.0.1:3000
```

2. Konfigurasi `SmartBank/Connector/.env.local` bila perlu. Nilai di file ini mengoverride `SmartBank/.env`; pastikan `CONNECTOR_ADMIN_KEY` konsisten.

3. Siapkan database dan Prisma:

```powershell
cd C:\CODING\IRC\Eco\SmartBank\Connector
npm install
npm run db:setup
npm run prisma:generate
npm run prisma:push
```

`npm run db:setup` membutuhkan hak MySQL untuk membuat database/user. Pada Laragon dengan `root` tanpa password dan database sudah ada, gunakan `npm run prisma:push` langsung bila script tersebut gagal membuat user `root`.

4. Jalankan Central Bank lalu Connector:

```powershell
cd C:\CODING\IRC\Eco\SmartBank\Central-Bank
npm run start:dev

cd C:\CODING\IRC\Eco\SmartBank\Connector
npm run dev
```

Verifikasi:

```powershell
Invoke-WebRequest http://127.0.0.1:5000/health
```

## Provision API Key Lokal

Buat API key berbeda untuk setiap aplikasi, misalnya POS dan Marketplace. Dari `SmartBank/Connector`:

```powershell
npm run seed:service-key -- WARUNGPOS "WarungPOS"
npm run seed:service-key -- MARKETPLACE "PasarKita"
```

Salin key yang ditampilkan sekali saja ke environment aplikasi terkait:

```env
# POS/.env
SMARTBANK_CONNECTOR_URL=http://localhost:5000
SMARTBANK_CONNECTOR_API_KEY=sbk_...
SMARTBANK_POS_SELLER_EXTERNAL_ID=pos-merchant-main

# Marketplace/.env
SMARTBANK_CONNECTOR_URL=http://localhost:5000
SMARTBANK_CONNECTOR_API_KEY=sbk_...
SMARTBANK_MARKETPLACE_EXTERNAL_ID=marketplace-merchant-main
```

Jangan memakai `CONNECTOR_ADMIN_KEY` sebagai API key POS atau Marketplace.

## Setup Docker Terpadu

Dari root `C:\CODING\IRC\Eco`:

```powershell
Copy-Item .env.example .env
```

Isi minimal:

```env
MYSQL_ROOT_PASSWORD=<password-mysql>
MYSQL_PASSWORD=<password-user-central-bank>
JWT_SECRET=<minimal-32-karakter-random>
SERVICE_TOKEN=<minimal-32-karakter-random>
CONNECTOR_ADMIN_KEY=<minimal-32-karakter-random>
CONNECTOR_AUDIT_HMAC_SECRET=<minimal-32-karakter-random>
SMARTBANK_CONNECTOR_API_KEY=sbk_<key-random-minimal-32-karakter>
POS_SESSION_SECRET=<minimal-32-karakter-random>
```

Jalankan:

```powershell
docker compose up --build
```

Compose menjalankan MySQL, Central Bank, Wallet, Gateway, Connector, UI, dan POS. Connector otomatis melakukan Prisma `db push` serta bootstrap service `WARUNGPOS` memakai `SMARTBANK_CONNECTOR_API_KEY` dari root `.env`.

Endpoint host:

```text
Connector:    http://localhost:5000/health
Central Bank: http://localhost:3000/api/v1/health
POS:          http://localhost:3002
```

Untuk aplikasi di dalam network Docker, gunakan hostname service `http://connector:5000`, bukan `localhost`.

## Wallet Linking dan Pembayaran

1. Tautkan wallet pembeli dari UI aplikasi saudara menggunakan OTP.
2. Tautkan wallet penerima toko/treasury dari UI manager/admin aplikasi saudara.
3. Pastikan external ID penerima cocok dengan environment aplikasi.
4. Pembeli mengisi PIN SmartBank pada halaman pembayaran order/invoice.
5. Backend aplikasi saudara mengirim `POST /v1/connect/payment-requests` dengan API key server-side dan idempotency key stabil.

## Troubleshooting

| Gejala | Penyebab umum | Tindakan |
| --- | --- | --- |
| `ADMIN_UNAUTHORIZED` | Key di `.env.local` berbeda atau Connector belum restart | Samakan `CONNECTOR_ADMIN_KEY`, restart Connector, buat header baru. |
| `USER_NOT_LINKED` | Buyer atau merchant/treasury belum linked pada service yang sama | Cek status linkage di UI dan tautkan ulang external ID yang dipakai payment. |
| `INVALID_PIN` | PIN wallet pembeli salah | Gunakan PIN SmartBank pembeli, bukan password aplikasi. |
| `P1010` Prisma | User/password/database MySQL salah | Periksa `DATABASE_URL`, host, port, user, password. |
| `404` berulang sesudah linkage berubah | Record idempotency gagal lama | Gunakan key invoice/order stabil; versi Connector terbaru hanya menyimpan respons `2xx`. |
| Docker `TLS handshake timeout` | Pull image Docker Hub terganggu | Ulangi `docker pull mysql:8.0`, cek jaringan/DNS/VPN. |
