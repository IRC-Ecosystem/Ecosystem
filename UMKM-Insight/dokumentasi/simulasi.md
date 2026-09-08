# Walkthrough Simulasi Sistem UMKM Insight

Dokumen ini berisi panduan skenario pengujian untuk memverifikasi seluruh fitur yang telah dimigrasi ke backend PHP & MySQL.

## 1. Persiapan Teknis (Setup)

Sebelum menjalankan simulasi, pastikan lingkungan pengembangan Anda sudah siap:

### A. Menjalankan Server Lokal
Aplikasi ini berbasis PHP, sehingga memerlukan web server untuk berjalan. Anda bisa menggunakan salah satu cara berikut:

*   **FlyEnv (Direkomendasikan)**:
    1.  Buka aplikasi FlyEnv.
    2.  Aktifkan layanan **PHP**, **Apache/Nginx**, dan **MySQL**.
    3.  Buka modul **Hosts** (Ikon Dunia) dan klik **Add Site**.
    4.  **Domain**: Isi dengan `umkm.test` atau nama bebas lainnya.
    5.  **Root Path**: Klik *Browse* dan pilih folder proyek `RPL` ini.
    6.  Klik **Save**. Aplikasi dapat diakses di browser melalui `https://umkm.test`.
*   **XAMPP/Laragon**: Letakkan folder proyek ini di dalam folder `htdocs` (XAMPP) atau `www` (Laragon). Akses via `http://localhost/RPL/`.
*   **PHP Built-in Server**: Buka terminal/CMD di folder proyek ini dan jalankan perintah `php -S localhost:8000`. Akses via `http://localhost:8000`.

### B. Konfigurasi Database
1.  Buka **phpMyAdmin** atau tool database favorit Anda.
2.  Buat database baru dengan nama `umkm_insight` (sesuaikan jika Anda mengubahnya di `config/db.php`).
3.  Impor file [dokumentasi/database.sql](file:///c:/Users/Av/Documents/Library/Semester%204/RPL/dokumentasi/database.sql) ke dalam database tersebut.
4.  Pastikan detail koneksi di `config/db.php` sudah sesuai dengan `DB_HOST`, `DB_NAME`, `DB_USER`, dan `DB_PASS` lingkungan Anda.

---

## 2. Akun Pengujian (Pre-seeded)
Gunakan akun berikut untuk melakukan simulasi:

| Role | Username | Password | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Client (Free)** | `budi` | `password` | UMKM dengan akses fitur dasar. |
| **Client (Premium)** | `sari` | `password` | UMKM dengan akses analitik lengkap. |
| **Operator** | `op_jaya` | `password` | Staff pengelola operasional & tier. |
| **Admin** | `admin_super` | `password` | Pengelola sistem & statistik global. |

---

## 2. Skenario Simulasi

### Skenario A: Registrasi & Analitik Dasar (Client)
1.  Buka browser dan akses halaman `/register.php` (misal: `https://umkm.test/register.php`), daftar akun baru sebagai UMKM.
2.  Login menggunakan akun tersebut melalui halaman utama `/index.php`.
3.  **Verifikasi**: Dashboard harus menampilkan pesan selamat datang dan data (masih kosong jika belum ada transaksi di DB).
4.  Buka halaman `/laporan-penjualan.php` dan `/arus-kas.php`. Karena akun baru bersifat **Free**, pastikan terdapat pesan/badge "Free Tier" dan fitur tertentu terkunci.

### Skenario B: Alur Upgrade Tier Premium
1.  Login sebagai Client `budi` (Free) di `/index.php`.
2.  (Internal) Tambahkan baris di tabel `tier_requests` secara manual melalui database untuk mensimulasikan klik tombol upgrade:
    ```sql
    INSERT INTO tier_requests (user_id, status) VALUES (1, 'pending');
    ```
3.  Logout dan Login sebagai Operator `op_jaya` di `/index.php`.
4.  Buka halaman **Manajemen Operasional** di `/operator.php`.
5.  Cari nama "Budi Santoso" di tabel **Pengajuan Tier Premium**.
6.  Klik tombol **Approve**.
7.  Logout dan Login kembali sebagai `budi`.
8.  **Verifikasi**: Status akun `budi` sekarang adalah **Premium**, dan fitur di modul analitik kini sudah terbuka.

### Skenario C: Pusat Bantuan & Pengaduan
1.  Login sebagai Client `sari` di `/index.php`.
2.  Buka `/pengaduan.php`, kirimkan tiket bantuan baru.
3.  Logout dan Login sebagai Operator `op_jaya`.
4.  Buka `/pengaduan-admin.php`.
5.  Cari tiket dari `sari`, klik **Tandai Selesai**.
6.  Logout dan Login kembali sebagai `sari`.
7.  **Verifikasi**: Status pengaduan di histori berubah menjadi **Resolved**.

### Skenario D: Manajemen Global (Admin)
1.  Login sebagai Admin `admin_super` di `/index.php`.
2.  Buka `/admin.php`.
3.  Lihat **Total Pendapatan Ekosistem** (akumulasi transaksi seluruh user).
4.  Klik tombol **Blast Message**.
5.  Isi judul "Pemeliharaan Sistem" dan pesan "Sistem akan offline malam ini". Klik **Kirim**.
6.  Login sebagai user manapun (Client/Operator).
7.  **Verifikasi**: Klik ikon lonceng/notifikasi di topbar, pesan blast dari admin harus muncul.

---

## 3. Catatan Teknis untuk Penguji
- Seluruh data transaksi ditarik dari tabel `transaction_cache`.
- Jika grafik tidak muncul, pastikan database sudah terisi data sampel dari file `database.sql`.
- File prototype HTML asli tersimpan di folder `_archive/Archives/` sebagai referensi visual asli.

---
Tim Pengembang RPL 2 - UMKM Insight

kelompok 3
