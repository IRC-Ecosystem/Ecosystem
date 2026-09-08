<?php
/** @var array $data */
$products = $data['products'] ?? [];
$categories = $data['categories'] ?? [];
$totalProducts = count($products);
$activeProducts = count(array_filter($products, static fn ($product) => ($product['status'] ?? '') === 'active'));
$emptyStock = count(array_filter($products, static fn ($product) => (int) ($product['stock'] ?? 0) <= 0));
$lowStock = count(array_filter($products, static fn ($product) => (int) ($product['stock'] ?? 0) > 0 && (int) ($product['stock'] ?? 0) <= 5));
$views30 = max(120, $totalProducts * 21 + $activeProducts * 7);
$money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$productSku = static fn (array $product): string => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product['category'] ?? 'PRD'), 0, 3)) . '-' . str_pad((string) $product['id'], 4, '0', STR_PAD_LEFT);
$safeAttr = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<style>
    .product-shell {
        font-family: "Plus Jakarta Sans", system-ui, sans-serif;
    }
    .product-drawer {
        transition: opacity 180ms ease, transform 180ms ease;
    }
    .product-drawer[aria-hidden="true"] {
        opacity: 0;
        pointer-events: none;
    }
    .product-drawer[aria-hidden="true"] .product-drawer-panel {
        transform: translateX(24px);
    }
</style>

<section class="product-shell space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0b1c30]">Manajemen Produk</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Kelola daftar produk, stok, kategori, harga, dan foto produk toko kamu.</p>
        </div>
        <button type="button" data-product-open="create" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049] active:scale-95">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            Tambah Produk Baru
        </button>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-4">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Total Produk</p>
            <p class="mt-2 text-2xl font-extrabold text-[#0b1c30]"><?= $totalProducts ?></p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-4">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Produk Aktif</p>
            <p class="mt-2 text-2xl font-extrabold text-[#00685f]"><?= $activeProducts ?></p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-4">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Stok Habis</p>
            <p class="mt-2 text-2xl font-extrabold text-[#ba1a1a]"><?= $emptyStock ?></p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-4">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Dilihat 30 Hari</p>
            <p class="mt-2 text-2xl font-extrabold text-[#0b1c30]"><?= number_format($views30, 0, ',', '.') ?></p>
        </article>
    </div>

    <div class="rounded-xl border border-[#bcc9c6] bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex w-full flex-col gap-3 md:flex-row md:items-center">
                <div class="relative flex-1 md:max-w-md">
                    <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#6d7a77]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                    <input id="productSearch" class="w-full rounded-lg border border-[#bcc9c6] py-3 pl-10 pr-4 text-sm text-[#0b1c30] outline-none transition focus:border-[#00685f] focus:ring-2 focus:ring-[#00685f]/20" placeholder="Cari nama produk atau SKU..." type="text">
                </div>
                <button type="button" class="hidden rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm font-bold text-[#0b1c30] transition hover:bg-[#eff4ff] sm:inline-flex">
                    Tindakan Massal
                </button>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <label class="flex items-center gap-2 text-sm font-bold text-[#3d4947]">
                    Kategori:
                    <select id="categoryFilter" class="rounded-lg border border-[#bcc9c6] bg-white px-3 py-3 text-sm font-semibold text-[#0b1c30] focus:border-[#00685f] focus:ring-[#00685f]/20">
                        <option value="">Semua</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars(strtolower($category['category'])) ?>"><?= htmlspecialchars($category['category']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-sm font-bold text-[#3d4947]">
                    Stok:
                    <select id="stockFilter" class="rounded-lg border border-[#bcc9c6] bg-white px-3 py-3 text-sm font-semibold text-[#0b1c30] focus:border-[#00685f] focus:ring-[#00685f]/20">
                        <option value="">Semua</option>
                        <option value="available">Tersedia</option>
                        <option value="low">Hampir Habis</option>
                        <option value="empty">Habis</option>
                    </select>
                </label>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-left text-sm">
                <thead>
                    <tr class="border-b border-[#bcc9c6] bg-[#f8f9ff]">
                        <th class="w-12 px-6 py-4 text-center"><input class="rounded border-[#bcc9c6] text-[#00685f] focus:ring-[#00685f]/20" type="checkbox"></th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Info Produk</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">SKU</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Kategori</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Harga</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Stok</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Status</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Aksi</th>
                    </tr>
                </thead>
                <tbody id="productRows" class="divide-y divide-[#bcc9c6]">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $stock = (int) $product['stock'];
                        $stockState = $stock <= 0 ? 'empty' : ($stock <= 5 ? 'low' : 'available');
                        $isActive = ($product['status'] ?? '') === 'active';
                        $image = trim((string) ($product['image_url'] ?? ''));
                        ?>
                        <tr class="product-row transition hover:bg-[#f8f9ff]" data-name="<?= $safeAttr(strtolower($product['name'] . ' ' . $productSku($product))) ?>" data-category="<?= $safeAttr(strtolower($product['category'])) ?>" data-stock-state="<?= $stockState ?>">
                            <td class="px-6 py-4 text-center"><input class="rounded border-[#bcc9c6] text-[#00685f] focus:ring-[#00685f]/20" type="checkbox"></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-[#bcc9c6] bg-[#eff4ff]">
                                        <?php if ($image): ?>
                                            <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <div class="flex h-full w-full items-center justify-center text-[#00685f]">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="min-w-[220px]">
                                        <p class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($product['name']) ?></p>
                                        <p class="mt-1 line-clamp-1 text-xs text-[#3d4947]"><?= htmlspecialchars($product['description'] ?: 'Deskripsi produk belum diisi') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-semibold text-[#0b1c30]"><?= htmlspecialchars($productSku($product)) ?></td>
                            <td class="px-6 py-4"><span class="rounded bg-[#dce9ff] px-3 py-1 text-xs font-bold text-[#3d4947]"><?= htmlspecialchars($product['category']) ?></span></td>
                            <td class="px-6 py-4 font-semibold text-[#0b1c30]"><?= $money($product['price']) ?></td>
                            <td class="px-6 py-4">
                                <?php if ($stockState === 'empty'): ?>
                                    <span class="font-extrabold text-[#ba1a1a]">0</span>
                                    <p class="text-[10px] font-extrabold uppercase text-[#ba1a1a]">Habis</p>
                                <?php elseif ($stockState === 'low'): ?>
                                    <span class="font-extrabold text-[#ba1a1a]"><?= $stock ?></span>
                                    <p class="text-[10px] font-extrabold uppercase text-[#ba1a1a]">Hampir Habis</p>
                                <?php else: ?>
                                    <span class="font-semibold text-[#0b1c30]"><?= $stock ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($isActive): ?>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-[#008378]/10 px-3 py-1 text-xs font-extrabold text-[#00685f]"><span class="h-1.5 w-1.5 rounded-full bg-[#00685f]"></span>Aktif</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-2 rounded-full bg-[#bcc9c6]/20 px-3 py-1 text-xs font-extrabold text-[#3d4947]"><span class="h-1.5 w-1.5 rounded-full bg-[#6d7a77]"></span>Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="rounded p-2 text-[#3d4947] transition hover:bg-[#00685f]/10 hover:text-[#00685f]"
                                        data-product-open="edit"
                                        data-id="<?= (int) $product['id'] ?>"
                                        data-name="<?= $safeAttr($product['name']) ?>"
                                        data-category="<?= $safeAttr($product['category']) ?>"
                                        data-description="<?= $safeAttr($product['description']) ?>"
                                        data-price="<?= $safeAttr($product['price']) ?>"
                                        data-stock="<?= (int) $product['stock'] ?>"
                                        data-image-url="<?= $safeAttr($product['image_url']) ?>"
                                        data-status="<?= $safeAttr($product['status']) ?>"
                                        aria-label="Edit produk">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                    </button>
                                    <a href="<?= BASEURL ?>toko/deleteProduct/<?= (int) $product['id'] ?>" class="rounded p-2 text-[#3d4947] transition hover:bg-[#ffdad6] hover:text-[#ba1a1a]" aria-label="Hapus produk">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6M14 11v6"></path></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$products): ?>
                        <tr><td colspan="8" class="px-6 py-12 text-center text-sm text-[#3d4947]">Belum ada produk. Tekan tombol Tambah Produk Baru untuk mengisi katalog toko.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-col justify-between gap-3 border-t border-[#bcc9c6] px-6 py-4 text-sm text-[#3d4947] md:flex-row md:items-center">
            <p>Menampilkan <span id="visibleProductCount"><?= $totalProducts ?></span> dari <?= $totalProducts ?> produk</p>
            <div class="flex items-center gap-2">
                <button class="flex h-8 w-8 items-center justify-center rounded border border-[#bcc9c6] text-[#6d7a77]" disabled>&lt;</button>
                <button class="flex h-8 w-8 items-center justify-center rounded bg-[#00685f] text-xs font-extrabold text-white">1</button>
                <button class="flex h-8 w-8 items-center justify-center rounded border border-[#bcc9c6] text-xs font-bold">2</button>
                <button class="flex h-8 w-8 items-center justify-center rounded border border-[#bcc9c6] text-[#6d7a77]">&gt;</button>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-[#00685f]/20 bg-[#00685f]/5 p-6">
        <div class="flex gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#00685f]/10 text-[#00685f]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"></path><path d="M10 22h4"></path><path d="M12 2a7 7 0 0 0-4 12c.6.6 1 1.3 1 2h6c0-.7.4-1.4 1-2A7 7 0 0 0 12 2z"></path></svg>
            </div>
            <div>
                <h2 class="text-xl font-extrabold text-[#00685f]">Tips Mengelola Produk</h2>
                <p class="mt-2 text-sm leading-6 text-[#3d4947]">Pastikan stok selalu terupdate. Produk dengan deskripsi lengkap dan foto yang jelas lebih mudah ditemukan pembeli.</p>
            </div>
        </div>
    </div>
</section>

<div id="productDrawer" class="product-drawer fixed inset-0 z-[70] bg-[#0b1c30]/45" aria-hidden="true">
    <div class="absolute inset-y-0 right-0 flex w-full justify-end">
        <form id="productForm" method="post" action="<?= BASEURL ?>toko/product" class="product-drawer-panel flex h-full w-full max-w-4xl flex-col overflow-y-auto bg-[#f8f9ff] shadow-2xl transition-transform">
            <?= csrf_field() ?>
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[#bcc9c6] bg-white px-6 py-5">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-[#00685f]">Produk Seller</p>
                    <h2 id="productFormTitle" class="text-2xl font-extrabold text-[#0b1c30]">Tambah Produk Baru</h2>
                </div>
                <button type="button" data-product-close class="rounded-lg border border-[#bcc9c6] p-2 text-[#3d4947] transition hover:bg-[#eff4ff]" aria-label="Tutup form">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="grid gap-6 p-6">
                <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#00685f]/10 text-[#00685f]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4M12 8h.01"></path></svg>
                        </span>
                        <h3 class="text-xl font-extrabold text-[#0b1c30]">Informasi Produk</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
                        <label class="md:col-span-8">
                            <span class="text-sm font-extrabold text-[#3d4947]">Nama Produk <b class="text-[#ba1a1a]">*</b></span>
                            <input id="productName" name="name" required class="mt-2 w-full rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="Contoh: Kemeja Batik Pria Slim Fit Premium" type="text">
                            <span class="mt-2 block text-xs text-[#6d7a77]">Gunakan nama yang deskriptif agar mudah ditemukan pembeli.</span>
                        </label>
                        <label class="md:col-span-4">
                            <span class="text-sm font-extrabold text-[#3d4947]">Kategori <b class="text-[#ba1a1a]">*</b></span>
                            <input id="productCategory" name="category" required list="sellerCategories" class="mt-2 w-full rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="Pilih atau tulis kategori">
                            <datalist id="sellerCategories">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= htmlspecialchars($category['category']) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </label>
                        <label class="md:col-span-12">
                            <span class="text-sm font-extrabold text-[#3d4947]">Deskripsi Produk</span>
                            <textarea id="productDescription" name="description" class="mt-2 w-full rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="Jelaskan detail produk, bahan, ukuran, rasa, manfaat, atau cara perawatan..." rows="5"></textarea>
                        </label>
                    </div>
                </section>

                <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#00685f]/10 text-[#00685f]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        </span>
                        <h3 class="text-xl font-extrabold text-[#0b1c30]">Detail Penjualan</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <label>
                            <span class="text-sm font-extrabold text-[#3d4947]">Harga (Rp) <b class="text-[#ba1a1a]">*</b></span>
                            <div class="relative mt-2">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-extrabold text-[#6d7a77]">Rp</span>
                                <input id="productPrice" name="price" required min="0" class="w-full rounded-lg border border-[#bcc9c6] py-3 pl-12 pr-4 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="0" type="number">
                            </div>
                        </label>
                        <label>
                            <span class="text-sm font-extrabold text-[#3d4947]">Stok <b class="text-[#ba1a1a]">*</b></span>
                            <input id="productStock" name="stock" required min="0" class="mt-2 w-full rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="0" type="number">
                        </label>
                        <label>
                            <span class="text-sm font-extrabold text-[#3d4947]">Status Tampil</span>
                            <select id="productStatus" name="status" class="mt-2 w-full rounded-lg border border-[#bcc9c6] bg-white px-4 py-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20">
                                <option value="active">Aktif</option>
                                <option value="inactive">Nonaktif</option>
                            </select>
                        </label>
                    </div>
                </section>

                <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#00685f]/10 text-[#00685f]">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="m21 15-5-5L5 21"></path></svg>
                            </span>
                            <h3 class="text-xl font-extrabold text-[#0b1c30]">Foto Produk</h3>
                        </div>
                        <span class="text-xs font-bold text-[#6d7a77]">URL foto utama</span>
                    </div>
                    <div class="grid gap-5 lg:grid-cols-[220px_1fr]">
                        <div class="aspect-square overflow-hidden rounded-xl border-2 border-dashed border-[#00685f]/30 bg-[#00685f]/5">
                            <img id="productImagePreview" alt="Preview produk" class="hidden h-full w-full object-cover">
                            <div id="productImageEmpty" class="flex h-full flex-col items-center justify-center gap-2 text-[#00685f]">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
                                <p class="text-xs font-extrabold uppercase">Foto Utama</p>
                            </div>
                        </div>
                        <div>
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">URL Foto Produk</span>
                                <input id="productImageUrl" name="image_url" class="mt-2 w-full rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="https://..." type="url">
                            </label>
                            <div class="mt-4 rounded-lg bg-[#dae2fd]/40 p-4">
                                <p class="text-sm leading-6 text-[#3f465c]">Tip: gunakan foto berlatar terang dengan produk terlihat jelas. Untuk sekarang backend menyimpan foto sebagai URL.</p>
                            </div>
                            <div class="mt-4 grid grid-cols-4 gap-3">
                                <?php for ($i = 0; $i < 4; $i++): ?>
                                    <div class="aspect-square rounded-lg border-2 border-dashed border-[#bcc9c6] bg-[#f8f9ff]"></div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <div class="sticky bottom-0 flex flex-col justify-between gap-3 border-t border-[#bcc9c6] bg-white/90 px-6 py-4 backdrop-blur sm:flex-row sm:items-center">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-bold text-[#0b1c30]">Tampilkan di toko</span>
                    <span class="rounded-full bg-[#89f5e7] px-3 py-1 text-xs font-extrabold text-[#00201d]">PasarKita</span>
                </div>
                <div class="flex gap-3">
                    <button type="button" data-product-close class="rounded-lg border border-[#00685f] px-5 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Batalkan</button>
                    <button id="productSubmitLabel" class="rounded-lg bg-[#00685f] px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049]">Simpan Produk</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const drawer = document.getElementById('productDrawer');
        const form = document.getElementById('productForm');
        const title = document.getElementById('productFormTitle');
        const submitLabel = document.getElementById('productSubmitLabel');
        const imageUrl = document.getElementById('productImageUrl');
        const preview = document.getElementById('productImagePreview');
        const emptyPreview = document.getElementById('productImageEmpty');
        const createAction = '<?= BASEURL ?>toko/product';
        const updateActionBase = '<?= BASEURL ?>toko/updateProduct/';

        function setPreview(url) {
            if (url) {
                preview.src = url;
                preview.classList.remove('hidden');
                emptyPreview.classList.add('hidden');
            } else {
                preview.removeAttribute('src');
                preview.classList.add('hidden');
                emptyPreview.classList.remove('hidden');
            }
        }

        function openDrawer(mode, trigger) {
            form.reset();
            if (mode === 'edit') {
                form.action = updateActionBase + trigger.dataset.id;
                title.textContent = 'Edit Produk';
                submitLabel.textContent = 'Update Produk';
                document.getElementById('productName').value = trigger.dataset.name || '';
                document.getElementById('productCategory').value = trigger.dataset.category || '';
                document.getElementById('productDescription').value = trigger.dataset.description || '';
                document.getElementById('productPrice').value = trigger.dataset.price || '';
                document.getElementById('productStock').value = trigger.dataset.stock || '';
                document.getElementById('productStatus').value = trigger.dataset.status || 'active';
                imageUrl.value = trigger.dataset.imageUrl || '';
                setPreview(imageUrl.value);
            } else {
                form.action = createAction;
                title.textContent = 'Tambah Produk Baru';
                submitLabel.textContent = 'Simpan Produk';
                document.getElementById('productStatus').value = 'active';
                setPreview('');
            }
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function closeDrawer() {
            drawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-product-open]').forEach((button) => {
            button.addEventListener('click', () => openDrawer(button.dataset.productOpen, button));
        });

        document.querySelectorAll('[data-product-close]').forEach((button) => {
            button.addEventListener('click', closeDrawer);
        });

        drawer.addEventListener('click', (event) => {
            if (event.target === drawer) closeDrawer();
        });

        imageUrl.addEventListener('input', () => setPreview(imageUrl.value.trim()));

        const search = document.getElementById('productSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const stockFilter = document.getElementById('stockFilter');
        const visibleCount = document.getElementById('visibleProductCount');

        function applyFilters() {
            const keyword = search.value.trim().toLowerCase();
            const category = categoryFilter.value;
            const stock = stockFilter.value;
            let shown = 0;

            document.querySelectorAll('.product-row').forEach((row) => {
                const matchesKeyword = !keyword || row.dataset.name.includes(keyword);
                const matchesCategory = !category || row.dataset.category === category;
                const matchesStock = !stock || row.dataset.stockState === stock;
                const visible = matchesKeyword && matchesCategory && matchesStock;
                row.classList.toggle('hidden', !visible);
                if (visible) shown++;
            });

            visibleCount.textContent = shown;
        }

        [search, categoryFilter, stockFilter].forEach((input) => input.addEventListener('input', applyFilters));
        [categoryFilter, stockFilter].forEach((input) => input.addEventListener('change', applyFilters));

        document.querySelectorAll('tbody input[type="checkbox"]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const row = checkbox.closest('tr');
                if (row) row.classList.toggle('bg-[#00685f]/5', checkbox.checked);
            });
        });
    })();
</script>
