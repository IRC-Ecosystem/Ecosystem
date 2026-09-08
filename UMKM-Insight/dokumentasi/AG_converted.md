3. ALGORITMA GREEDY
Algoritma Greedy merupakan pendekatan untuk menyelesaikan masalah optimasi dengan cara
memilih keputusan terbaik pada setiap langkah secara lokal. Istilah greedy (rakus) mereﬂeksikan
strategi  pengambilan  keputusan  yang  selalu  memprioritaskan  pilihan  paling  menguntungkan
saat itu, tanpa mempertimbangkan konsekuensi jangka panjang.

Pendekatan ini banyak digunakan pada permasalahan optimasi karena sederhana dan memiliki
waktu eksekusi yang relatif cepat.

3.1 Konsep dan Karakteristik Algoritma Greedy
Algoritma Greedy didasarkan pada prinsip bahwa solusi optimal global dapat dicapai melalui
serangkaian keputusan optimal lokal.

Karakteristik utama algoritma ini meliputi:

  Prinsip Greedy Choice Property

Keputusan terbaik pada setiap langkah (optimal lokal) diharapkan mengarah pada solusi
terbaik secara keseluruhan (optimal global).

  Cara Kerja

Pada setiap iterasi, algoritma memilih elemen yang paling menguntungkan dari himpunan
kandidat yang tersedia. Setelah keputusan diambil, pilihan tersebut tidak dapat dibatalkan (no
backtracking).

  Kelebihan

Struktur algoritma sederhana dan memiliki performa yang cepat.

  Kekurangan

Tidak selalu menghasilkan solusi optimal global, terutama pada kasus di mana keputusan
lokal tidak mencerminkan solusi terbaik secara keseluruhan.

3.2 Implementasi Algoritma Greedy

Pada praktikum ini, algoritma greedy diimplementasikan untuk menyelesaikan masalah penukaran
uang dengan memilih kombinasi koin yang memenuhi nilai target.

Algoritma bekerja dengan langkah sebagai berikut:

1.  Mengurutkan koin dari nilai terbesar ke terkecil

2.  Memilih koin terbesar yang masih memenuhi sisa nilai target

3.  Mengulangi proses hingga target tercapai atau tidak ada solusi

Python

def coin_exchange(c, a):

    # c = himpunan_koin (list), a = target nilai (integer)

    # Langkah 1: Urutkan koin dari nilai terbesar (pendekatan greedy)

    c_sorted = sorted(c, reverse=True)

    s = [] # Himpunan solusi

    total_s = 0 # Menyimpan nilai total semua koin di dalam S

    # Langkah 2: Evaluasi setiap koin terbesar yang tersisa (x)

    for x in c_sorted:

        # Jika target sudah tercapai, hentikan proses (pengganti while)

        if total_s == a:

            break

        # Jika nilai semua koin di S ditambah nilai koin x <= A

        if total_s + x <= a:

            s.append(x) # S <- S U {x}

            total_s += x

    # Langkah 3: Validasi akhir apakah total koin di S sama dengan A

    if total_s == a:

        return s

    else:

        return "tidak ada solusi"

# === Pengujian Kasus (Praktikum & Post-Test 1) ===

# Kasus: Menukar uang senilai 1700 dari himpunan koin yang tersedia

himpunan_koin = [1000, 500, 500, 200, 200, 100, 100]

target_penukaran = 1700

print("=== Hasil Pengujian Algoritma Greedy ===")

print(f"Himpunan koin tersedia (C): {himpunan_koin}")

print(f"Target penukaran uang (A) : {target_penukaran}")

hasil_solusi = coin_exchange(himpunan_koin, target_penukaran)

print(f"Solusi koin yang dipilih (S): {hasil_solusi}")

3.3 Analisis Output
Berdasarkan hasil eksekusi program, algoritma bekerja secara iteratif dengan memilih koin terbesar
yang memungkinkan.

Sebagai ilustrasi:

  Program memilih koin 1000 → sisa target menjadi 700

  Kemudian memilih koin 500 → sisa target menjadi 200

  Koin 500 berikutnya tidak dipilih karena melebihi target

  Program memilih koin 200 → target tercapai

Solusi yang diperoleh adalah kombinasi:

[1000, 500, 200]

Proses ini menunjukkan bahwa algoritma selalu mengambil keputusan lokal terbaik pada setiap
langkah.

3.4 Analisis Kompleksitas Waktu

Kompleksitas algoritma greedy pada kasus ini terdiri dari dua bagian utama:

  Proses pengurutan (sorting)

Mengurutkan koin menggunakan algoritma bawaan (misalnya Timsort pada Python)
membutuhkan waktu:

𝑂(𝑛log  𝑛)

  Proses iterasi pemilihan koin

Algoritma melakukan iterasi linear terhadap daftar koin:

𝑂(𝑛)

Karena 𝑂(𝑛log  𝑛)mendominasi 𝑂(𝑛), maka kompleksitas total algoritma adalah:

𝑂(𝑛log  𝑛)

3.6 Studi Kasus: Vending Machine
Salah satu penerapan nyata algoritma greedy dapat ditemukan pada sistem vending machine (mesin jual
otomatis), khususnya dalam proses pemberian kembalian.

Sebagai contoh, jika seseorang melakukan pembayaran sebesar Rp 10.000 untuk pembelian senilai Rp
4.000, maka  sistem  harus  memberikan  kembalian  sebesar Rp  6.000. Algoritma greedy  akan  bekerja
dengan memilih pecahan uang terbesar yang memungkinkan pada setiap langkah:

  Memilih Rp 5.000 → sisa Rp 1.000

  Tidak memilih Rp 2.000 karena melebihi sisa

  Memilih Rp 1.000 → sisa Rp 0

Pendekatan ini bertujuan untuk meminimalkan jumlah koin atau lembar uang yang digunakan.

Implementasi serupa banyak ditemukan pada sistem Point of Sale (POS) atau aplikasi kasir sederhana,
di mana fungsi seperti calculate_change() menggunakan strategi greedy untuk menentukan kombinasi
kembalian secara efisien.

4. ALGORITMA DIVIDE & CONQUER
Divide  and  Conquer  (bagi  dan  taklukkan)  merupakan  strategi  algoritma  untuk  menyelesaikan
permasalahan dengan cara membaginya menjadi sub-masalah yang lebih kecil, menyelesaikan masing-
masing sub-masalah secara independen, kemudian menggabungkan hasilnya menjadi solusi akhir.

Pendekatan ini efektif untuk pengolahan data berskala besar karena mampu mengurangi kompleksitas
melalui dekomposisi masalah.

4.1 Konsep dan Mekanisme Divide and Conquer
Strategi ini terdiri dari tiga tahap utama:

1.  Divide (Membagi)

Memecah masalah utama menjadi beberapa sub-masalah yang lebih kecil.

2.  Conquer (Menyelesaikan)

Menyelesaikan sub-masalah tersebut, umumnya menggunakan pendekatan rekursif.

3.  Combine (Menggabungkan)

Menggabungkan solusi dari sub-masalah untuk membentuk solusi akhir.

Pendekatan  ini  banyak  digunakan  dalam  algoritma  pengurutan  dan  pencarian  karena  efisien  dalam
menangani data berukuran besar.

4.2 Implementasi Quick Sort
Quick Sort merupakan algoritma yang menggunakan teknik divide and conquer dengan memilih
sebuah elemen sebagai pivot, kemudian membagi list menjadi dua bagian:

  Elemen yang lebih kecil dari pivot

  Elemen yang lebih besar dari pivot

Python

def partition(A, i, j):

    """

    Fungsi untuk mempartisi list dengan memosisikan pivot.

    Sesuai teori pembagian pada indeks tengah.

    """

    # Memilih pivot dari elemen tengah

