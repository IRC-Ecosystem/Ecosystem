# Status Implementasi FR-POS WarungPOS

Dokumen ini merangkum kesesuaian aplikasi POS terhadap Functional Requirement WarungPOS pada PRD, khususnya **FR-POS-001 sampai FR-POS-007**.

## Ringkasan Status

| FR | Requirement | Status | Bukti / Catatan |
| --- | --- | --- | --- |
| FR-POS-001 | Kasir dapat membuat invoice dari produk dan jumlah. | Sesuai | Ada `POST /kasir/direct-sale`; kasir memilih produk dan quantity, lalu sistem membuat invoice berstatus `pending_payment`. |
| FR-POS-002 | POS menggunakan sumber stok yang sama dengan Marketplace. | Sebagian sesuai | POS dan katalog konsumen memakai tabel `products` yang kini memiliki `on_hand`, `reserved`, dan `available`. Belum ada Inventory Module eksternal/shared lintas sistem. |
| FR-POS-003 | Sistem mendukung pembayaran SmartBank dan pembayaran tunai simulasi. | Sebagian sesuai | Jalur yang aktif di kode sekarang adalah SmartBank Connector. Payment disimpan di tabel `payments` dengan idempotency key dan response provider. Cash/QRIS/transfer tidak lagi menjadi jalur payment aktif di backend. |
| FR-POS-004 | Invoice digital hanya `PAID` jika payment sukses. | Sesuai | Order konsumen berjalan `pending -> approved -> paid`, sedangkan direct sale kasir berjalan `pending_payment -> paid` setelah pembayaran diproses. |
| FR-POS-005 | Sistem harus mencegah penjualan melebihi stock available. | Sebagian sesuai | Checkout dan direct sale melakukan atomic reservation terhadap `on_hand - reserved`; settlement mengonsumsi reservation. Namun belum ada uji transaksi paralel end-to-end. |
| FR-POS-006 | Sistem mendukung void sebelum settlement dengan permission supervisor. | Sebagian sesuai | Manager dapat void transaksi `pending`, `approved`, atau `pending_payment` melalui dashboard. Transaksi `paid` wajib menggunakan refund, dan belum ada role supervisor granular. |
| FR-POS-007 | Semua void, diskon, dan koreksi harus diaudit. | Sebagian sesuai | Void tercatat atomik di `audit_logs` beserta pelaku, alasan, IP, user agent, dan status sebelum/sesudah. Audit aksi diskon/koreksi serta aksi penting lain belum lengkap. |

## FR yang Sudah Paling Sesuai

| FR | Alasan |
| --- | --- |
| FR-POS-001 | Kasir dapat membuat invoice direct sale dari produk dan quantity tanpa langsung menandai transaksi sebagai `paid`. |
| FR-POS-004 | Status `paid` baru diberikan setelah proses pembayaran berhasil. |
| FR-POS-005 | Validasi stok sudah dilakukan sebelum transaksi/payment dan stok dipotong saat transaksi `paid`. |

## Perbaikan Hari Ini

| FR | Area | Perbaikan | Detail Implementasi | Role Terdampak | Status |
| --- | --- | --- | --- | --- | --- |
| FR-POS-001 | Alur invoice kasir | Invoice kasir dibuat sebelum pembayaran. | Kasir memilih produk dan quantity, lalu sistem membuat invoice direct sale dengan status `pending_payment`. Invoice belum memerlukan metode pembayaran di tahap ini. | Kasir | Selesai |
| FR-POS-001 | UI kasir | Dashboard kasir dibuat ulang agar menyerupai alur kasir toko offline. | Halaman transaksi dipisah menjadi `Pilih Produk`, `Invoice Aktif`, dan `Antrian Bayar`. Transaksi masuk dipindahkan ke menu sidebar sendiri. | Kasir | Selesai |
| FR-POS-001 | UI produk | Nama produk ditampilkan jelas dan tidak dipotong. | Kartu produk menampilkan nama lengkap, kategori, stok, harga, serta kontrol tambah/kurang quantity. Jika produk punya `gambar`, gambar dipakai; jika tidak, fallback inisial tetap tersedia. | Kasir | Selesai |
| FR-POS-001 | Konfirmasi invoice | Notifikasi bawaan browser diganti modal custom. | Konfirmasi `Buat Invoice` tidak lagi memakai `window.confirm` bawaan Chrome, tetapi memakai modal yang sesuai UI aplikasi. | Kasir | Selesai |
| FR-POS-003 | Database payment | Payment dipisah dari transaksi. | Ditambahkan tabel `payments` untuk menyimpan `transaction_id`, provider, method, status, amount, payment request ID, response code/body, kasir, dan waktu bayar. | Kasir, Manager, Konsumen | Selesai |
| FR-POS-003 | Model payment | Model khusus payment dibuat. | Ditambahkan `models/paymentModel.js` untuk membuat record payment dan mengambil payment success terbaru per transaksi. | Sistem | Selesai |
| FR-POS-003 | Pembayaran lokal | Cash, QRIS, dan transfer tidak menjadi jalur aktif. | `PaymentModel.create()` menolak payment lokal dan mewajibkan provider SmartBank Connector. Dokumentasi/UI harus menghindari klaim bahwa payment lokal masih tersedia. | Kasir, Manager | Tidak aktif |
| FR-POS-003 | SmartBank | Jalur POS untuk SmartBank sudah terintegrasi fungsional. | POS mengambil endpoint aktif dari API Integrator, melakukan call external, dan menyimpan status transaksi, `payment_request_id`, reference, dan response body ke database secara sukses. | Kasir, Manager | Selesai |
| FR-POS-003 | UI manager payment | Riwayat payment dipisah dari dashboard utama. | Ditambahkan halaman `/manager/payments` dengan ringkasan total payment, success, failed, SmartBank, dan tabel detail payment. | Manager | Selesai |
| FR-POS-003 | Sidebar manager | Menu manager dirapihkan. | Sidebar manager sekarang memisahkan `Dashboard`, `Payment`, dan `API Integrator`. Active state sidebar dibuat exact supaya halaman Payment tidak terbaca sebagai Dashboard. | Manager | Selesai |
| FR-POS-004 | Guard status paid | Transaksi tidak bisa menjadi `paid` tanpa payment success. | `TransactionModel.payTransaction()` mengecek keberadaan `payments.status = 'success'` untuk transaksi dan metode pembayaran yang diproses. Jika tidak ada, update ke `paid` ditolak dengan alasan `payment_not_success`. | Sistem | Selesai |
| FR-POS-004 | Struk kasir | Struk kasir menampilkan detail payment. | Struk kasir menampilkan status payment, provider, waktu bayar, request ID/reference, dan response code jika tersedia. | Kasir | Selesai |
| FR-POS-004 | Struk konsumen | Struk konsumen menampilkan detail payment. | Struk konsumen dan PDF receipt ikut membaca payment success terbaru agar pembeli melihat bukti pembayaran yang sama. | Konsumen | Selesai |
| FR-POS-005 | Validasi stok | Stok dicek saat invoice dan saat payment. | Sistem mengecek stok sebelum invoice dibuat dan mengecek ulang stok saat pembayaran. Stok hanya dipotong ketika transaksi berhasil `paid`. | Kasir, Sistem | Sudah sesuai |
| FR-POS-006 | Void sebelum settlement | Manager dapat membatalkan transaksi belum dibayar. | `POST /manager/transactions/:id/void` menerima alasan 3-500 karakter, hanya untuk `pending`, `approved`, atau `pending_payment`; transaksi `paid` harus refund. Karena stok baru dipotong saat settlement, void tidak memerlukan restore stok. | Manager | Sebagian selesai |
| FR-POS-007 | Audit log void | Jejak void dapat diperiksa manager. | `transaction.voided` ditulis dalam transaksi database yang sama ke `audit_logs`; halaman `/manager/audit-logs` menampilkan pelaku, alasan, metadata request, serta data sebelum/sesudah. Audit approve, reject, payment, refund, stok, dan endpoint belum tersedia. | Manager, Sistem | Sebagian selesai |

## Validasi Hari Ini

| Validasi | Hasil |
| --- | --- |
| `node --check` controller/model terkait | Lolos |
| Compile EJS view kasir, konsumen, manager | Lolos |
| Render dummy dashboard/payment/receipt | Lolos |
| Migration database lokal | Tabel `payments`, `refunds`, `outbox_events`, dan `payment_reconciliation` tersedia di schema |
| `npm test` | Berjalan, tetapi project masih berisi placeholder `No automated tests configured yet` |

## FR yang Berjalan tetapi Belum 100% Sesuai PRD

| FR | Kekurangan Utama |
| --- | --- |
| FR-POS-002 | Masih memakai `products.stock`, belum Inventory Module shared formal. |

## FR yang Belum Ada

| FR | Yang Perlu Dibuat |
| --- | --- |
| FR-POS-006 | Role supervisor granular dan pengujian void paralel. Restore stok tidak diperlukan pada void saat ini karena void dibatasi sebelum settlement. |
| FR-POS-007 | Pencatatan audit untuk approve, reject, payment, refund, stok, produk, endpoint API, diskon, dan koreksi. |

## Referensi File Implementasi

| Area | File |
| --- | --- |
| Route kasir | `routes/kasirRoutes.js` |
| Controller kasir | `controllers/kasirController.js` |
| Model transaksi | `models/transactionModel.js` |
| Model payment | `models/paymentModel.js` |
| Schema database | `database/migrate_to_current_schema.sql` |
| Dashboard kasir | `views/kasir/dashboard.ejs` |
| Receipt kasir | `views/kasir/receipt.ejs` |
| Receipt konsumen | `views/konsumen/receipt.ejs` |
| Controller konsumen | `controllers/konsumenController.js` |
| Route manager | `routes/managerRoutes.js` |
| Controller manager | `controllers/managerController.js` |
| Dashboard manager | `views/manager/dashboard.ejs` |
| Payment manager | `views/manager/payments.ejs` |
| Sidebar shared | `views/partials/sidebar.ejs` |
