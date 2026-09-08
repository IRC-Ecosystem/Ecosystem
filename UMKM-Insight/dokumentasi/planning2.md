
### 1. Tabel Bayangan (Karena Belum ada API SmartBank, maka buat data Simulasi), Distribute Database
### 2. Endpoint dari SmartBank POS, Marketplace
### 3. Notifikasi Tagihan + Fitur Pembelian pertama dan Pembayaran Bulanan. 2 Bukti berupa screenshot bukti pembayaran dan Transaksi dari Smartbank.
### 4. Sinkronsasi data.

Empat poin diatas merupakan hal-hal yang saya tangkap ketika dosen memintakan perkembangan selanjutnya dari Proyek UMKM Insight.Berikut adalah poin-poin tersebut dan implementasinya.

#### 1. Tabel Bayangan
Karena belum ada API yang diperlukan oleh Proyek UMKM Insight, maka dibuat data simulasi, ini termasuk SmartBank sebagai semua yang berhungan dengan transaksi keuangannya maupun status keuangan itu sendiri. Untuk performa produk ataupun semua yang berhubungan dengan data penjualan juga dibuat simulasi dari POS dan juga Marketplace.

Jadi Apa beda nya POS dengan Marketplace? POS itu dimiliki oleh 1 UMKM saja sedangkan MarketPlace itu ibaratkan sebagai Tokopedia ataupun Shoppee yang dimana banyak produk yang dipasarkan dibanding dengan UMKM POS. Tergantung dari Lokasi dimana UMKM tersebut menggunakan POS, mungkin satu produk lebih laris di lokasi tersebut di bandingkan dengan lokasi lain

Bayangan saya agak kabur di bagian ini, saya lupa sebenarnya apa yang beliau minta.

#### 2. Endpoint dari SmartBank, POS dan MarketPlace
Apakah ini maksudnya adalah membuat Endpoint/API sementara yang merupakan tabel database simulasi sebelum dimasukkan API yang sesungguhnya? Saya berasumsi demikian.

#### 3. Notifikasi Tagihan + Fitur Pembelian pertama dan Pembayaran Bulanan. 2 Bukti berupa screenshot bukti pembayaran dan Transaksi dari Smartbank.
Ini berkaitan dengan Minimnya simulasi berlangganan di proyek ini. memang ada fiturnya cuma belum ada simulasi berlangganannya, dari transaksi pertama kali membeli hingga pembayaran tagihan bulanan belum ada. jadi tentunya disimulasi ini akan ada prosesnya, dan sebagai operator ia bisa memasktikan 2 hal sebagai bukti kalau user sudah membayar, pertama screenshot bukti pembayaran yang secara manual dikirim oleh user dan juga histori transaksi Smartback (karena kasusnya User menggunakan Smartbank sebagai alat pembayaran, karena UMKM Insight adalah salah satu aplikasi dibawah payung yang sama dengan SmartBank)

#### 4. Sinkronsasi data
Untuk hal ini mungkin saya kurang tangkap, tapi kemungkinan sinkornisasi ini cukup berfokus pada UMKM Insight yang berhubungan dengan database, apakah benar benar ada sistem sinkronisasi data atau tidak yang siap untuk di hubungan dengan API aplikasi lain tanpa terjadi nya ketabrakana data.
