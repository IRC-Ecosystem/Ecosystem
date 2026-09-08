<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .landing-font {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
</style>

<div class="landing-font bg-[#f8f9ff] text-slate-950">
    <section class="border-b border-[#bcc9c6] bg-[#eff4ff]">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 md:grid-cols-[1.05fr_0.95fr] md:items-center lg:px-8 lg:py-16">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-wide text-[#00685f]">Marketplace UMKM Terintegrasi</p>
                <h1 class="mt-4 max-w-3xl text-4xl font-extrabold leading-tight text-[#0b1c30] md:text-6xl">
                    Memberdayakan <span class="text-[#00685f]">UMKM Indonesia</span> dalam satu ekosistem digital.
                </h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-[#3d4947] md:text-lg">
                    PasarKita menghubungkan pembeli, seller, pembayaran SmartBank, pengiriman LogistiKita, restock SupplierHub, dan analitik UMKM Insight agar alur bisnis lokal terlihat jelas dari katalog sampai ledger transaksi.
                </p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="<?= BASEURL ?>auth/register" class="inline-flex items-center justify-center rounded-lg bg-[#00685f] px-6 py-3 font-bold text-white shadow-sm transition hover:bg-[#008378]">
                        Mulai Belanja
                    </a>
                    <a href="<?= BASEURL ?>auth/login" class="inline-flex items-center justify-center rounded-lg border border-[#00685f] bg-white px-6 py-3 font-bold text-[#00685f] transition hover:bg-[#dae2fd]">
                        Masuk Dashboard
                    </a>
                </div>
                <div class="mt-8 grid max-w-xl grid-cols-3 gap-3 text-center">
                    <div class="rounded-lg border border-[#bcc9c6] bg-white p-3">
                        <b class="block text-xl text-[#00685f]">2%</b>
                        <span class="text-xs font-semibold text-[#3d4947]">Fee Marketplace</span>
                    </div>
                    <div class="rounded-lg border border-[#bcc9c6] bg-white p-3">
                        <b class="block text-xl text-[#825100]">3 Role</b>
                        <span class="text-xs font-semibold text-[#3d4947]">Admin, Seller, User</span>
                    </div>
                    <div class="rounded-lg border border-[#bcc9c6] bg-white p-3">
                        <b class="block text-xl text-[#00685f]">Live</b>
                        <span class="text-xs font-semibold text-[#3d4947]">Grafik Database</span>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-xl">
                <div class="aspect-[4/3]">
                    <img
                        src="https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=85"
                        alt="Pelaku UMKM berdiskusi mengelola bisnis digital"
                        class="h-full w-full object-cover"
                    >
                </div>
                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <div class="rounded-lg bg-[#eff4ff] p-3">
                        <p class="text-xs font-bold uppercase text-[#3d4947]">Checkout</p>
                        <p class="mt-1 text-sm font-semibold text-[#0b1c30]">Cart ke payment request</p>
                    </div>
                    <div class="rounded-lg bg-[#89f5e7]/30 p-3">
                        <p class="text-xs font-bold uppercase text-[#3d4947]">Seller</p>
                        <p class="mt-1 text-sm font-semibold text-[#0b1c30]">Stok, order, performa</p>
                    </div>
                    <div class="rounded-lg bg-[#ffddb8]/60 p-3">
                        <p class="text-xs font-bold uppercase text-[#3d4947]">Admin</p>
                        <p class="mt-1 text-sm font-semibold text-[#0b1c30]">Monitoring platform</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white px-4 py-14 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-3xl font-extrabold text-[#0b1c30] md:text-4xl">Ekosistem Terintegrasi PasarKita</h2>
                <p class="mt-4 text-base leading-7 text-[#3d4947]">
                    Setiap modul dibuat saling terhubung agar demo aplikasi tidak hanya menampilkan halaman, tetapi juga alur bisnis yang utuh.
                </p>
            </div>

            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                <?php
                $ecosystems = [
                    ['name' => 'PasarKita', 'desc' => 'Jual beli digital', 'icon' => 'PK', 'class' => 'bg-[#00685f] text-white'],
                    ['name' => 'SmartBank', 'desc' => 'Pembayaran aman', 'icon' => 'SB', 'class' => 'bg-[#565e74] text-white'],
                    ['name' => 'LogistiKita', 'desc' => 'Pengiriman cepat', 'icon' => 'LK', 'class' => 'bg-[#825100] text-white'],
                    ['name' => 'SupplierHub', 'desc' => 'Restock produk', 'icon' => 'SH', 'class' => 'bg-[#3d4947] text-white'],
                    ['name' => 'UMKM Insight', 'desc' => 'Analitik bisnis', 'icon' => 'UI', 'class' => 'bg-[#ba1a1a] text-white'],
                    ['name' => 'Gamifikasi', 'desc' => 'Poin dan badge', 'icon' => 'GM', 'class' => 'bg-[#ffddb8] text-[#2a1700]'],
                ];
                ?>
                <?php foreach ($ecosystems as $item): ?>
                    <article class="rounded-xl border border-[#bcc9c6] bg-[#f8f9ff] p-4 text-center transition hover:-translate-y-1 hover:shadow-md">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full <?= $item['class'] ?> text-sm font-extrabold">
                            <?= htmlspecialchars($item['icon']) ?>
                        </div>
                        <h3 class="mt-4 font-extrabold text-[#0b1c30]"><?= htmlspecialchars($item['name']) ?></h3>
                        <p class="mt-1 text-sm text-[#3d4947]"><?= htmlspecialchars($item['desc']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="bg-[#f8f9ff] px-4 py-14 lg:px-8">
        <div class="mx-auto grid max-w-7xl gap-5 md:grid-cols-3">
            <article class="md:col-span-2 flex min-h-[300px] flex-col justify-end overflow-hidden rounded-xl bg-[#00685f] p-8 text-white">
                <p class="text-sm font-bold uppercase tracking-wide text-[#89f5e7]">Visi Kami</p>
                <h2 class="mt-3 text-3xl font-extrabold md:text-4xl">Menjadi katalis transformasi digital UMKM Indonesia.</h2>
                <p class="mt-4 max-w-2xl leading-7 text-white/90">
                    Aplikasi ini dirancang untuk menunjukkan bagaimana pembeli, seller, admin, pembayaran, stok, dan pelaporan dapat berjalan dalam satu rantai kerja yang mudah dipahami.
                </p>
            </article>

            <article class="rounded-xl border border-[#bcc9c6] bg-[#dce9ff] p-8 text-center">
                <p class="text-5xl font-extrabold text-[#00685f]"><?= count($data['products'] ?? []) ?>+</p>
                <h3 class="mt-3 text-xl font-extrabold text-[#0b1c30]">Produk Aktif</h3>
                <p class="mt-2 text-sm leading-6 text-[#3d4947]">Katalog diambil langsung dari database produk seller.</p>
            </article>

            <article class="rounded-xl border border-[#bcc9c6] bg-white p-8">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#89f5e7] font-extrabold text-[#00201d]">01</div>
                <h3 class="mt-5 text-xl font-extrabold text-[#0b1c30]">Efisiensi Operasional</h3>
                <p class="mt-2 text-sm leading-6 text-[#3d4947]">Seller dapat mengelola produk, pesanan, stok, restock, dan keuangan dari sidebar khusus.</p>
            </article>

            <article class="md:col-span-2 rounded-xl bg-[#0b1c30] p-8 text-white">
                <div class="grid gap-6 md:grid-cols-[0.8fr_1.2fr] md:items-center">
                    <div class="rounded-lg border border-[#00685f]/50 bg-[#00685f]/20 p-6 text-center">
                        <p class="text-5xl font-extrabold text-[#89f5e7]">API</p>
                        <p class="mt-2 text-sm text-white/70">Swagger tersedia untuk testing endpoint.</p>
                    </div>
                    <div>
                        <h3 class="text-2xl font-extrabold text-[#89f5e7]">Data Tidak Mentah</h3>
                        <p class="mt-3 leading-7 text-white/80">
                            Dashboard admin dan seller memakai endpoint grafik yang membaca database, menampilkan loading state, dan refresh otomatis untuk mengecek perubahan data.
                        </p>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section class="bg-white px-4 py-14 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-sm font-extrabold uppercase tracking-wide text-[#00685f]">Katalog UMKM</p>
                    <h2 class="mt-2 text-3xl font-extrabold text-[#0b1c30]">Produk Terbaru dari Seller</h2>
                    <p class="mt-2 text-[#3d4947]">Data produk ini diambil dari tabel produk yang aktif di database.</p>
                </div>
                <a href="<?= BASEURL ?>auth/login" class="inline-flex items-center justify-center rounded-lg border border-[#00685f] px-4 py-2 text-sm font-bold text-[#00685f] hover:bg-[#eff4ff]">
                    Masuk untuk Checkout
                </a>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach (array_slice($data['products'] ?? [], 0, 8) as $product): ?>
                    <article class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-[#f8f9ff] shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                        <div class="aspect-[4/3] bg-[#eff4ff]">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?= htmlspecialchars($product['image_url']) ?>" class="h-full w-full object-cover" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#3d4947]"><?= htmlspecialchars($product['store_name'] ?? 'Seller UMKM') ?></p>
                            <h3 class="mt-2 min-h-[48px] font-extrabold leading-6 text-[#0b1c30]"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="mt-4 flex items-center justify-between gap-3">
                                <p class="font-extrabold text-[#00685f]">Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
                                <span class="rounded-md bg-white px-2 py-1 text-xs font-bold text-[#3d4947]">Stok <?= (int) $product['stock'] ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="bg-[#f8f9ff] px-4 py-14 lg:px-8">
        <div class="mx-auto max-w-7xl rounded-xl border border-[#bcc9c6] bg-white p-8 text-center md:p-12">
            <h2 class="text-3xl font-extrabold text-[#0b1c30] md:text-5xl">Siap masuk ke ekosistem PasarKita?</h2>
            <p class="mx-auto mt-4 max-w-2xl leading-7 text-[#3d4947]">
                Buat akun pembeli, coba checkout, buka toko, lalu lihat bagaimana admin dan seller membaca perkembangan data melalui dashboard.
            </p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="<?= BASEURL ?>auth/register" class="inline-flex items-center justify-center rounded-lg bg-[#00685f] px-7 py-3 font-extrabold text-white hover:bg-[#008378]">Daftar Sekarang</a>
                <a href="<?= BASEURL ?>about" class="inline-flex items-center justify-center rounded-lg bg-[#dae2fd] px-7 py-3 font-extrabold text-[#5c647a] hover:bg-[#bec6e0]">Pelajari Aplikasi</a>
            </div>
        </div>
    </section>
</div>

<script>
    document.querySelectorAll('.landing-font section').forEach(function (section, index) {
        if (index === 0) {
            return;
        }

        section.classList.add('opacity-0', 'translate-y-6', 'transition-all', 'duration-700');
    });

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.remove('opacity-0', 'translate-y-6');
                    observer.unobserve(entry.target);
                }
            });
        }, {threshold: 0.12});

        document.querySelectorAll('.landing-font section').forEach(function (section, index) {
            if (index > 0) {
                observer.observe(section);
            }
        });
    } else {
        document.querySelectorAll('.landing-font section').forEach(function (section) {
            section.classList.remove('opacity-0', 'translate-y-6');
        });
    }
</script>
