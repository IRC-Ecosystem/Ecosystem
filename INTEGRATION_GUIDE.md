# Panduan SmartBank, WarungPOS, dan PasarKita

## Pilih Mode

| Mode | Gunakan untuk | Connector |
| --- | --- | --- |
| Lokal native | Debug source | `http://127.0.0.1:5000` |
| Docker Compose | Menjalankan semua service | `http://connector:5000` di container |

Jangan menjalankan dua mode pada port yang sama.

## URL

| Service | Lokal native | Docker |
| --- | --- | --- |
| Central Bank | `http://127.0.0.1:3000` | `http://localhost:3000` |
| Connector health | `http://127.0.0.1:5000/health` | `http://localhost:5000/health` |
| WarungPOS | `http://localhost:3002` | `http://localhost:3002` |
| PasarKita | `http://localhost:8080` | `http://localhost:8080` |

## Lokal Native

Prasyarat: Node.js 20+, PHP 8+ dengan `curl` dan `pdo_mysql`, MySQL/Laragon pada port `3306`.

1. Isi `SmartBank/.env` berdasarkan `SmartBank/Connector/CONNECTOR_SETUP.md`.
2. Terminal 1:

```powershell
cd C:\CODING\IRC\Eco\SmartBank\Central-Bank
npm install
npm run start:dev
```

3. Terminal 2:

```powershell
cd C:\CODING\IRC\Eco\SmartBank\Connector
npm install
npm run db:setup
npm run prisma:generate
npm run prisma:push
npm run dev
```

4. Provision key terpisah:

```powershell
npm run seed:service-key -- WARUNGPOS WarungPOS
npm run seed:service-key -- MARKETPLACE PasarKita
```

5. Isi `POS/.env` dengan key WarungPOS dan `SMARTBANK_CONNECTOR_URL=http://127.0.0.1:5000`, lalu jalankan `npm install`, `npm run setup:db`, `npm run dev`.
6. Import `Marketplace/database/pasarkita.sql`. Isi `Marketplace/.env` dengan key Marketplace, `BASEURL=http://localhost:8080/`, dan Connector lokal.
7. Terminal 4:

```powershell
cd C:\CODING\IRC\Eco\Marketplace
php -S localhost:8080 -t public
```

## Docker

1. Salin `.env.example` menjadi `.env`.
2. Isi password dan secret minimal 32 karakter. Isi `SMARTBANK_WARUNGPOS_API_KEY` dan `SMARTBANK_MARKETPLACE_API_KEY` dengan key berbeda.
3. Jalankan:

```powershell
cd C:\CODING\IRC\Eco
docker compose up --build
```

4. Cek `http://localhost:5000/health`. Stop dengan `docker compose down`.

Database PasarKita hanya diimport pada volume MySQL baru. Untuk memulai data Docker dari nol: `docker compose down -v`, lalu `docker compose up --build`.

## Setup Wallet

1. Daftarkan buyer pada SmartBank menggunakan nomor telepon yang sama dengan aplikasi.
2. WarungPOS: login manager, buka pengaturan wallet SmartBank, OTP-link wallet penerima toko.
3. PasarKita: login admin, buka `/admin/smartbank`, OTP-link wallet treasury.
4. PasarKita buyer: login, buka `/user/profile`, OTP-link wallet buyer.
5. Seller multi-toko: gunakan external ID `marketplace-store-<storeId>` dan endpoint seller SmartBank setelah migrasi `Marketplace/database/migrate_smartbank_seller.sql` diterapkan.

## Coba Pembayaran

1. Buat buyer SmartBank, pastikan mempunyai saldo dan PIN enam digit.
2. WarungPOS: tambah barang ke invoice, pilih `smartbank`, masukkan PIN buyer. Cek invoice dan ledger.
3. PasarKita: login buyer, tambah produk ke keranjang, checkout, buka `/user/orders`, masukkan PIN. Cek status `paid` dan `smartbank_payments`.
4. Submit ulang pembayaran order/invoice yang sama. Tidak boleh ada debit kedua.

## Troubleshooting

| Gejala | Tindakan |
| --- | --- |
| Connector tidak aktif | Pastikan Central Bank hidup, lalu cek `/health` pada port `5000`. |
| `USER_NOT_LINKED` | Ulangi OTP-link buyer atau wallet penerima pada aplikasi yang benar. |
| `INVALID_PIN` | Gunakan PIN wallet SmartBank, bukan password aplikasi. |
| `INSUFFICIENT_BALANCE` | Tambah saldo wallet buyer, lalu ulangi pembayaran. |
| API key gagal | Pastikan key aplikasi benar dan bukan `CONNECTOR_ADMIN_KEY`. |
| Port bentrok | Hentikan proses lama atau ubah port aplikasi. |
| Database kosong Docker | Jalankan `docker compose down -v`, lalu build ulang. |

## Smoke Test

- Connector health merespons sukses.
- WarungPOS dan PasarKita dapat login.
- Buyer dan receiver berhasil OTP-link.
- Pembayaran sukses satu kali.
- Retry tidak menggandakan debit.
- PIN, OTP, API key tidak muncul pada UI/log.

## UMKM Insight

UMKM Insight menerima event settlement SmartBank pada `POST /api/events.php`. Sebelum data tampil di dashboard, petakan penerima settlement ke user UMKM Insight pada tabel `integration_subjects`.

```sql
INSERT INTO integration_subjects (source, external_subject_id, user_id)
VALUES ('SMARTBANK', 'pos-merchant-main', 1);
```

Import `UMKM-Insight/dokumentasi/database_events.sql` untuk mode lokal. Docker mengimpor schema ini hanya saat volume MySQL baru dibuat. Event tanpa mapping tetap dicatat pada `integration_events`, namun tidak masuk `transaction_cache`.
