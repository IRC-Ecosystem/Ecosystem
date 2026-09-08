# SmartBank Marketplace Setup

## 1. Database

Import `database/pasarkita.sql` pada MySQL. Tabel `smartbank_payments` dibuat otomatis saat modul SmartBank pertama digunakan.

## 2. Connector service

Daftarkan Marketplace sebagai service Connector menggunakan Admin API. Simpan API key yang dikembalikan di `Marketplace/.env`:

```env
SMARTBANK_CONNECTOR_URL=http://localhost:5000
SMARTBANK_CONNECTOR_API_KEY=sbk_...
SMARTBANK_MARKETPLACE_EXTERNAL_ID=marketplace-merchant-main
SMARTBANK_CONNECTOR_TIMEOUT_MS=10000
```

Jangan gunakan API key POS. Marketplace memerlukan service Connector sendiri agar linkage dan audit terisolasi.

## 3. Wallet penerima

Login sebagai admin Marketplace, lalu buka:

```text
/admin/smartbank
```

Kirim OTP ke nomor akun treasury Marketplace, verifikasi OTP, lalu tetapkan wallet penerima. Dana settlement masuk ke wallet treasury ini.

## 4. Wallet pembeli

Login sebagai pembeli, buka `/user/profile`, lalu hubungkan wallet dengan OTP. Nomor telepon Marketplace harus sama dengan nomor pada akun SmartBank pembeli.

## 5. Pembayaran

Checkout membuat order berstatus `pending`. Dari `/user/orders`, pembeli memasukkan PIN SmartBank untuk menyelesaikan pembayaran. PIN tidak disimpan Marketplace.

## Batasan

Settlement saat ini mengirim seluruh nilai order ke wallet treasury Marketplace. Payout ke seller multi-toko belum diimplementasikan.
