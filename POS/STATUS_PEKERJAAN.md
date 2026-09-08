# Status Pekerjaan WarungPOS

Dokumen ini merangkum pekerjaan yang sudah dikerjakan dan yang belum dikerjakan pada aplikasi WarungPOS berdasarkan kondisi implementasi saat ini dan perbandingan dengan PRD ekosistem UMKM.

## Yang Sudah Dikerjakan

1. **Auth dan role dasar**
   - Login user.
   - Register user.
   - Logout user.
   - Session login.
   - Pembatasan akses berdasarkan role `manager`, `operator`, `kasir`, dan `konsumen`.

2. **Manajemen produk dan stok dasar**
   - Operator dapat menambah produk.
   - Operator dapat mengedit produk.
   - Operator dapat menghapus produk.
   - Operator dapat memperbarui stok produk.
   - Produk memiliki nama, harga, stok, kategori, dan gambar.

3. **Katalog konsumen**
   - Konsumen dapat melihat katalog produk.
   - Konsumen dapat mencari produk.
   - Konsumen dapat melakukan filter berdasarkan kategori.
   - Konsumen dapat menambahkan produk ke cart.
   - Konsumen dapat mengubah quantity cart.
   - Konsumen dapat menghapus item dari cart.

4. **Checkout konsumen**
   - Konsumen dapat checkout dari cart.
   - Sistem membuat invoice transaksi.
   - Transaksi checkout konsumen dibuat dengan status `pending`.
   - Item transaksi tersimpan ke database.

5. **Approval transaksi oleh kasir**
   - Kasir dapat melihat transaksi masuk.
   - Kasir dapat melihat detail transaksi.
   - Kasir dapat melakukan approve transaksi.
   - Kasir dapat melakukan reject transaksi.

6. **Pembayaran transaksi**
   - Kasir dapat memproses pembayaran transaksi yang sudah berstatus `approved`.
   - Setelah pembayaran berhasil, status transaksi berubah menjadi `paid`.

7. **Metode pembayaran aktif**
   - Sistem mengarahkan pembayaran aktif ke `smartbank`.
   - Payment dibuat melalui SmartBank Connector resmi, bukan random dummy.
   - Backend menyimpan payment request ID, provider reference, response code/body, idempotency key, dan waktu bayar di tabel `payments`.
   - Cash, QRIS, dan transfer tidak menjadi jalur payment aktif pada kode saat ini.

8. **Direct sale kasir**
   - Kasir dapat membuat transaksi langsung dari dashboard kasir.
   - Kasir dapat memilih produk dan quantity.
   - Transaksi direct sale langsung dibuat dengan status `paid`.
   - Direct sale tidak harus melalui checkout konsumen.

9. **Pengurangan stok otomatis**
   - Stok produk dicek sebelum transaksi diproses.
   - Stok produk berkurang saat transaksi berhasil menjadi `paid`.
   - POS dan katalog konsumen memakai sumber stok yang sama dari tabel `products`.

10. **Struk atau receipt**
    - Kasir dapat melihat struk transaksi.
    - Konsumen dapat melihat struk transaksi.
    - Konsumen dapat mengunduh receipt dalam format PDF.

11. **Dashboard manager**
    - Manager dapat melihat KPI penjualan.
    - Manager dapat melihat grafik penjualan.
    - Manager dapat melihat transaksi terbaru.
    - Manager dapat melihat performa kasir.
    - Manager dapat export laporan CSV.
    - Manager dapat export laporan PDF.

12. **API Integrator dan SmartBank wallet**
   - Manager dapat melihat endpoint lokal.
   - Manager dapat mengelola endpoint eksternal.
   - Manager dapat mengetes endpoint eksternal.
   - Manager dapat mengaktifkan endpoint eksternal.
   - Manager dapat menautkan wallet toko SmartBank sebagai penerima pembayaran.

13. **Integrasi SmartBank Connector**
    - Konsumen dapat menautkan akun POS ke SmartBank Wallet melalui OTP.
    - Konsumen membayar transaksi yang sudah disetujui kasir memakai PIN SmartBank.
    - Sistem memakai idempotency key berbasis invoice untuk request ke Connector.
    - Payment sukses baru dapat membuat transaksi menjadi `paid`.
    - Kasir hanya dapat memproses metode `smartbank` pada flow payment saat ini.

14. **Payment, refund, dan event dasar**
    - Tabel `payments` sudah terpisah dari transaksi.
    - Tabel `refunds` sudah tersedia untuk reversal SmartBank.
    - Manager memiliki route refund payment: `POST /manager/payments/:id/refund`.
    - Tabel `outbox_events` sudah tersedia untuk event invoice paid dan integrasi insight.
    - Worker outbox dan reconciliation sudah ada pada service background.

15. **Keamanan dasar**
    - Middleware Helmet sudah digunakan.
    - Rate limit sudah digunakan.
    - Sanitasi input sudah digunakan.
    - Session timeout sudah digunakan.
    - Halaman error 403, 404, dan 500 sudah tersedia.
    - Endpoint `/health` sudah tersedia, tetapi masih health sederhana.

## Yang Belum Dikerjakan

1. **Inventory module formal**
   - Produk menyimpan `on_hand`, `reserved`, dan `available`; `stock` dipertahankan sebagai nilai kompatibilitas yang sama dengan `available`.
   - Checkout konsumen menahan stok selama 15 menit, sedangkan invoice direct sale menahan stok selama 10 menit.
   - Settlement mengonsumsi reservation; reject, void, atau expiry melepasnya otomatis.
   - Tabel `inventory_movements` mencatat reserve, release, consume, dan expiry.

2. **Pencegahan overselling tingkat inventory formal**
   - Atomic reservation menggunakan kondisi `on_hand - reserved >= qty` saat checkout/direct sale.
   - Settlement hanya dapat mengonsumsi reservation aktif transaksi terkait.
   - Belum ada pengujian untuk pembelian stok terakhir secara bersamaan.

3. **Audit log**
   - Void transaksi sudah dicatat atomik sebagai `transaction.voided`, termasuk pelaku, alasan, IP, user agent, serta data sebelum/sesudah.
   - Manager dapat melihat 100 catatan terbaru pada `/manager/audit-logs`.
   - Belum ada audit untuk approve transaksi.
   - Belum ada audit untuk reject transaksi.
   - Belum ada audit untuk pembayaran.
   - Belum ada audit untuk direct sale.
   - Belum ada audit untuk update stok.
   - Belum ada audit untuk hapus produk.
   - Belum ada audit untuk perubahan endpoint API.

4. **Void transaksi**
   - Manager dapat void transaksi `pending`, `approved`, atau `pending_payment` melalui dashboard dengan alasan wajib.
   - Transaksi `paid` ditolak dari jalur void dan harus melalui refund SmartBank.
   - Void hanya diizinkan saat `stock_deducted = 0`, sehingga tidak ada stok yang perlu direstore.
   - Belum ada role supervisor atau permission granular selain role manager.

5. **Refund lanjutan**
   - Refund SmartBank dasar sudah mulai tersedia melalui route manager dan tabel `refunds`.
   - Belum ada refund penuh yang menyelesaikan seluruh konsekuensi transaksi.
   - Belum ada refund parsial.
   - Belum ada reversal stok untuk refund.
   - Belum ada audit log refund formal.

6. **Idempotency payment lanjutan**
   - Tabel `payments` sudah menyimpan `idempotency_key` dan `request_fingerprint`.
   - Unique index untuk idempotency key sudah tersedia.
   - Masih perlu test bisnis untuk double click/retry agar proteksi ini terbukti end-to-end.
   - Masih perlu penyelarasan UI agar pengguna tidak memicu flow pembayaran ganda.

7. **Payment state machine lengkap**
   - Status transaksi sudah mencakup `pending`, `pending_payment`, `approved`, `paid`, `rejected`, `voided`, dan `refunded`.
   - Status payment sudah mencakup `processing`, `pending`, `success`, `failed`, dan `refunded`.
   - Belum ada validasi transisi status yang lengkap.

8. **SmartBank ledger**
   - SmartBank Connector sudah dipakai untuk payment dan reversal dasar.
   - Belum ada double-entry ledger.
   - Belum ada account balance.
   - Belum ada reconciliation order-payment-ledger.
   - Belum ada refund atau reversal melalui ledger internal POS.

9. **Tenant dan outlet**
   - Belum ada `tenant_id`.
   - Belum ada `outlet_id`.
   - Belum ada assignment kasir ke outlet.
   - Belum ada pemisahan produk per outlet.
   - Belum ada pemisahan transaksi per UMKM atau tenant.

10. **Permission detail**
    - Saat ini sistem hanya menggunakan role dasar.
    - Belum ada permission granular untuk void.
    - Belum ada permission granular untuk refund.
    - Belum ada permission granular untuk koreksi transaksi.
    - Belum ada permission supervisor.

11. **Riwayat pergerakan stok**
    - Belum ada catatan stok masuk.
    - Belum ada catatan stok keluar.
    - Belum ada catatan adjustment stok.
    - Belum ada catatan stok karena void.
    - Belum ada catatan stok karena refund.
    - Belum ada catatan stok karena restock.

12. **SupplierHub**
    - Belum ada supplier order.
    - Belum ada proses restock dari supplier.
    - Belum ada penerimaan barang.
    - Belum ada partial receipt.
    - Belum ada integrasi restock ke inventory atau stok POS.

13. **LogistiKita**
    - Belum ada shipment request.
    - Belum ada tracking pengiriman.
    - Belum ada tarif logistik.
    - Belum ada proof of delivery.

14. **Analytics read model**
    - Dashboard manager masih membaca langsung dari data transaksi utama.
    - Belum ada read model khusus analytics.
    - Belum ada informasi `last_updated_at` untuk data analytics.

15. **Gamification**
    - Belum ada point.
    - Belum ada badge.
    - Belum ada mission.
    - Belum ada reward.
    - Belum ada event target harian POS.
    - Belum ada reversal point jika transaksi dibatalkan atau direfund.

16. **Event dan outbox lanjutan**
    - Tabel transactional outbox sudah tersedia.
    - Worker outbox dasar sudah tersedia.
    - Belum ada retry event.
    - Belum ada replay event.
    - Belum ada dead-letter handling.
    - Belum ada consumer idempotent.

17. **Monitoring production**
    - Endpoint `/health` sederhana sudah ada.
    - Belum ada health endpoint lengkap yang mengecek dependency.
    - Belum ada readiness endpoint.
    - Belum ada liveness endpoint.
    - Belum ada request ID.
    - Belum ada structured log.
    - Belum ada monitoring operasional.

18. **Testing lengkap**
    - Belum ada test untuk double payment.
    - Belum ada test untuk stok habis.
    - Belum ada test untuk transaksi paralel.
    - Belum ada test untuk SmartBank gagal.
    - Belum ada test untuk void.
    - Belum ada test untuk refund.
    - Belum ada test untuk audit log.

## Urutan Pekerjaan yang Disarankan

1. Audit log untuk approve, reject, payment, refund, stok, produk, dan integrasi.
2. Test void serta pencatatan audit log.
3. Test double payment dan transaksi paralel.
4. Inventory module formal.
5. Refund penuh beserta konsekuensi stok dan audit.
6. Permission granular.
7. Tenant dan outlet.
8. SmartBank ledger.
9. Event dan outbox lanjutan.
10. Analytics read model.

## Catatan

Aplikasi saat ini sudah cukup untuk kebutuhan demo POS dasar karena fitur login, role, produk, checkout, kasir, pembayaran SmartBank Connector, pemotongan stok, struk, laporan manager, API Integrator, payment history, refund dasar, outbox dasar, dan health endpoint sudah tersedia.

Namun, aplikasi belum sepenuhnya memenuhi PRD ekosistem UMKM karena bagian seperti inventory formal, audit log untuk semua aksi penting, ledger, tenant, permission granular, event processing lengkap, SupplierHub, LogistiKita, analytics read model, dan gamification belum tersedia.
