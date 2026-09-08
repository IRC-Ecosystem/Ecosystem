# Dokumentasi Integrasi Eco

## Ringkasan Perbaikan

| Temuan | Solusi |
|---|---|
| Secret placeholder dan API key terekspos | `.env` lokal dirotasi; `.env.example` hanya berisi placeholder; secret baru tidak ditulis di dokumentasi. |
| Session POS lemah | Fallback production dihapus; cookie `httpOnly`, `secure` production, `SameSite=Strict`; endpoint `/health` ditambahkan. |
| Burn Central Bank gagal validasi | Validasi tipe source dilakukan sebelum lookup sink; seluruh 54 test Central Bank lulus. |
| Wallet tanpa suite test | Smoke test native `node:test` untuk `/health`; test tidak membutuhkan DB. |
| Event UMKM tanpa validasi/rate limit/logging | Validasi source, event ID, type, subject, amount; limit 100 event/source/menit; error logging terstruktur. |
| PHP patch self-delete | `unlink(__FILE__)` dihapus. |
| POS SQL tidak di-init Compose | `POS/database/migrate_to_current_schema.sql` dipasang sebagai init `07-warungpos.sql`. |
| CORS dan dependency readiness tidak lengkap | Allowlist seluruh UI/service lokal; healthcheck serta `depends_on` health ditambahkan. |
| SupplierHub standalone dan mapping user hardcoded | Docker service port `4004`; health endpoint; Connector URL/API key; mapping ID numerik dihapus. |
| Logistika kosong | Service minimal port `4005`: health, create shipment, idempotensi `external_id`; persistence masih batas dev. |
| API Integrator tidak runtime | Docker service port `4001`; DB health nyata; `SECRET_KEY` wajib; route targets dikonfigurasi ke service Compose. |
| Marketplace SQL injection | Query memakai placeholder PDO untuk filter dan nilai; lint PHP lulus. |

## Arsitektur Integrasi

```text
POS :3002 ------------------\
Marketplace :8080 ---------- Connector :5000 --> Central Bank :3000 --> Wallet :6969
SupplierHub :4004 ----------/          |
                                       +--> UMKM Insight :3006 events

API Integrator :4001 --> routing/health proxy ke Connector, POS, Marketplace,
                         SupplierHub, Logistika :4005, UMKM Insight
Central Bank UI :5173 --> Central Bank
Wallet UI :3001 ------> Gateway :4000 --> Central Bank / Wallet
```

Payment contract utama: `POST /v1/connect/payment-requests` dengan `Authorization: Bearer`, `X-Idempotency-Key`, buyer/seller external ID, `gross_amount`, dan PIN 6 digit. POS dan Marketplace memakai contract ini. SupplierHub menyediakan client Connector untuk mode `PAYMENT_MODE=smartbank`; mode `mock` hanya untuk development eksplisit.

Endpoint runtime:

| Service | Endpoint |
|---|---|
| Central Bank | `GET http://localhost:3000/api/v1/health` |
| Wallet | `GET http://localhost:6969/health` |
| Gateway | `GET http://localhost:4000/health` |
| Connector | `GET http://localhost:5000/health`, `GET /ready` |
| POS | `GET http://localhost:3002/health` |
| Marketplace | `GET http://localhost:8080/` |
| UMKM Insight | `GET http://localhost:3006/api/health.php`, `POST /api/events.php` |
| SupplierHub | `GET http://localhost:4004/health.php` |
| API Integrator | `GET http://localhost:4001/health` |
| Logistika | `GET http://localhost:4005/health`, `POST /api/v1/shipments` |

## Cara Penggunaan

1. Salin `.env.example` menjadi `.env`.
2. Isi semua `change_me_*` dengan secret acak minimal 32 karakter. Jangan commit `.env`.
3. Pastikan Docker Desktop dan Docker Compose aktif.
4. Jalankan validasi: `docker compose config --quiet`.
5. Pada laptop RAM 4 GB, jalankan stack inti secara serial: `docker compose --parallel 1 up -d --build --wait`.
6. Tambahkan domain hanya saat diperlukan: `docker compose --parallel 1 --profile ui|pos|marketplace|supplier|integrator up -d --build --wait`.
7. Pantau: `docker compose ps`, `docker stats --no-stream`, dan `docker compose logs -f <service>`.

Stack default memuat MySQL, Central Bank, UMKM Insight, dan Connector. `ui` memuat Wallet, Gateway, dan dua frontend; `pos` memuat POS dan Logistika; `marketplace` memuat Marketplace; `supplier` memuat SupplierHub dan Logistika; `integrator` memuat API Integrator, SupplierHub, dan Logistika. Detail untuk developer ada di `DEVELOPER_GUIDE.md`.

Database init memakai MySQL 8, init Connector, Marketplace, UMKM Insight, POS, dan SupplierHub. Volume MySQL existing tidak menjalankan ulang script init; gunakan migration service/schema untuk database yang sudah ada.

Production wajib memakai reverse proxy TLS. Compose lokal memakai HTTP untuk development.

## Cara Testing

Perintah suite:

```powershell
npm test --prefix POS
npm test --prefix SmartBank/Connector
npm test --prefix SmartBank/Wallet
npm test --prefix SmartBank/Central-Bank -- --runInBand
npm test --prefix Logistika
python -m compileall -q Api-Integrator
php -l UMKM-Insight/api/events.php
docker compose config --quiet
docker compose build
```

Bukti lokal sebelumnya: POS `3 passed`, Connector `5 passed`, Wallet `1 passed`, Central Bank `54 passed`, Logistika `1 passed`; PHP lint tanpa error; Python compile tanpa error; Compose config valid; dependency audit Wallet `0 vulnerabilities`.

Observasi runtime 2026-08-10 setelah Docker Desktop direstart: 13 container existing otomatis hidup kembali; MySQL, Wallet, Gateway, Logistika, Marketplace, SupplierHub, dan UMKM Insight dapat sehat, tetapi Central Bank, Connector, POS, dan API Integrator mengalami kegagalan koneksi MySQL atau healthcheck timeout selama startup. Tidak ada container OOM-killed. Karena itu, klaim semua 13 container sehat tidak dipertahankan; hasil `docker compose --parallel 1 ... up --wait`, `docker compose ps`, dan smoke test pada saat rilis adalah bukti yang berlaku.
