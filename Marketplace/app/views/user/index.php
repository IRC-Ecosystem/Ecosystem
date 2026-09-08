<?php
/** @var array $data */
$user = $data['user'];
$orders = $data['orders'];
$cart = $data['cart'];
$completedOrders = array_filter($orders, fn ($order) => $order['order_status'] === 'completed');
$activeOrders = array_filter($orders, fn ($order) => in_array($order['order_status'], ['processing', 'shipped'], true));
$points = (count($orders) * 150) + count($cart['items']) * 25;
$levelProgress = min(100, ($points % 1000) / 10);
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .customer-surface {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .customer-card {
        transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
    }

    .customer-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px -18px rgba(0, 104, 95, .35);
    }
</style>

<div class="customer-surface space-y-8">
    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-[#eff4ff]">
        <div class="grid gap-6 p-6 lg:grid-cols-[1.4fr_0.6fr] lg:items-center">
            <div>
                <p class="text-sm font-extrabold uppercase tracking-wide text-[#00685f]">Dashboard Customer</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight text-[#0b1c30] md:text-4xl">
                    Halo, <?= htmlspecialchars($user['name']) ?>.
                </h1>
                  <p class="mt-3 max-w-2xl text-sm leading-6 text-[#3d4947] md:text-base">
                     Pantau status wallet SmartBank, misi pembeli, keranjang, pesanan aktif, dan produk UMKM unggulan dari satu halaman.
                </p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="<?= BASEURL ?>user/catalog" class="rounded-lg bg-[#00685f] px-5 py-3 text-sm font-bold text-white hover:bg-[#008378]">Jelajahi Katalog</a>
                    <a href="<?= BASEURL ?>user/cart" class="rounded-lg border border-[#00685f] bg-white px-5 py-3 text-sm font-bold text-[#00685f] hover:bg-[#dae2fd]">Lihat Keranjang</a>
                </div>
            </div>
              <div class="rounded-xl bg-[#00685f] p-5 text-white">
                 <p class="text-xs font-bold uppercase tracking-wide text-[#89f5e7]">SmartBank Wallet</p>
                 <p class="mt-2 text-2xl font-extrabold"><?= !empty($data['smartBank']['linked']) ? 'Terhubung' : 'Belum terhubung' ?></p>
                 <p class="mt-3 text-sm leading-6 text-white/80"><?= !empty($data['smartBank']['linked']) ? 'Gunakan PIN SmartBank saat membayar pesanan.' : 'Hubungkan wallet dari Profil sebelum melakukan pembayaran.' ?></p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="customer-card rounded-xl border border-[#bcc9c6] bg-white p-5">
            <p class="text-sm font-semibold text-[#3d4947]">Total Pesanan</p>
            <p class="mt-2 text-3xl font-extrabold text-[#0b1c30]"><?= count($orders) ?></p>
            <p class="mt-1 text-xs text-[#3d4947]"><?= count($completedOrders) ?> selesai</p>
        </article>
        <article class="customer-card rounded-xl border border-[#bcc9c6] bg-white p-5">
            <p class="text-sm font-semibold text-[#3d4947]">Pesanan Aktif</p>
            <p class="mt-2 text-3xl font-extrabold text-[#00685f]"><?= count($activeOrders) ?></p>
            <p class="mt-1 text-xs text-[#3d4947]">Processing atau shipped</p>
        </article>
        <article class="customer-card rounded-xl border border-[#bcc9c6] bg-white p-5">
            <p class="text-sm font-semibold text-[#3d4947]">Item Keranjang</p>
            <p class="mt-2 text-3xl font-extrabold text-[#825100]"><?= count($cart['items']) ?></p>
            <p class="mt-1 text-xs text-[#3d4947]">Total Rp<?= number_format($cart['total'], 0, ',', '.') ?></p>
        </article>
        <article class="customer-card rounded-xl border border-[#bcc9c6] bg-[#0b1c30] p-5 text-white">
            <p class="text-sm font-semibold text-white/70">Poin Pembeli</p>
            <p class="mt-2 text-3xl font-extrabold text-[#89f5e7]"><?= number_format($points, 0, ',', '.') ?></p>
            <p class="mt-1 text-xs text-white/70">Simulasi loyalty dari aktivitas belanja</p>
        </article>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="customer-card rounded-xl border border-[#bcc9c6] bg-white p-6">
            <div class="flex flex-col gap-5 md:flex-row md:items-center">
                <div class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-[#008378] text-2xl font-extrabold text-white">L2</div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-[#825100]">Status Saat Ini</p>
                            <h2 class="mt-1 text-2xl font-extrabold text-[#0b1c30]">Level 2: Pembeli Setia</h2>
                        </div>
                        <span class="rounded-lg bg-[#ffddb8] px-3 py-2 text-xs font-extrabold text-[#653e00]"><?= (int) $levelProgress ?>% menuju VIP</span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-[#3d4947]">Dapatkan poin dari checkout, ulasan, dan eksplorasi katalog untuk membuka voucher tambahan.</p>
                    <div class="mt-4">
                        <div class="mb-2 flex justify-between text-xs font-bold text-[#3d4947]">
                            <span>Progress Level</span>
                            <span><?= number_format($points % 1000, 0, ',', '.') ?> / 1000 XP</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-[#e5eeff]">
                            <div class="h-3 rounded-full bg-[#00685f]" style="width: <?= (int) $levelProgress ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="customer-card rounded-xl border border-[#bcc9c6] bg-[#00685f] p-6 text-white">
            <p class="text-xs font-bold uppercase tracking-wide text-[#89f5e7]">Voucher Pembeli</p>
            <div class="mt-4 space-y-3">
                <?php foreach ($data['vouchers'] as $voucher): ?>
                    <div class="rounded-lg bg-white/10 p-3">
                        <b class="text-[#89f5e7]"><?= htmlspecialchars($voucher['code']) ?></b>
                        <p class="mt-1 text-sm text-white/80"><?= htmlspecialchars($voucher['label']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </section>

    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-[#0b1c30]">Misi Harian Pembeli</h2>
                <p class="mt-1 text-sm text-[#3d4947]">Elemen gamifikasi untuk membuat flow customer lebih hidup saat demo.</p>
            </div>
            <span class="rounded-lg bg-[#d3e4fe] px-3 py-2 text-xs font-bold text-[#3d4947]">Reset harian</span>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <?php
            $missions = [
                ['title' => 'Cari 3 Produk Unik', 'desc' => 'Jelajahi katalog UMKM hari ini.', 'reward' => '+20 XP', 'progress' => min(3, count($data['products'])) . '/3'],
                ['title' => 'Checkout Keranjang', 'desc' => 'Selesaikan transaksi via SmartBank.', 'reward' => '+100 XP', 'progress' => count($orders) ? 'Selesai' : 'Belum'],
                ['title' => 'Bagikan Produk', 'desc' => 'Bantu seller menjangkau pembeli baru.', 'reward' => '+30 XP', 'progress' => '0/1'],
            ];
            ?>
            <?php foreach ($missions as $mission): ?>
                <article class="customer-card rounded-xl border border-[#bcc9c6] bg-white p-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#89f5e7] text-sm font-extrabold text-[#00201d]"><?= htmlspecialchars(substr($mission['title'], 0, 1)) ?></div>
                        <div>
                            <h3 class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($mission['title']) ?></h3>
                            <p class="mt-1 text-sm text-[#3d4947]"><?= htmlspecialchars($mission['desc']) ?></p>
                        </div>
                    </div>
                    <div class="mt-5 flex items-center justify-between text-xs font-bold">
                        <span class="text-[#00685f]"><?= htmlspecialchars($mission['reward']) ?></span>
                        <span class="text-[#3d4947]"><?= htmlspecialchars($mission['progress']) ?></span>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-5">
            <h3 class="text-lg font-extrabold text-[#0b1c30]">SmartBank Protection</h3>
             <p class="mt-2 text-sm leading-6 text-[#3d4947]">Checkout membuat order pending. Pembayaran diselesaikan melalui Connector menggunakan PIN SmartBank pembeli.</p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-5">
            <h3 class="text-lg font-extrabold text-[#0b1c30]">LogistiKita Tracking</h3>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Status order aktif ditampilkan dari database. Integrasi pengiriman bisa ditambahkan di tahap berikutnya.</p>
        </article>
    </section>

    <section>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-2xl font-extrabold text-[#0b1c30]">Produk Unggulan</h2>
                <p class="mt-1 text-sm text-[#3d4947]">Pilihan produk aktif dari seller UMKM.</p>
            </div>
            <a href="<?= BASEURL ?>user/catalog" class="rounded-lg border border-[#00685f] px-4 py-2 text-sm font-bold text-[#00685f] hover:bg-[#eff4ff]">Lihat Semua</a>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($data['featured'] as $product): ?>
                <article class="customer-card overflow-hidden rounded-xl border border-[#bcc9c6] bg-white">
                    <div class="aspect-[4/3] bg-[#eff4ff]">
                        <?php if (!empty($product['image_url'])): ?>
                            <img src="<?= htmlspecialchars($product['image_url']) ?>" class="h-full w-full object-cover" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php endif; ?>
                    </div>
                    <div class="p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-[#3d4947]"><?= htmlspecialchars($product['store_name']) ?></p>
                        <h3 class="mt-2 min-h-[48px] font-extrabold leading-6 text-[#0b1c30]"><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p class="font-extrabold text-[#00685f]">Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
                            <span class="rounded-md bg-[#e5eeff] px-2 py-1 text-xs font-bold text-[#3d4947]">Stok <?= (int) $product['stock'] ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
