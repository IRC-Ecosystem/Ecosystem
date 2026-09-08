# 📊 UMKM Insight — Business Intelligence & Analytics

UMKM Insight adalah platform analitik bisnis dan wawasan cerdas (*business intelligence*) untuk pemilik UMKM. Platform ini mengolah data transaksi penjualan, arus kas, dan performa produk secara real-time dari SmartBank dan aplikasi ekosistem lainnya.

---

## ⚡ Fitur Otomatisasi Ekosistem (Background Auto-Sync)

- **Sinkronisasi Real-Time**: Menerima data transaksi penjualan dari SmartBank dan Gateway API Integrator secara otomatis di balik layar.
- **Auto-Create Guest User**: Jika transaksi dikirim oleh akun/pengguna eksternal yang belum terdaftar di UMKM Insight, sistem secara otomatis membuatkan profil guest user (`ext_<source>_<user_id>`) tanpa menolak transaksi (mencegah error 404).
- **Update Grafik Real-Time**: Laporan Penjualan, Arus Kas, dan Performa Produk (*Top Selling Products*) langsung diperbarui saat transaksi baru masuk.

---

## 🌐 URL Akses (Docker)

Dari root directory `Ecosystem RPL II`:

```powershell
docker compose up --build -d
```

- **Landing Page & Dashboard**: `http://localhost:3006`
- **Integrasi Gateway UI**: `http://localhost:3006/api-integrator.php`
- **API Endpoint**: `http://localhost:4006`
- **MySQL Database**: `localhost:3316` (Database: `umkm_insight`)

---

## 👤 Akun Default untuk Login

| Role | Username | Password | Keterangan |
|---|---|---|---|
| **Client / UMKM** | `budi` | `password123` | Akun demo pemilik UMKM |
| **Client Demo** | `client_demo` | `password` | Akun demo client |
| **Operator** | `op_jaya` | `password` | Dashboard operator sistem |
| **Admin** | `admin_super` | `password` | Dashboard administrator |

---

## 🔌 Endpoint API Integrasi

| Method | Endpoint | Deskripsi & Fungsi |
|---|---|---|
| `GET` | `/api/health.php` | Cek status kesehatan service & koneksi database |
| `POST` | `/api/transactions.php` | **Penerima Transaksi Utama**: Menerima & mencatat transaksi otomatis dari SmartBank / Gateway |
| `GET` | `/api/transactions.php?user_id=...` | Membaca daftar riwayat transaksi pengguna |
| `POST` | `/api/market_trends.php` | Menerima data tren pasar dari Marketplace |
| `GET` | `/api/sync_data.php` | Pull data dari service eksternal jika dikonfigurasi |

*Header API Key (jika `UMKM_API_KEY` di-set):*
```http
X-API-Key: nilai-api-key
```

---

## 🛠️ Setup Manual (Non-Docker)

1. Buat database `umkm_insight` di MySQL lokal.
2. Import file database secara berurutan:
   - `dokumentasi/database.sql`
   - `dokumentasi/database_patch.sql`
   - `dokumentasi/database_patch_2.sql`
3. Salin `.env.example` menjadi `.env` dan sesuaikan kredensial database.
4. Jalankan via server Apache/PHP (PHP 8.2) dengan document root ke folder `UMKM-Insight`.

---

## 📁 Struktur Folder Project

```text
UMKM-Insight/
├── api/              # Endpoint JSON API integrasi otomatis (/transactions.php, /health.php)
├── app/              # Core logic, controller, service, repository PHP
├── assets/           # CSS, JavaScript, dan visual assets
├── config/           # Konfigurasi database PDO & MySQL
├── includes/         # Header, sidebar, topbar, dan footer layout
├── *.php             # Halaman utama UI (dashboard.php, laporan-penjualan.php, api-integrator.php)
└── dokumentasi/      # SQL schema, patch, dan catatan teknis
```
