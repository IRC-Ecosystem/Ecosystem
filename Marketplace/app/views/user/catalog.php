<?php
/** @var array $data */
$currentQuery = $_GET['q'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$cartCount = count($data['cart']['items']);
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .catalog-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .catalog-card {
        transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s;
    }

    .catalog-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -8px rgba(0, 106, 97, 0.15);
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<div class="catalog-page space-y-6">
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-700">Area Katalog</p>
                <h2 class="mt-2 text-3xl font-extrabold text-slate-950">Katalog Produk Unggulan</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Cari produk lokal, filter kategori, lalu tambahkan ke keranjang.</p>
            </div>
            <a href="<?= BASEURL ?>user/cart" class="inline-flex items-center justify-center rounded-lg bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">
                Keranjang: <?= $cartCount ?> item
            </a>
        </div>
    </section>

    <section class="space-y-5">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form class="grid gap-3 xl:grid-cols-[minmax(260px,1fr)_130px_130px_auto]" method="get" action="<?= BASEURL ?>user/catalog">
                <label class="relative block">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase tracking-wide text-slate-400">Cari</span>
                    <input name="q" value="<?= htmlspecialchars($currentQuery) ?>" class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-16 pr-4 text-sm focus:border-emerald-700 focus:ring-emerald-100" placeholder="Produk, toko, atau kategori..." type="text">
                </label>
                <input name="min_price" value="<?= htmlspecialchars($minPrice) ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-700 focus:ring-emerald-100" placeholder="Min Rp" type="text">
                <input name="max_price" value="<?= htmlspecialchars($maxPrice) ?>" class="rounded-xl border border-slate-300 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-700 focus:ring-emerald-100" placeholder="Max Rp" type="text">
                <div class="flex gap-2">
                    <button class="rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Terapkan</button>
                    <?php if ($currentQuery !== '' || $minPrice !== '' || $maxPrice !== ''): ?>
                        <a href="<?= BASEURL ?>user/catalog" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50">Reset</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="text-sm text-slate-500">
                    <b class="text-slate-950"><?= count($data['products']) ?></b> produk ditemukan - Keranjang <b class="text-slate-950">Rp<?= number_format($data['cart']['total'], 0, ',', '.') ?></b>
                </div>
                <div class="no-scrollbar flex gap-2 overflow-x-auto pb-1">
                    <a class="whitespace-nowrap rounded-full bg-emerald-700 px-5 py-2 text-xs font-bold uppercase tracking-wide text-white" href="<?= BASEURL ?>user/catalog">Semua</a>
                    <?php foreach ($data['categories'] as $category): ?>
                        <a class="whitespace-nowrap rounded-full bg-slate-100 px-5 py-2 text-xs font-bold uppercase tracking-wide text-slate-600 hover:bg-emerald-50 hover:text-emerald-800" href="<?= BASEURL ?>user/catalog?q=<?= urlencode($category) ?>">
                            <?= htmlspecialchars($category) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

            <?php if (!$data['products']): ?>
                <section class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <h3 class="text-xl font-extrabold text-slate-950">Produk tidak ditemukan</h3>
                    <p class="mt-2 text-sm text-slate-500">Coba kata kunci atau rentang harga lain.</p>
                    <a href="<?= BASEURL ?>user/catalog" class="mt-5 inline-flex rounded-lg bg-emerald-700 px-5 py-3 text-sm font-bold text-white">Reset Katalog</a>
                </section>
            <?php else: ?>
                <section class="grid grid-cols-1 gap-5 md:grid-cols-2 2xl:grid-cols-4">
                    <?php foreach ($data['products'] as $index => $product): ?>
                        <?php
                        $rating = number_format(4.7 + (($index % 3) / 10), 1);
                        $heroBadge = $index % 2 === 0;
                        ?>
                        <article class="catalog-card group flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="relative h-48 overflow-hidden bg-slate-100">
                                <?php if (!empty($product['image_url'])): ?>
                                    <img alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" src="<?= htmlspecialchars($product['image_url']) ?>">
                                <?php else: ?>
                                    <div class="flex h-full w-full items-center justify-center text-sm font-bold text-slate-500">PasarKita</div>
                                <?php endif; ?>
                                <?php if ($heroBadge): ?>
                                    <span class="absolute left-4 top-4 rounded-full bg-amber-100 px-3 py-1 text-[10px] font-extrabold uppercase tracking-wide text-amber-800">Pahlawan UMKM</span>
                                <?php endif; ?>
                                <button type="button" class="absolute bottom-4 right-4 rounded-full bg-white/90 px-3 py-2 text-xs font-bold text-emerald-700 shadow-sm hover:bg-emerald-700 hover:text-white" data-favorite-button>Fav</button>
                            </div>
                            <div class="flex flex-1 flex-col p-4">
                                <div class="mb-2 flex items-start justify-between gap-3">
                                    <h3 class="font-extrabold leading-6 text-slate-950"><?= htmlspecialchars($product['name']) ?></h3>
                                    <span class="shrink-0 text-xs font-bold text-amber-700">Rating <?= $rating ?></span>
                                </div>
                                <p class="text-xs font-semibold text-slate-600">Toko <?= htmlspecialchars($product['store_name']) ?></p>
                                <p class="mt-4 text-sm text-slate-600"><?= htmlspecialchars($product['category']) ?> - Stok <?= (int) $product['stock'] ?></p>
                                <div class="mt-auto pt-5">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-xl font-extrabold text-emerald-700">Rp<?= number_format($product['price'], 0, ',', '.') ?></span>
                                        <span class="text-xs font-bold text-slate-500">Ready</span>
                                    </div>
                                    <form method="post" action="<?= BASEURL ?>user/addCart" class="mt-4 grid grid-cols-[86px_1fr] gap-2">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <input type="number" name="qty" min="1" max="<?= max(1, (int) $product['stock']) ?>" value="1" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-700 focus:ring-emerald-100">
                                        <button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm font-bold text-white hover:bg-emerald-800 disabled:bg-slate-300 disabled:text-slate-500" <?= (int) $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                            Tambah
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>

                <div class="flex justify-center pt-3">
                    <a class="rounded-xl border-2 border-emerald-700 px-8 py-4 font-bold text-emerald-700 hover:bg-emerald-700 hover:text-white" href="<?= BASEURL ?>user/catalog">Lihat Produk Lainnya</a>
                </div>
            <?php endif; ?>
    </section>
</div>

<script>
    document.querySelectorAll('[data-favorite-button]').forEach(function (button) {
        button.addEventListener('click', function () {
            button.textContent = button.textContent === 'Fav' ? 'Saved' : 'Fav';
        });
    });
</script>
