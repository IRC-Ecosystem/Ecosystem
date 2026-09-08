# TI41254 Software Engineering 2 — Soal ATS 2026

## Identitas Asesmen

- **Mata Kuliah:** TI41254 Software Engineering 2
- **Semester:** Genap T.A. 2025–2026
- **Asesmen:** 1
- **Jenis Asesmen:** Reguler
- **Program Studi:** D4 Teknik Informatika – Sekolah Vokasi
- **Lama Pengerjaan:** 120 menit

---

## Petunjuk Pengerjaan

- Jawablah pertanyaan-pertanyaan soal berikut dengan baik dan benar.

---

# Soal

## 1.

Tuliskan nama aplikasi produk TUBES kelompok dan buatkan deskripsi aplikasi anda!

---

## 2. [Bobot 50]

Anda adalah developer utama dari aplikasi yang kelompok Anda kerjakan pada TUBES ekosistem UMKM digital. Aplikasi Anda tidak bekerja secara independen, tetapi harus berinteraksi dengan aplikasi lain melalui API Gateway dan SmartBank.

Jelaskan bagaimana aplikasi Anda melakukan proses transaksi end-to-end ketika terjadi aktivitas ekonomi utama pada sistem.

Analisis minimal mencakup:

1. Input utama yang diterima aplikasi Anda.
2. API apa saja yang perlu dipanggil ke sistem lain.
3. Data apa yang dikirim dan diterima.
4. Mekanisme validasi JWT/token.
5. Risiko inkonsistensi data yang mungkin terjadi.
6. Dampak jika salah satu aplikasi lain mengalami kegagalan.
7. Strategi agar sistem tetap robust dan tidak menyebabkan kerusakan transaksi berantai pada ekosistem UMKM.

### Contoh Interaksi

1. Marketplace → SupplierHub → LogistiKita → SmartBank
2. POS → SmartBank → UMKM Insight
3. SupplierHub → Marketplace → Logistik
4. dll. sesuai aplikasi masing-masing.

---

## 3. [Bobot 50]

Pada suatu hari terjadi lonjakan transaksi besar pada ekosistem UMKM.

Namun muncul kondisi berikut:

1. SmartBank mengalami delay validasi pembayaran,
2. Marketplace tetap menerima checkout,
3. SupplierHub memiliki stok terbatas,
4. dan LogistiKita mengalami keterlambatan sinkronisasi ongkir.

Sebagai software engineer dari aplikasi yang Anda kerjakan:

Analisis bagaimana aplikasi Anda seharusnya merespons kondisi tersebut agar:

1. Transaksi ekonomi tetap konsisten,
2. Tidak terjadi double transaction,
3. Tidak terjadi pengurangan stok palsu,
4. Sistem tetap scalable,
5. User tetap mendapatkan feedback yang jelas,
6. Ekosistem tidak mengalami “cascade failure”.

Kemudian jelaskan:

1. Komponen apa yang paling kritis dalam aplikasi Anda,
2. Endpoint/API mana yang harus diprioritaskan,
3. Log apa saja yang wajib dicatat,
4. dan bagaimana prinsip Clean Architecture atau SOLID dapat membantu menyelesaikan masalah tersebut.
