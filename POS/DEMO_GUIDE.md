# Panduan Demo WarungPOS

## 1. Persiapan

Pastikan MySQL/XAMPP sudah menyala, lalu jalankan:

```bash
npm install
npm run setup:db
npm start
```

Aplikasi berjalan di:

```txt
http://localhost:3002
```

## 2. Akun Demo

Password semua akun:

```txt
admin123
```

| Role | Email |
| --- | --- |
| Manager | `manager@warungpos.test` |
| Operator | `operator@warungpos.test` |
| Kasir | `kasir@warungpos.test` |
| Konsumen | `konsumen@warungpos.test` |

## 3. Alur Demo Utama

1. Login sebagai Manager.
2. Buka dashboard untuk melihat KPI, grafik, performa kasir, dan export laporan.
3. Buka `API Integrator`.
4. Tunjukkan halaman `Endpoint POS` untuk dokumentasi API lokal otomatis.
5. Buka `SmartBank`.
6. Tunjukkan health check Connector pada provider SmartBank.
7. Login sebagai Konsumen, buka Profil, isi nomor HP yang sama dengan SmartBank, lalu minta OTP.
8. Buka Inbox SmartBank, salin OTP, verifikasi, dan konfirmasi linkage di POS.
9. Buat checkout, lalu login sebagai Kasir dan approve transaksi.
10. Kembali ke halaman status Konsumen lalu masukkan PIN untuk membayar dengan SmartBank.

## 4. Konfigurasi SmartBank

Isi `.env` POS setelah service POS didaftarkan pada Connector:

```env
SMARTBANK_CONNECTOR_URL=http://localhost:5000
SMARTBANK_CONNECTOR_API_KEY=sbk_api_key_service_pos
SMARTBANK_POS_SELLER_EXTERNAL_ID=pos-user-id_merchant_yang_sudah_link
```

API Integrator hanya menyimpan health check:

```txt
GET http://localhost:5000/health
```

Pembayaran dipanggil oleh backend POS ke `/v1/connect/payment-requests` menggunakan API key service, external user ID hasil linkage, idempotency key invoice, dan PIN enam digit.

## 5. Catatan Demo

- Jika Connector belum berjalan di port `5000`, health check dan pembayaran akan gagal dengan pesan layanan tidak tersedia.
- Jika konsumen atau seller belum link, Connector menolak pembayaran dengan `USER_NOT_LINKED`.
- API key Connector tidak pernah dikirim ke browser; hanya backend POS yang menyimpannya.
- Jalur pembayaran demo yang aktif adalah SmartBank Connector. Cash/QRIS/transfer tidak dipakai sebagai payment lokal di backend saat ini.
- Endpoint lokal POS tetap bisa dilihat tanpa SmartBank karena dibaca otomatis dari route aplikasi.
