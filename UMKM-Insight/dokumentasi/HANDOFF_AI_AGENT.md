# 🔄 Handoff Document — Proyek UMKM Insight
## Untuk AI Agent Selanjutnya

> **Tanggal:** 2 Juni 2026  
> **Conversation ID sesi ini:** `117a6d1c-d299-4849-ac41-8ac1d7492ad5`  
> **Conversation ID sesi sebelumnya:** `d543ea8d-fc95-4ecc-8a5f-8e5dfad27b86`

---

## 1. Ringkasan Proyek

**UMKM Insight** adalah aplikasi web berbasis **PHP 8 Native** (tanpa framework) + **MySQL** yang berfungsi sebagai **Analytics Dashboard** untuk pelaku UMKM. Aplikasi ini bersifat **Read-Only** terhadap data transaksi — ia tidak membuat transaksi baru, melainkan mengambil (sinkronisasi) data dari 3 sumber API simulasi:

| Sumber API | Fungsi | Hubungan dengan UMKM |
|---|---|---|
| **SmartBank** | Data keuangan: saldo, histori transaksi (income/expense) | Dimiliki per user (via `smartbank_id`) |
| **WarungPos** | Data penjualan kasir fisik (lokal/POS) | Dimiliki per user (via `smartbank_id` yang sama) |
| **PasarKita** | Tren produk laris secara **global** di marketplace | **Bukan milik UMKM manapun** — data `smartbank_id = 'GLOBAL'` |

### Arsitektur Kunci
- **Database:** `umkm_insight` (MySQL), skema awal di `dokumentasi/database.sql`, patch tambahan di `dokumentasi/database_patch_2.sql`
- **Autentikasi:** PHP Session (di `includes/auth.php`), **JANGAN panggil `session_start()` di file manapun** karena `auth.php` sudah menanganinya
- **CSS:** Gabungan `assets/css/style.css` (custom CSS variables + dark mode) dan **Tailwind CDN** (dimuat via `includes/header.php`)
- **Icons:** Phosphor Icons (`@phosphor-icons/web`)
- **Charts:** Chart.js (CDN)

---

## 2. Struktur Role & Akun Demo

| Username | Password | Role | Tier | SmartBank ID (di simulasi) |
|---|---|---|---|---|
| `budi` | `password` | client | free | `SB-UMKM-001` (default di DB), data simulasi ada di `SB-8829-102` |
| `sari` | `password` | client | premium | `SB-UMKM-002` (default), data simulasi di `SB-9938-204` |
| `op_jaya` | `password` | operator | — | — |
| `admin_super` | `password` | admin | — | — |

> [!WARNING]
> **Masalah penting:** Data simulasi transaksi di `smartbank_transactions` dan `external_sales` menggunakan ID `SB-8829-102` dan `SB-9938-204`, **BUKAN** `SB-UMKM-001`/`SB-UMKM-002`. Jadi user perlu menghubungkan SmartBank ID yang benar (misal `SB-8829-102`) di halaman Profil, lalu klik Sinkronisasi di Dashboard agar data tampil. Ini perlu disamakan/diperbaiki agar tidak membingungkan.

---

## 3. File-File Penting & Status

### Halaman Utama (Root)
| File | Fungsi | Status |
|---|---|---|
| `login.php` | Halaman login (split-panel design) | ✅ Fixed: layout + theme switcher |
| `register.php` | Halaman registrasi | ✅ Selesai |
| `dashboard.php` | Dashboard utama client (KPI, chart, transaksi) | ✅ Selesai |
| `profile.php` | **BARU** — Pengaturan profil, koneksi SmartBank/WarungPos | ✅ Selesai |
| `pembayaran.php` | **BARU** — Checkout instan via SmartBank | ✅ Selesai |
| `langganan.php` | Halaman tagihan & upload bukti bayar manual | ✅ Updated: tombol SmartBank instan |
| `langganan-admin.php` | Operator: verifikasi bukti bayar | ✅ Selesai |
| `performa-produk.php` | Perbandingan produk Lokal (WarungPos) vs Global (PasarKita) | ✅ Fixed: error kolom `description` |
| `laporan-penjualan.php` | Laporan penjualan detail | ✅ Selesai |
| `arus-kas.php` | Analisis arus kas (premium) | ✅ Selesai |
| `admin.php` | Manajemen user (admin) | ⚠️ Belum ada fitur Add/Delete user |
| `audit-logs.php` | Log aktivitas sistem (admin) | ❌ **PLACEHOLDER** — belum diimplementasi |
| `system-config.php` | Konfigurasi sistem (admin) | ❌ Placeholder |
| `operator.php` | Dashboard operator | ✅ Selesai |
| `pengaduan.php` / `pengaduan-admin.php` | Sistem pengaduan | ✅ Selesai |

### Backend / API
| File | Fungsi | Status |
|---|---|---|
| `api/sync_data.php` | Sinkronisasi data dari SmartBank + WarungPos + PasarKita | ✅ Fixed sesi ini |
| `_archive/simulators/smartbank/api.php` | Endpoint simulasi SmartBank | ✅ Selesai |
| `_archive/simulators/warungpos/api.php` | Endpoint simulasi WarungPos | ✅ Selesai |
| `_archive/simulators/pasarkita/api.php` | Endpoint simulasi PasarKita (global trends) | ✅ Selesai |

### Includes
| File | Fungsi | Catatan |
|---|---|---|
| `includes/auth.php` | Session + middleware role | ⚠️ Sudah ada `session_start()` di sini — **jangan duplikat** |
| `includes/header.php` | `<head>` HTML, Tailwind config, tema gelap/terang | ✅ Updated: icon switcher JS |
| `includes/sidebar.php` | Navigasi sidebar berdasarkan role | ✅ Updated |
| `includes/topbar.php` | Bar atas: tanggal, theme toggle, notifikasi, avatar (link ke profil) | ✅ Updated: avatar → link profil |
| `includes/footer.php` | Penutup HTML | ✅ |

### Database
| File | Fungsi |
|---|---|
| `dokumentasi/database.sql` | Skema awal: `users`, `notifications`, `complaints`, `products`, `transaction_cache`, `tier_requests`, `offers` |
| `dokumentasi/database_patch_2.sql` | Tabel tambahan: `smartbank_accounts`, `smartbank_transactions`, `external_sales`, `subscription_payments`, `market_trends_cache` tanpa dummy data |
| `_archive/maintenance/apply_patch.php` | Script untuk menjalankan patch + ALTER TABLE (sudah dijalankan, diarsipkan) |

### Referensi Proyek (Context)
| File | Isi |
|---|---|
| `Context/Deskripsi Aplikasi RPL - 1. Deskripsi Aplikasi.csv` | Deskripsi semua aplikasi dalam ekosistem RPL |
| `Context/Kebutuhan Fungsional Aplikasi RPL - 2. Fungsional.csv` | Kebutuhan fungsional per aplikasi |
| `Context/ATURAN KEUANGAN DALAM EKOSISTEM RPL - 6. Aturan Keuangan.csv` | Model bisnis & aturan keuangan |
| `Context/evaluasi_proyek_RPL_Fase1.md` | Evaluasi awal: 85% Fase 1 selesai |
| `dokumentasi/planning2.md` | Catatan dari dosen tentang 4 poin pengembangan Fase 2 |
| `dokumentasi/evaluasi_proyek_RPL_Fase2.md` | Evaluasi sesi ini: semua poin planning2 selesai |

---

## 4. Alur Koneksi API (Penting!)

Ini adalah alur yang **sudah berjalan** dan perlu dipahami:

```
1. User buka profile.php
2. Masukkan SmartBank ID (misal: SB-8829-102) + PIN (123456) → Tersimpan di users.smartbank_id
3. Masukkan WarungPos ID (misal: WP-001) + PIN (654321) → Tersimpan di users.warungpos_id
4. User buka dashboard.php → Klik "Sinkronisasi Data"
5. api/sync_data.php berjalan:
   a. Query smartbank_transactions WHERE smartbank_id = [user's ID] → INSERT ke transaction_cache
   b. Query external_sales WHERE smartbank_id = [user's ID] AND source='POS' → INSERT ke transaction_cache
   c. Query external_sales WHERE smartbank_id='GLOBAL' → INSERT ke market_trends_cache
6. Dashboard reload → KPI cards dan tabel transaksi terisi data
```

> [!IMPORTANT]
> Jika user memutus koneksi (tombol "Putus" di profile.php), `smartbank_id` di-NULL-kan dan `transaction_cache` untuk user tersebut di-DELETE. Dashboard kembali 0.

---

## 5. Bug/Masalah yang Diketahui Masih Ada

1. **SmartBank ID default tidak cocok dengan data simulasi**: User `budi` punya `smartbank_id = 'SB-UMKM-001'` di database awal, tapi data transaksi simulasi ada di `SB-8829-102`. Ini membingungkan. **Solusi yang disarankan:** Update `database.sql` seed agar `budi` langsung punya `smartbank_id = 'SB-8829-102'`, atau sebaliknya buat data simulasi untuk `SB-UMKM-001`.

2. **`pembayaran.php` juga punya `session_start()` duplikat** — perlu dihapus (baris 2, sama kasusnya dengan profile.php).

3. **Tema Gelap belum 100% merata** — beberapa halaman mungkin masih ada elemen yang warnanya tidak berubah saat dark mode. Perlu audit visual per halaman.

4. **Mini chart di Dashboard masih statis (placeholder)** — Chart "Arus Kas" dan "Produk" di bawah KPI cards menggunakan data hardcoded, bukan dari database.

---

## 6. Fitur yang Belum Diimplementasikan (Fase 3)

Berdasarkan evaluasi Fase 1 dan permintaan user, berikut yang masih perlu dikerjakan:

### Prioritas Tinggi
1. **Audit Log System** (`audit-logs.php`)
   - Saat ini **placeholder** (hanya tulisan "Modul Sedang Dikembangkan")
   - Perlu: Buat tabel `audit_logs` (sudah ada di `dokumentasi/database_patch.sql` tapi belum dijalankan)
   - Catat event: Login/Logout, Approval tagihan, Sinkronisasi data, Perubahan koneksi API
   - Tampilkan di halaman admin dalam bentuk tabel dengan filter

2. **CRUD User oleh Admin** (`admin.php`)
   - Saat ini admin hanya bisa melihat dan mengubah tier user
   - Perlu: Tambah fitur Create (buat akun operator baru) dan Delete/Nonaktifkan akun

3. **Ekspor Laporan PDF/CSV** (`laporan-penjualan.php`)
   - Fitur ekspor tabel penjualan ke file CSV atau PDF
   - Nilai plus tinggi untuk demo ke dosen

### Prioritas Sedang
4. **Sinkronisasi SmartBank ID agar konsisten** — Samakan ID di seed data dengan ID di simulasi
5. **File tidak terpakai sudah diarsipkan**: `apply_patch.php` dan `run_patch.php` dipindah ke `_archive/maintenance/`; `dashboard1.php` sudah tidak ada di folder utama.

---

## 7. Konvensi Kode yang Harus Diikuti

1. **JANGAN panggil `session_start()`** di file halaman manapun — `includes/auth.php` sudah menanganinya
2. **Selalu include urutan**: `header.php` → `sidebar.php` → `topbar.php` (untuk halaman yang punya layout dashboard)
3. **Gunakan `requireRole('client'|'operator'|'admin')`** di awal file untuk proteksi halaman
4. **Gunakan `getCurrentUser($pdo)`** untuk mengambil data user yang sedang login
5. **Gunakan `formatRupiah()`** dari `config/db.php` untuk format mata uang
6. **Dark mode**: Gunakan CSS variables (`var(--surface)`, `var(--card)`, dll) dan class Tailwind `dark:` prefix
7. **PIN Simulasi**: SmartBank = `123456`, WarungPos = `654321`

---

## 8. Cara Menjalankan Proyek

1. Letakkan folder proyek di root web server (XAMPP: `htdocs/RPL/`)
2. Import `dokumentasi/database.sql` ke MySQL (buat database `umkm_insight`)
3. Jalankan `dokumentasi/database_patch_2.sql` (atau akses `_archive/maintenance/apply_patch.php` di browser bila masih diperlukan)
4. Akses `http://localhost/RPL/login.php`
5. Login dengan akun demo (lihat tabel di Bagian 2)
