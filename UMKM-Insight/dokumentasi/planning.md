# Planning Pengembangan Aplikasi UMKM Insight (PHP & MySQL)

Dokumen ini berisi rencana pengembangan untuk mentransformasi prototype statis UMKM Insight menjadi aplikasi web dinamis berbasis PHP dan MySQL sesuai dengan dokumen desain RPL.

## 1. Arsitektur Aplikasi
Aplikasi akan dibangun menggunakan arsitektur **MVC (Model-View-Controller)** sederhana tanpa framework berat (Native PHP) untuk memenuhi kriteria tugas.

**Struktur Folder:**
- `/config`: Koneksi database dan konfigurasi API.
- `/models`: Logika data dan query database.
- `/controllers`: Logika bisnis dan routing.
- `/views`: File UI (HTML/PHP).
- `/assets`: CSS, JS, dan Images.
- `/api`: Endpoint REST API untuk integrasi ekosistem.
- `/includes`: Header, sidebar, dan footer reusable.

## 2. Desain Database (MySQL)

### Tabel `users`
| Kolom | Tipe | Deskripsi |
|---|---|---|
| id | INT AI PK | ID User |
| username | VARCHAR(50) | Username unik |
| password | VARCHAR(255) | Hash password |
| role | ENUM('client', 'operator', 'admin') | Peran pengguna (Client=Konsumen Insight) |
| nama_lengkap | VARCHAR(100) | Nama asli pengguna |
| email | VARCHAR(100) | Email bisnis |
| nama_bisnis | VARCHAR(100) | Nama UMKM (Khusus Client) |
| kategori | VARCHAR(50) | Kategori bisnis |
| smartbank_id | VARCHAR(50) | ID Akun SmartBank (Integrasi) |
| tier | ENUM('free', 'premium') | Status langganan |
| tier_expiry | DATE | Tanggal berakhir premium |
| created_at | TIMESTAMP | Waktu pendaftaran |

### Tabel `notifications`
| Kolom | Tipe | Deskripsi |
|---|---|---|
| id | INT AI PK | ID Notifikasi |
| user_id | INT FK | Penerima |
| sender_id | INT FK | Pengirim (Operator/Admin) |
| type | ENUM('auto', 'admin', 'offer') | Jenis (Notif Sistem, Admin, Penawaran) |
| title | VARCHAR(100) | Judul |
| message | TEXT | Isi pesan |
| is_read | BOOLEAN | Status baca |
| created_at | TIMESTAMP | Waktu kirim |

### Tabel `complaints` (Pengaduan)
| Kolom | Tipe | Deskripsi |
|---|---|---|
| id | INT AI PK | ID Pengaduan |
| user_id | INT FK | Pengirim (Client) |
| subject | VARCHAR(100) | Judul pengaduan |
| message | TEXT | Isi keluhan |
| status | ENUM('open', 'resolved') | Status penanganan |
| operator_id | INT FK | Operator yang menangani |
| created_at | TIMESTAMP | Waktu kirim |

### Tabel `offers` (Penawaran)
| Kolom | Tipe | Deskripsi |
|---|---|---|
| id | INT AI PK | ID Penawaran |
| title | VARCHAR(100) | Nama Promo/Layanan |
| description | TEXT | Detail penawaran |
| price | DECIMAL(15,2) | Harga khusus (jika ada) |
| target_tier | ENUM('all', 'free', 'premium') | Target audiens |
| created_by | INT FK | Operator yang membuat |
| created_at | TIMESTAMP | Waktu dibuat |

### Tabel `transaction_cache`
*Catatan: UMKM Insight bersifat read-only dari SmartBank, namun meng-cache data untuk performa analitik.*
| Kolom | Tipe | Deskripsi |
|---|---|---|
| id | INT AI PK | ID internal |
| external_id | VARCHAR(50) | ID dari SmartBank |
| user_id | INT FK | Pemilik transaksi |
| type | VARCHAR(50) | Jenis (Penjualan, Fee, dsb) |
| source | VARCHAR(50) | Sumber (Marketplace, POS, dsb) |
| amount | DECIMAL(15,2) | Nominal |
| status | VARCHAR(20) | Status (Success, dsb) |
| transaction_date | DATETIME | Waktu transaksi |

## 3. Implementasi Fitur Utama

### A. Autentikasi & Role-based Access (RBAC)
- **Admin**: Akses penuh ke sistem, monitoring log, audit trail, dan konfigurasi global.
- **Operator**: Mengelola pembayaran langganan, menangani pengaduan (complaints), membuat penawaran layanan (offers), dan memantau status operasional UMKM.
- **Konsumen (UMKM Owner)**: User utama yang menggunakan analitik, melakukan pembayaran premium, dan mengirim pengaduan jika ada kendala.

- Login menggunakan session PHP dengan pengecekan role di setiap controller.

### B. Modul Analitik (Dashboard, Laporan, Arus Kas, Performa)
- **Data Retrieval**: Mengambil data dari `transaction_cache`.
- **Tier Locking**: Pengecekan status `tier` pada controller sebelum mengirim data ke view. Jika 'free', data akan dibatasi (misal: hanya 7 hari terakhir).
- **Visualization**: Menggunakan Chart.js (tetap di sisi client) dengan data yang disuplai dari PHP via JSON.

### C. Integrasi SmartBank (API Gateway)
- Implementasi endpoint `/api/ambil_data_transaksi.php`.
- Simulasi request ke API SmartBank (via cURL) untuk sinkronisasi ledger ke `transaction_cache`.

### D. Dashboard & Manajemen Sesuai Role
- **Dashboard Client (Konsumen)**: Visualisasi analitik (Dashboard, Laporan, Arus Kas, Performa), Fitur Langganan, dan Pengiriman Pengaduan.
- **Dashboard Operator**: Manajemen Transaksi Premium (Approve/Reject), Manajemen Penawaran/Promo, Penanganan Tiket Pengaduan, dan Pengiriman Notifikasi/Blast Penawaran.
- **Dashboard Admin**: Statistik Global Ekosistem, Manajemen User (Create/Edit Staff), Audit Log Sistem, dan Pengaturan Fee/Parameter Keuangan.

## 4. Rencana Kerja (Roadmap)

1.  **Tahap 1: Setup Environment** [DONE]
    - Konfigurasi database MySQL dan struktur folder.
2.  **Tahap 2: Migrasi Frontend ke PHP** [DONE]
    - Konversi .html ke .php dan pemisahan komponen (Header, Sidebar, dsb).
3.  **Tahap 3: Sistem Autentikasi & RBAC** [DONE]
    - Login, Register, Logout dengan Session PHP & MySQL.
4.  **Tahap 4: Pengolahan Data & Fitur Operasional** [DONE]
    - Query SQL riil untuk analitik dashboard (Revenue, Cashflow, Produk).
    - Manajemen pengaduan dan tier otomatis (Operator/Admin).
    - Notifikasi sistem dan fitur Blast Message.
5.  **Tahap Akhir: Final Polish & Testing** [IN PROGRESS]
    - Verifikasi alur RBAC (Role-Based Access Control).
    - Testing integrasi data antar role.
    - Final UI polish dan pembersihan kode.

---
*Dibuat untuk: Tugas Besar RPL 2 - Kelompok UMKM Insight*
