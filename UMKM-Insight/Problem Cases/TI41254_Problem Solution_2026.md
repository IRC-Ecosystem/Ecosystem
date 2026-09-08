# TI41254 Software Engineering 2 — Jawaban ATS 2026

**Nama Aplikasi:** UMKM Insight  
**Role dalam Ekosistem:** Sistem Analitik & Pelaporan Keuangan UMKM  
**Bahasa/Stack:** PHP 8, MySQL, REST API Integration  

---

## Soal 1 — Nama & Deskripsi Aplikasi

### Nama Aplikasi: **UMKM Insight**

**UMKM Insight** adalah sebuah platform analitik dan manajemen keuangan berbasis web yang dirancang khusus untuk para pelaku Usaha Mikro, Kecil, dan Menengah (UMKM) yang berpartisipasi dalam ekosistem UMKM digital.

Aplikasi ini berfungsi sebagai **pusat intelijen bisnis (Business Intelligence Hub)** yang mengumpulkan, memproses, dan memvisualisasikan data transaksi dari berbagai sistem yang beroperasi di dalam ekosistem — termasuk Marketplace, Point of Sale (POS), SupplierHub, dan SmartBank — kemudian menyajikannya kepada pemilik UMKM dalam bentuk laporan yang mudah dipahami.

**Fitur Utama:**
| Fitur | Deskripsi | Tier |
|---|---|---|
| Dashboard Ringkasan | Menampilkan KPI utama: total pendapatan, pengeluaran, laba bersih | Free & Premium |
| Laporan Penjualan | Analisis tren pendapatan per periode, per produk, per sumber | Premium |
| Arus Kas | Visualisasi cash flow masuk/keluar secara kronologis | Premium |
| Performa Produk | Analisis produk terlaris dan margin keuntungan | Premium |
| Notifikasi Cerdas | Peringatan dari sistem Operator/Admin tentang perubahan layanan | Semua |
| Pengaduan | Kanal komunikasi langsung antara UMKM dan tim Operator | Semua |
| Manajemen Tier | Upgrade akun dari Free ke Premium melalui integrasi SmartBank | Semua |

### Teknologi Stack & Target Pengguna
- **Teknologi:** PHP 8.x Native, MySQL, Tailwind CSS, Chart.js (Visualisasi), Phosphor Icons.
- **Arsitektur:** MVC (Model-View-Controller) dengan integrasi REST API.
- **Target User:**
    - **Admin:** Monitoring audit trail dan konfigurasi sistem global.
    - **Operator:** Manajemen langganan UMKM dan penanganan pengaduan.
    - **UMKM (Client):** Pengguna utama fitur analitik bisnis.

**Peran dalam Ekosistem:**
UMKM Insight bukan aplikasi transaksional; ia adalah **consumer** dari data yang dihasilkan oleh aplikasi lain. Posisinya dalam ekosistem adalah sebagai lapisan analitik (analytics layer) di atas infrastruktur transaksi yang disediakan oleh Marketplace, POS, SupplierHub, dan SmartBank.

---

## Soal 2 [Bobot 50] — Analisis Proses Transaksi End-to-End

> **Konteks:** Sebagai developer utama UMKM Insight, saya akan menganalisis alur transaksi utama, yaitu ketika seorang UMKM (pengguna) melakukan **upgrade akun dari Free ke Premium** dengan pembayaran melalui SmartBank.

---

### 1. Input Utama yang Diterima Aplikasi

UMKM Insight menerima dua jenis input utama:

**A. Data Pengguna (User-Initiated):**
- Login credentials (username, password)
- Permintaan upgrade tier (klik tombol "Upgrade ke Premium") di dashboard
- Data identitas pengguna: `user_id`, `smartbank_id`, `tier_saat_ini`
- Permintaan laporan/filter tanggal dari fitur analitik

**B. Data Transaksi (Dari SmartBank via API Gateway):**
- Data transaksi dari **SmartBank** (berisi: amount, status, external_id, product_id, transaction_date)
- Notifikasi status pembayaran dari **SmartBank** setelah payment settlement
- Data sinkronisasi produk/stok dari **SupplierHub/Marketplace** (jika ada perubahan)

**Struktur data input transaksi yang masuk ke `transaction_cache`:**
```json
{
  "external_id": "TX-MKT-20260512-0041",
  "user_id": 1,
  "product_id": 3,
  "type": "Income",
  "source": "Marketplace",
  "amount": 450000.00,
  "status": "Success",
  "transaction_date": "2026-05-12T08:30:00Z"
}
```

---

### 2. API yang Perlu Dipanggil ke Sistem Lain

Berikut adalah peta integrasi API UMKM Insight dengan sistem-sistem lain di ekosistem:

```
UMKM Insight
    │
    ├──[PULL]──► SmartBank API (GET /api/transactions, POST /api/payment/initiate)
    │
    ├──[PULL]──► Marketplace API (GET /api/v1/marketplace/transactions)
    │
    ├──[PULL]──► SupplierHub API (GET /api/v1/supplierhub/stock)
    │
    └──[PUSH]──► SmartBank Webhook (Menerima POST update status pembayaran)
```

---

### 3. Data yang Dikirim dan Diterima

#### Skenario Transaksi: Upgrade Tier & Sinkronisasi Penjualan
**Flow Diagram (ASCII Art):**
```
┌─────────────────────────────────────────────────────────────┐
│                    Pengguna UMKM (Client)                   │
└────────────────┬────────────────────────────────────────────┘
                 │ 1. Request Upgrade Tier
                 ▼
┌──────────────────────────────────────────┐
│      UMKM Insight (App Utama)            │
└───────────────────┬──────────────────────┘
                    │ 2. POST Initiate Payment
                    ▼
┌──────────────────────────────────────────┐
│        SmartBank (Payment Processor)     │
└───────────────────┬──────────────────────┘
                    │ 3. Update Status (Success)
                    ▼
┌──────────────────────────────────────────┐
│  UMKM Insight (Webhook Listener)         │
│  - Validate JWT & Signature              │
│  - Update DB (Tier: Premium)             │
│  - Sync Data Marketplace/POS             │
└──────────────────────────────────────────┘
```

#### Data Flow Detail:
**A. Saat Pengguna Memulai Upgrade Tier (UMKM Insight → SmartBank):**
Data yang **dikirim** (`POST /api/payment/initiate`):
```json
{
  "payer_id": "SB-UMKM-001",
  "receiver_id": "SB-SYSTEM-INSIGHT",
  "amount": 50000,
  "currency": "IDR",
  "reference_id": "TIER-REQ-14",
  "callback_url": "https://umkm-insight.id/api/webhook/smartbank"
}
```

Data yang **diterima** (response SmartBank):
```json
{
  "payment_ref": "PAY-SB-20260512-9921",
  "status": "pending",
  "payment_url": "https://smartbank.id/pay/9921",
  "expires_at": "2026-05-12T09:15:00Z"
}
```

**B. Saat SmartBank Mengirim Konfirmasi (SmartBank → UMKM Insight Webhook):**
```json
{
  "event": "payment.success",
  "payment_ref": "PAY-SB-20260512-9921",
  "reference_id": "TIER-REQ-14",
  "amount": 50000,
  "paid_at": "2026-05-12T08:47:22Z",
  "signature": "hmac-sha256:a7f8c..."
}
```

---

### 4. Mekanisme Validasi JWT/Token

UMKM Insight mengimplementasikan mekanisme keamanan berlapis:

**A. Autentikasi Sesi Internal (PHP Session + Role-Based):**
```php
// Setiap halaman private diperlindungi oleh includes/auth.php
function requireRole(string $role): void {
    if (!isLoggedIn()) { header('Location: login.php'); exit; }
    if ($_SESSION['role'] !== $role) { header('Location: 403.php'); exit; }
}
```

**B. Validasi JWT untuk Komunikasi Antar-Layanan:**
Setiap request keluar ke API Gateway harus menyertakan JWT Bearer Token:
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

Proses validasi JWT pada sisi UMKM Insight:
1. **Signature Verification:** Memverifikasi tanda tangan JWT menggunakan secret key bersama
2. **Claim Validation:** Memeriksa `exp` (expiry), `iss` (issuer), dan `sub` (subject user)
3. **Role Claim Check:** Memastikan claim `role` dalam token sesuai dengan operasi yang diminta
4. **Token Refresh:** Jika token mendekati expiry, lakukan refresh sebelum request dikirim

**C. Validasi Webhook dari SmartBank:**
Untuk mencegah spoofing, setiap webhook divalidasi dengan HMAC-SHA256:
```php
function validateWebhookSignature(string $payload, string $signature): bool {
    $expected = hash_hmac('sha256', $payload, SMARTBANK_WEBHOOK_SECRET);
    return hash_equals($expected, $signature);
}
```

---

### 5. Risiko Inkonsistensi Data yang Mungkin Terjadi

| # | Skenario Risiko | Dampak | Kemungkinan |
|---|---|---|---|
| 1 | Webhook SmartBank diterima dua kali (duplikat) | Double upgrade tier untuk satu pembayaran | Sedang |
| 2 | Network timeout saat sinkronisasi transaksi | Data di `transaction_cache` tidak up-to-date, laporan tidak akurat | Tinggi |
| 3 | SmartBank konfirmasi pembayaran tapi tier belum terupdate di DB | User membayar tapi akun masih Free | Rendah-Sedang |
| 4 | Race condition saat dua request upgrade datang bersamaan | Duplikasi entri `tier_requests` untuk satu user | Rendah |
| 5 | Perbedaan zona waktu antara sistem | Data transaksi ter-sort salah, laporan periode meleset | Sedang |
| 6 | Data produk dari SupplierHub berubah setelah transaksi dicatat | Ketidaksesuaian antara harga aktual dan harga tercatat | Sedang |

---

### 6. Dampak Jika Salah Satu Aplikasi Lain Mengalami Kegagalan

| Aplikasi Gagal | Dampak pada UMKM Insight | Severity |
|---|---|---|
| **SmartBank DOWN** | Upgrade tier tidak bisa diproses. Data transaksi tidak bisa disinkronisasi. Laporan keuangan berhenti diperbarui. | 🔴 Kritis |
| **Marketplace DOWN** | Data penjualan dari kanal marketplace tidak masuk ke analytics. Laporan tren menjadi parsial. | 🟡 Sedang |
| **POS DOWN** | Sama seperti marketplace, data dari kanal POS hilang sementara. | 🟡 Sedang |
| **SupplierHub DOWN** | Data stok dan produk tidak tersinkronisasi. Fitur performa produk menjadi stale. | 🟢 Rendah |
| **API Gateway DOWN** | Seluruh komunikasi ke sistem eksternal terputus. UMKM Insight beroperasi hanya dengan data lokal (cache). | 🔴 Kritis |

---

### 7. Strategi Agar Sistem Tetap Robust

**A. Circuit Breaker Pattern:**
Jika API eksternal (SmartBank/Gateway) gagal lebih dari N kali dalam periode T, matikan sementara request ke API tersebut dan kembalikan respons cached/default. Cegah cascade failure.

```
Request → [Circuit Breaker] → API Eksternal
              ↓ (jika OPEN)
          Return cached response / graceful degradation
```

**B. Idempotency Key:**
Setiap transaksi upgrade tier menggunakan `reference_id` unik. Sebelum memproses webhook, cek apakah `reference_id` sudah pernah diproses:
```php
// Cek idempotency sebelum proses
$existing = $pdo->prepare("SELECT id FROM tier_requests WHERE id = ? AND status = 'approved'");
$existing->execute([$tierId]);
if ($existing->fetch()) { http_response_code(200); exit; } // Sudah diproses, skip
```

**C. Retry dengan Exponential Backoff:**
Untuk sinkronisasi data yang gagal, jadwalkan ulang dengan jeda yang semakin panjang (1s → 2s → 4s → 8s) agar tidak membanjiri sistem yang sedang pulih.

**D. Data Caching Lokal:**
Tabel `transaction_cache` di database lokal berfungsi sebagai buffer. Data dari SmartBank/Marketplace disimpan secara periodik, sehingga laporan tetap bisa ditampilkan meskipun sistem eksternal sedang tidak tersedia.

**E. Health Check & Monitoring:**
Implementasikan endpoint `/health` yang mengecek konektivitas ke SmartBank dan API Gateway secara real-time. Jika koneksi gagal, tampilkan banner peringatan di dashboard user.

---

## Soal 3 [Bobot 50] — Analisis Sistem Saat Kondisi Krisis

> **Konteks:** Terjadi lonjakan transaksi besar. SmartBank delay, Marketplace tetap checkout, SupplierHub stok terbatas, dan LogistiKita keterlambatan sinkronisasi. Berikut adalah respons UMKM Insight terhadap kondisi ini.

---

### Respons UMKM Insight Terhadap Kondisi Krisis

#### Kondisi 1: SmartBank Mengalami Delay Validasi Pembayaran

**Dampak ke UMKM Insight:**
- Data transaksi baru dari SmartBank tidak masuk ke `transaction_cache`
- Jika ada user yang sedang dalam proses upgrade tier, statusnya akan menggantung (`pending`)
- Laporan keuangan real-time tidak diperbarui

**Respons yang seharusnya dilakukan:**

1. **Aktifkan Mode Degraded (Graceful Degradation):**
   - Tampilkan banner di dashboard: *"Data transaksi Anda sedang diperbarui. Laporan mungkin belum mencerminkan transaksi terbaru. Kami sedang memantau situasi."*
   - Tetap tampilkan data dari `transaction_cache` yang terakhir tersimpan

2. **Hentikan Inisiasi Transaksi Baru:**
   - Nonaktifkan tombol "Upgrade ke Premium" sementara
   - Tampilkan pesan: *"Layanan pembayaran sedang dalam pemeliharaan. Coba lagi dalam beberapa menit."*

3. **Queue Sinkronisasi:**
   - Catat semua request sinkronisasi yang gagal dalam antrian job
   - Ketika SmartBank pulih, proses antrian secara berurutan dengan idempotency check

```
[SmartBank Delay Detected]
        ↓
Set flag: SMARTBANK_HEALTH = false
        ↓
Disable payment initiation buttons
        ↓
Show degraded mode banner to all active users
        ↓
Queue sync jobs for retry (max 5 attempts with backoff)
        ↓
Poll SmartBank /health endpoint every 30 seconds
        ↓
[SmartBank Recovered] → Process queued jobs → Reset flag
```

---

#### Kondisi 2: Marketplace Tetap Menerima Checkout

**Dampak ke UMKM Insight:**
- Volume data transaksi yang perlu disinkronisasi bertambah besar
- Risiko data masuk dalam kondisi partial (sebagian settlement, sebagian pending)

**Respons:**

1. **Tandai Semua Transaksi Marketplace sebagai `pending_sync`:**
   - Saat SmartBank delay, transaksi dari Marketplace yang belum dikonfirmasi SmartBank harus disimpan dengan status `pending`, bukan `Success`

2. **Validasi Berlapis sebelum Tampil di Laporan:**
   - Laporan hanya menghitung transaksi dengan `status = 'Success'`
   - Transaksi `pending` ditampilkan di bagian terpisah: *"Transaksi Menunggu Konfirmasi"*

3. **Konsistensi Data:**
   - Gunakan `external_id` sebagai primary key unik untuk setiap transaksi dari Marketplace
   - Jika transaksi yang sama datang dua kali (karena retry Marketplace), sistem melakukan `INSERT IGNORE` atau `ON DUPLICATE KEY UPDATE`

---

#### Kondisi 3: SupplierHub Memiliki Stok Terbatas

**Dampak ke UMKM Insight:**
- Data stok produk di fitur "Performa Produk" bisa tidak akurat
- UMKM Insight tidak terlibat dalam keputusan stok, tetapi perlu menampilkan informasi ini dengan benar

**Respons:**

1. **Sinkronisasi Stok Ditunda ke Jadwal Berikutnya:**
   - Jika sinkronisasi gagal karena SupplierHub overload, jadwalkan ulang dan tampilkan label *"Terakhir diperbarui: [timestamp]"* di samping data stok

2. **Tidak Mengurangi Stok Palsu:**
   - UMKM Insight **tidak** melakukan operasi pengurangan stok sendiri
   - Semua perubahan stok adalah data yang diterima dari SupplierHub, bukan dihitung ulang oleh UMKM Insight
   - Ini mencegah **phantom stock reduction** (pengurangan stok palsu)

---

#### Kondisi 4: LogistiKita Mengalami Keterlambatan Sinkronisasi Ongkir

**Dampak ke UMKM Insight:**
- Komponen biaya pengiriman dalam laporan keuangan UMKM mungkin belum tercatat
- Laporan arus kas bisa menampilkan pengeluaran yang belum akurat

**Respons:**

1. **Pisahkan Kategori Ongkir dari Laporan Utama:**
   - Buat kategori tersendiri: `source = 'Logistik'` dalam `transaction_cache`
   - Jika data dari LogistiKita terlambat, laporan utama tetap konsisten; hanya komponen logistik yang perlu diperbarui

2. **Notifikasi ke User:**
   - *"Catatan: Data biaya pengiriman mungkin belum sepenuhnya tersinkronisasi. Laporan akan diperbarui otomatis."*

---

### Memastikan 6 Kondisi Sistem Terjaga

| # | Syarat | Strategi UMKM Insight |
|---|---|---|
| 1 | **Transaksi ekonomi tetap konsisten** | Gunakan `transaction_cache` sebagai buffer lokal; hanya tampilkan data `status=Success` |
| 2 | **Tidak ada double transaction** | Idempotency key via `external_id` unik; cek duplikat sebelum `INSERT` |
| 3 | **Tidak ada pengurangan stok palsu** | UMKM Insight read-only terhadap stok; tidak pernah menulis ke tabel stok langsung |
| 4 | **Sistem tetap scalable** | Circuit breaker + queue-based sync; operasi berat dijalankan sebagai background job |
| 5 | **User mendapat feedback jelas** | Banner degraded mode, label "terakhir diperbarui", disable tombol saat layanan tidak tersedia |
| 6 | **Tidak ada cascade failure** | Isolasi setiap integrasi; kegagalan SmartBank tidak menyebabkan dashboard down; selalu serve cached data |

---

### Komponen Paling Kritis dalam UMKM Insight

**1. `transaction_cache` (Database Table) — PALING KRITIS**
Ini adalah jantung dari UMKM Insight. Semua data analitik bersumber dari tabel ini. Jika tabel ini korup atau data di dalamnya tidak konsisten, seluruh laporan akan salah. Harus dilindungi dengan:
- Foreign key constraint yang ketat
- Backup berkala
- Write-through validation sebelum INSERT

**2. `config/db.php` — Koneksi & Helper**
Titik tunggal konfigurasi database. Jika PDO connection pool habis saat lonjakan transaksi, seluruh aplikasi akan down. Perlu connection pooling dan retry logic.

**3. Webhook Handler (`/api/webhook/smartbank`) — KRITIS untuk Tier Upgrade**
Endpoint ini adalah satu-satunya pintu masuk konfirmasi pembayaran. Kegagalan di sini langsung berdampak pada user yang tidak mendapatkan akses Premium meski sudah membayar.

---

### Endpoint/API yang Harus Diprioritaskan

| Prioritas | Endpoint | Alasan |
|---|---|---|
| 🔴 P1 | `POST /api/webhook/smartbank` | Konfirmasi pembayaran — data keuangan sensitif |
| 🔴 P1 | `GET /dashboard` | Entry point utama user; harus selalu tersedia |
| 🟡 P2 | `GET /api/transactions/sync` | Sinkronisasi data; bisa di-queue jika sistem sibuk |
| 🟡 P2 | `POST /tier-requests` | Pengajuan upgrade; harus atomic dengan validasi ketat |
| 🟢 P3 | `GET /laporan-penjualan` | Laporan analitik; bisa serve dari cache |
| 🟢 P3 | `GET /performa-produk` | Data stok; bisa stale beberapa menit |

---

### Log yang Wajib Dicatat

```
[LEVEL]  [TIMESTAMP]           [CONTEXT]
─────────────────────────────────────────────────────────────────
ERROR    2026-05-12T08:47:22Z  [SmartBank] Connection timeout after 5000ms. Retry #1.
WARNING  2026-05-12T08:47:55Z  [TierUpgrade] Payment PAY-SB-9921 status=pending > 5min.
INFO     2026-05-12T08:48:01Z  [Webhook] Received payment.success for TIER-REQ-14.
INFO     2026-05-12T08:48:01Z  [Idempotency] TIER-REQ-14 not yet processed. Proceeding.
INFO     2026-05-12T08:48:02Z  [DB] User #1 tier updated: free → premium. Expiry: 2026-06-12.
ERROR    2026-05-12T08:50:10Z  [Sync] Marketplace sync failed: HTTP 503. Queued for retry.
WARNING  2026-05-12T08:55:00Z  [CircuitBreaker] SmartBank API: OPEN (5 failures in 60s).
INFO     2026-05-12T09:10:00Z  [CircuitBreaker] SmartBank API: HALF-OPEN. Testing...
INFO     2026-05-12T09:10:02Z  [CircuitBreaker] SmartBank API: CLOSED. Resuming normal ops.
```

**Kategori log minimum yang wajib ada:**
1. **Authentication log** — setiap percobaan login (berhasil/gagal)
2. **Webhook receive log** — setiap webhook diterima beserta payload-nya
3. **Idempotency check log** — apakah transaksi sudah pernah diproses
4. **External API call log** — setiap call ke SmartBank/Gateway (latency, status code)
5. **Circuit breaker state log** — perubahan state CLOSED/OPEN/HALF-OPEN
6. **Database write log** — setiap perubahan data yang bersifat finansial/tier
7. **Error & exception log** — stack trace lengkap untuk setiap exception yang tidak tertangani

---

### Bagaimana Prinsip Clean Architecture & SOLID Membantu

#### Clean Architecture

UMKM Insight dapat distrukturisasi dalam lapisan berikut:

```
┌─────────────────────────────────────┐
│  Presentation Layer (PHP Views)     │  ← dashboard.php, laporan.php
│  (Tidak tahu soal DB atau API)      │
├─────────────────────────────────────┤
│  Application Layer (Use Cases)      │  ← UpgradeTierUseCase, SyncTransactionJob
│  (Orkestrasi logika bisnis)         │
├─────────────────────────────────────┤
│  Domain Layer (Entities & Rules)    │  ← User, Transaction, TierRequest (pure logic)
│  (Tidak bergantung framework apapun)│
├─────────────────────────────────────┤
│  Infrastructure Layer               │  ← SmartBankApiClient, TransactionRepository
│  (Database, API, File System)       │
└─────────────────────────────────────┘
```

**Manfaat saat krisis:**
- Jika SmartBank diganti vendor lain, hanya `Infrastructure Layer` yang berubah. Logika bisnis tetap sama.
- Presentation layer tidak pernah bicara langsung ke database → tidak ada SQL injection via view
- Testing lebih mudah: domain layer bisa di-test tanpa koneksi nyata ke SmartBank

#### Prinsip SOLID dalam Konteks Krisis

| Prinsip | Penerapan di UMKM Insight | Manfaat saat Krisis |
|---|---|---|
| **S** — Single Responsibility | `SmartBankClient` hanya urus koneksi API. `TierUpgradeService` hanya urus logika upgrade. | Saat SmartBank down, kita tahu persis kelas mana yang perlu diperbaiki |
| **O** — Open/Closed | `PaymentGateway` interface terbuka untuk ekstensi (tambah GoPay, OVO), tertutup untuk modifikasi | Bisa swap payment provider tanpa ubah kode bisnis |
| **L** — Liskov Substitution | `CachedTransactionRepository` bisa menggantikan `LiveTransactionRepository` | Saat API down, langsung ganti ke cached version tanpa ubah consumer |
| **I** — Interface Segregation | Pisahkan `ITransactionReader` dan `ITransactionWriter` | Laporan (read-only) tidak terpengaruh jika writer sedang overload |
| **D** — Dependency Inversion | `DashboardService` bergantung pada abstraksi `ITransactionRepository`, bukan implementasi konkret | Bisa inject mock/cache saat testing atau saat sistem eksternal down |

**Contoh penerapan D (Dependency Inversion) untuk mengatasi krisis:**
```php
// Interface (Domain Layer)
interface ITransactionRepository {
    public function getByUser(int $userId, string $from, string $to): array;
}

// Implementasi Normal (Infrastructure)
class SmartBankTransactionRepository implements ITransactionRepository {
    public function getByUser(int $userId, string $from, string $to): array {
        // Fetch dari SmartBank API
    }
}

// Implementasi Fallback (Infrastructure) — digunakan saat SmartBank down
class CachedTransactionRepository implements ITransactionRepository {
    public function getByUser(int $userId, string $from, string $to): array {
        // Fetch dari transaction_cache di DB lokal
    }
}

// Use Case tidak perlu tahu mana yang aktif
class GetDashboardUseCase {
    public function __construct(private ITransactionRepository $repo) {}
    
    public function execute(int $userId): array {
        return $this->repo->getByUser($userId, '...', '...');
    }
}

// Saat sistem normal:
$service = new GetDashboardUseCase(new SmartBankTransactionRepository());

// Saat SmartBank down — HANYA satu baris yang berubah!
$service = new GetDashboardUseCase(new CachedTransactionRepository());
```

Dengan desain ini, **UMKM Insight dapat beralih ke mode degraded secara otomatis** hanya dengan mengganti implementasi repository — tanpa mengubah satu baris logika bisnis pun. Inilah kekuatan sesungguhnya dari Clean Architecture dan SOLID dalam menghadapi kondisi krisis ekosistem.

---
