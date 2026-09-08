<?php
/** @var array $data */
$lowStock = $data['lowStock'] ?? [];
$products = $data['products'] ?? [];
$criticalProducts = $lowStock ?: array_slice(array_filter($products, static fn ($product) => (int) ($product['stock'] ?? 0) <= 10), 0, 5);
$activeRestocks = max(1, (int) ceil(count($criticalProducts) / 2));
$recommendations = max(count($criticalProducts), count($products) ? min(8, count($products)) : 0);
$money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$sku = static fn (array $product): string => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product['category'] ?? 'PRD'), 0, 3)) . '-' . str_pad((string) ($product['id'] ?? 0), 4, '0', STR_PAD_LEFT);
$supplierList = [
    ['name' => 'CV. Batik Tekstil Nusantara', 'meta' => 'Kapasitas: Tinggi - Respon: 2 Jam'],
    ['name' => 'Koperasi Pengrajin Rattan Lestari', 'meta' => 'Kapasitas: Sedang - Respon: 5 Jam'],
    ['name' => 'Jepara Woodcraft Supplier', 'meta' => 'Kapasitas: Custom - Respon: 1 Hari'],
];
?>

<style>
    .restock-drawer {
        transition: opacity 180ms ease;
    }
    .restock-drawer[aria-hidden="true"] {
        opacity: 0;
        pointer-events: none;
    }
    .restock-drawer[aria-hidden="true"] .restock-drawer-panel {
        transform: translateX(24px);
    }
</style>

<section class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0b1c30]">Manajemen Restok</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Pantau stok produk dan lakukan pengadaan barang tepat waktu melalui integrasi SupplierHub.</p>
        </div>
        <button type="button" data-restock-open="create" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049] active:scale-95">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path><path d="M12 8v6M9 11h6"></path></svg>
            Buat Pesanan Restok Baru
        </button>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm transition hover:border-[#ba1a1a]">
            <div class="mb-4 flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#ffdad6] text-[#93000a]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                </div>
                <span class="text-xl font-extrabold text-[#ba1a1a]"><?= count($criticalProducts) ?> Produk</span>
            </div>
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Stok Kritis</h2>
            <p class="mt-2 text-sm text-[#3d4947]">Produk dengan stok di bawah level keamanan.</p>
            <button type="button" class="mt-5 border-t border-[#bcc9c6] pt-4 text-sm font-extrabold text-[#ba1a1a]">Lihat Produk &rarr;</button>
        </article>

        <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm transition hover:border-[#00685f]">
            <div class="mb-4 flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#008378] text-white">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"></path><path d="M14 17h1m2 0h5v-6l-3-4h-5"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
                </div>
                <span class="text-xl font-extrabold text-[#00685f]"><?= $activeRestocks ?> Aktif</span>
            </div>
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Pesanan Restok Aktif</h2>
            <p class="mt-2 text-sm text-[#3d4947]">Pesanan yang sedang diproses oleh supplier.</p>
            <button type="button" class="mt-5 border-t border-[#bcc9c6] pt-4 text-sm font-extrabold text-[#00685f]">Lacak Pengiriman &rarr;</button>
        </article>

        <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm transition hover:border-[#825100]">
            <div class="mb-4 flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#ffddb8] text-[#653e00]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg>
                </div>
                <span class="text-xl font-extrabold text-[#825100]"><?= $recommendations ?> Rekomendasi</span>
            </div>
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Rekomendasi Restok</h2>
            <p class="mt-2 text-sm text-[#3d4947]">Berdasarkan prediksi penjualan dan stok menipis.</p>
            <button type="button" class="mt-5 border-t border-[#bcc9c6] pt-4 text-sm font-extrabold text-[#825100]">Gunakan AI Restok &rarr;</button>
        </article>
    </div>

    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-[#bcc9c6] bg-[#eff4ff] p-6 md:flex-row md:items-center">
            <h2 class="flex items-center gap-2 text-xl font-extrabold text-[#0b1c30]">
                <svg class="h-5 w-5 text-[#00685f]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path></svg>
                Daftar Produk Perlu Restok
            </h2>
            <div class="flex gap-2">
                <button class="rounded-lg border border-[#bcc9c6] bg-white px-4 py-2 text-sm font-bold text-[#3d4947]">Filter</button>
                <button class="rounded-lg border border-[#bcc9c6] bg-white px-4 py-2 text-sm font-bold text-[#3d4947]">Ekspor</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-[#bcc9c6] bg-[#dce9ff]/50">
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Produk</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">SKU</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Stok Saat Ini</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Safety Stock</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bcc9c6]">
                    <?php foreach ($criticalProducts as $product): ?>
                        <?php
                        $stock = (int) ($product['stock'] ?? 0);
                        $safety = max(10, $stock + 15);
                        $critical = $stock <= 5;
                        ?>
                        <tr class="transition hover:bg-[#eff4ff]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="h-12 w-12 overflow-hidden rounded-lg border border-[#bcc9c6] bg-[#eff4ff]">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="h-full w-full object-cover">
                                        <?php else: ?>
                                            <div class="flex h-full items-center justify-center text-[#00685f]">
                                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($product['name']) ?></p>
                                        <p class="text-xs text-[#6d7a77]">Kategori: <?= htmlspecialchars($product['category'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-[#3d4947]"><?= htmlspecialchars($sku($product)) ?></td>
                            <td class="px-6 py-4 font-extrabold <?= $critical ? 'text-[#ba1a1a]' : 'text-[#825100]' ?>"><?= $stock ?> Pcs</td>
                            <td class="px-6 py-4 text-[#3d4947]"><?= $safety ?> Pcs</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-[10px] font-extrabold uppercase <?= $critical ? 'bg-[#ffdad6] text-[#93000a]' : 'bg-[#ffddb8] text-[#653e00]' ?>"><?= $critical ? 'Stok Kritis' : 'Hampir Habis' ?></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button type="button"
                                    data-restock-open="edit"
                                    data-product="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-stock="<?= $stock ?>"
                                    data-price="<?= (float) ($product['price'] ?? 0) ?>"
                                    class="rounded-lg bg-[#00685f] px-4 py-2 text-xs font-extrabold text-white transition hover:bg-[#005049]">
                                    Restok Sekarang
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$criticalProducts): ?>
                        <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-[#6d7a77]">Tidak ada stok menipis.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-col justify-between gap-3 border-t border-[#bcc9c6] bg-[#eff4ff] p-6 text-sm text-[#3d4947] md:flex-row md:items-center">
            <p>Menampilkan <?= count($criticalProducts) ?> dari <?= max(count($criticalProducts), count($products)) ?> produk kritis</p>
            <div class="flex gap-2">
                <button class="rounded-lg border border-[#bcc9c6] bg-white px-3 py-2">&lt;</button>
                <button class="rounded-lg bg-[#00685f] px-4 py-2 font-extrabold text-white">1</button>
                <button class="rounded-lg border border-[#bcc9c6] bg-white px-4 py-2">2</button>
                <button class="rounded-lg border border-[#bcc9c6] bg-white px-3 py-2">&gt;</button>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-[#0b1c30]">Supplier Terhubung</h2>
                <button class="text-sm font-extrabold text-[#00685f] hover:underline">Kelola Supplier</button>
            </div>
            <div class="space-y-4">
                <?php foreach ($supplierList as $supplier): ?>
                    <div class="flex cursor-pointer items-center gap-4 rounded-xl border border-[#bcc9c6] p-4 transition hover:bg-[#eff4ff]">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#dae2fd] text-[#3f465c]">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-4"></path></svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($supplier['name']) ?></h3>
                            <p class="text-xs text-[#6d7a77]"><?= htmlspecialchars($supplier['meta']) ?></p>
                        </div>
                        <span class="text-[#6d7a77]">&gt;</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-[#0b1c30]">Riwayat Restok</h2>
                <button class="text-sm font-extrabold text-[#00685f] hover:underline">Lihat Semua</button>
            </div>
            <div id="restockTimeline" class="relative overflow-hidden">
                <div class="absolute bottom-0 left-6 top-0 w-px bg-[#bcc9c6]"></div>
                <div class="relative space-y-6">
                    <div class="flex gap-4">
                        <div class="z-10 flex h-12 w-12 items-center justify-center rounded-full border-4 border-white bg-[#eff4ff] text-[#00685f]">OK</div>
                        <div class="flex-1">
                            <div class="flex justify-between gap-3">
                                <h3 class="font-extrabold text-[#0b1c30]">Pengiriman Diterima</h3>
                                <span class="text-xs text-[#6d7a77]">Tadi, 10:45</span>
                            </div>
                            <p class="mt-1 text-sm text-[#3d4947]">PO #77281 - Produk stok kritis telah masuk ke gudang utama.</p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <div class="z-10 flex h-12 w-12 items-center justify-center rounded-full border-4 border-white bg-[#eff4ff] text-[#825100]">SH</div>
                        <div class="flex-1">
                            <div class="flex justify-between gap-3">
                                <h3 class="font-extrabold text-[#0b1c30]">Pesanan Sedang Dikirim</h3>
                                <span class="text-xs text-[#6d7a77]">Kemarin</span>
                            </div>
                            <p class="mt-1 text-sm text-[#3d4947]">PO #77285 - Restok sedang menuju lokasi gudang.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</section>

<div id="restockDrawer" class="restock-drawer fixed inset-0 z-[70] bg-[#0b1c30]/45" aria-hidden="true">
    <div class="absolute inset-y-0 right-0 flex w-full justify-end">
        <form id="restockForm" class="restock-drawer-panel flex h-full w-full max-w-6xl flex-col overflow-y-auto bg-[#f8f9ff] shadow-2xl transition-transform">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[#bcc9c6] bg-white px-6 py-5">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-[#00685f]">SupplierHub</p>
                    <h2 id="restockFormTitle" class="text-2xl font-extrabold text-[#0b1c30]">Buat Pesanan Restok Baru</h2>
                </div>
                <button type="button" data-restock-close class="rounded-lg border border-[#bcc9c6] p-2 text-[#3d4947] transition hover:bg-[#eff4ff]" aria-label="Tutup form">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="grid gap-6 p-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="text-[#00685f]">Supplier</span>
                            <h3 class="text-xl font-extrabold text-[#0b1c30]">Pilih Supplier</h3>
                        </div>
                        <label class="block">
                            <span class="text-sm font-extrabold text-[#3d4947]">Cari atau pilih supplier terdaftar</span>
                            <select id="restockSupplier" class="mt-2 w-full rounded-lg border border-[#bcc9c6] bg-white p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20">
                                <?php foreach ($supplierList as $supplier): ?>
                                    <option><?= htmlspecialchars($supplier['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="mt-4 flex items-center gap-4 rounded-lg border border-dashed border-[#bcc9c6] bg-[#eff4ff] p-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#008378] text-white">SH</div>
                            <div>
                                <h4 id="supplierPreview" class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($supplierList[0]['name']) ?></h4>
                                <p class="text-xs text-[#6d7a77]">Rating 4.9 - Terhubung ke SupplierHub</p>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-[#bcc9c6] p-6">
                            <h3 class="text-xl font-extrabold text-[#0b1c30]">Daftar Produk Restok</h3>
                            <button type="button" id="addRestockProduct" class="rounded-lg bg-[#008378] px-4 py-2 text-sm font-extrabold text-white">Tambah Produk</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-[#eff4ff]">
                                    <tr>
                                        <th class="px-6 py-3 text-xs font-extrabold uppercase text-[#3d4947]">Produk & SKU</th>
                                        <th class="px-6 py-3 text-center text-xs font-extrabold uppercase text-[#3d4947]">Stok Saat Ini</th>
                                        <th class="px-6 py-3 text-center text-xs font-extrabold uppercase text-[#3d4947]">Jumlah Restok</th>
                                        <th class="px-6 py-3 text-right text-xs font-extrabold uppercase text-[#3d4947]">Harga Satuan</th>
                                        <th class="px-6 py-3 text-right text-xs font-extrabold uppercase text-[#3d4947]">Subtotal</th>
                                        <th class="px-6 py-3"></th>
                                    </tr>
                                </thead>
                                <tbody id="restockItems" class="divide-y divide-[#bcc9c6]">
                                    <tr data-restock-row>
                                        <td class="px-6 py-4">
                                            <select class="restock-product w-full rounded-lg border border-[#bcc9c6] p-2 text-sm">
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" data-stock="<?= (int) $product['stock'] ?>" data-price="<?= (float) $product['price'] ?>" data-sku="<?= htmlspecialchars($sku($product), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($product['name']) ?> - <?= htmlspecialchars($sku($product)) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td class="current-stock px-6 py-4 text-center font-bold text-[#ba1a1a]">0 unit</td>
                                        <td class="px-6 py-4 text-center"><input class="restock-qty w-20 rounded-lg border border-[#bcc9c6] p-2 text-center" type="number" min="1" value="50"></td>
                                        <td class="unit-price px-6 py-4 text-right text-[#3d4947]">Rp 0</td>
                                        <td class="row-subtotal px-6 py-4 text-right font-extrabold text-[#0b1c30]">Rp 0</td>
                                        <td class="px-6 py-4 text-center"><button type="button" data-remove-row class="text-[#ba1a1a]">Hapus</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="border-t border-[#bcc9c6] bg-[#eff4ff] p-6">
                            <textarea id="restockNotes" class="min-h-24 w-full rounded-lg border border-[#bcc9c6] p-4 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="Tambah catatan untuk pesanan restok ini (opsional)..."></textarea>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6 lg:sticky lg:top-24">
                    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
                        <div class="border-b border-[#bcc9c6] bg-[#eff4ff] p-6">
                            <h3 class="text-xl font-extrabold text-[#0b1c30]">Ringkasan Pesanan</h3>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="flex justify-between text-sm"><span class="text-[#3d4947]">Subtotal (<span id="summaryItems">1</span> Produk)</span><b id="summarySubtotal">Rp 0</b></div>
                            <div class="flex justify-between text-sm"><span class="text-[#3d4947]">Estimasi Pengiriman</span><b id="summaryShipping">Rp 150.000</b></div>
                            <div class="flex justify-between text-sm"><span class="text-[#3d4947]">Biaya Penanganan</span><b>Rp 10.000</b></div>
                            <div class="border-t border-[#bcc9c6] pt-4">
                                <p class="text-xs font-extrabold uppercase text-[#6d7a77]">Total Biaya</p>
                                <h4 id="summaryTotal" class="mt-1 text-3xl font-extrabold text-[#00685f]">Rp 160.000</h4>
                                <button class="mt-6 w-full rounded-xl bg-[#00685f] px-5 py-4 text-lg font-extrabold text-white shadow-md transition hover:bg-[#005049]">Konfirmasi & Buat Pesanan</button>
                                <p class="mt-3 text-center text-xs leading-5 text-[#6d7a77]">Dengan mengonfirmasi, pesanan restok akan dikirim ke simulasi SupplierHub.</p>
                            </div>
                        </div>
                    </section>

                    <section class="flex gap-4 rounded-xl bg-[#dae2fd] p-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white text-[#565e74]">?</div>
                        <div>
                            <h3 class="text-sm font-extrabold text-[#3f465c]">Butuh Bantuan?</h3>
                            <p class="mt-1 text-xs leading-5 text-[#3f465c]">Hubungi Account Manager untuk negosiasi harga grosir.</p>
                            <a href="<?= BASEURL ?>toko/chat" class="mt-2 inline-block text-xs font-extrabold text-[#00685f]">Hubungi via Chat</a>
                        </div>
                    </section>
                </aside>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const drawer = document.getElementById('restockDrawer');
        const form = document.getElementById('restockForm');
        const title = document.getElementById('restockFormTitle');
        const supplier = document.getElementById('restockSupplier');
        const supplierPreview = document.getElementById('supplierPreview');
        const rows = document.getElementById('restockItems');
        const addButton = document.getElementById('addRestockProduct');
        const timeline = document.getElementById('restockTimeline');
        const shipping = 150000;
        const handling = 10000;

        function rupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        }

        function updateRow(row) {
            const select = row.querySelector('.restock-product');
            const option = select.selectedOptions[0];
            const stock = Number(option?.dataset.stock || 0);
            const price = Number(option?.dataset.price || 0);
            const qty = Number(row.querySelector('.restock-qty').value || 0);
            row.querySelector('.current-stock').textContent = stock + ' unit';
            row.querySelector('.unit-price').textContent = rupiah(price);
            row.querySelector('.row-subtotal').textContent = rupiah(price * qty);
            updateSummary();
        }

        function updateSummary() {
            let subtotal = 0;
            rows.querySelectorAll('[data-restock-row]').forEach((row) => {
                const option = row.querySelector('.restock-product').selectedOptions[0];
                const price = Number(option?.dataset.price || 0);
                const qty = Number(row.querySelector('.restock-qty').value || 0);
                subtotal += price * qty;
            });
            document.getElementById('summaryItems').textContent = rows.querySelectorAll('[data-restock-row]').length;
            document.getElementById('summarySubtotal').textContent = rupiah(subtotal);
            document.getElementById('summaryShipping').textContent = rupiah(shipping);
            document.getElementById('summaryTotal').textContent = rupiah(subtotal + shipping + handling);
        }

        function wireRow(row) {
            row.querySelector('.restock-product').addEventListener('change', () => updateRow(row));
            row.querySelector('.restock-qty').addEventListener('input', () => updateRow(row));
            row.querySelector('[data-remove-row]').addEventListener('click', () => {
                if (rows.querySelectorAll('[data-restock-row]').length > 1) row.remove();
                updateSummary();
            });
            updateRow(row);
        }

        function openDrawer(mode, trigger) {
            title.textContent = mode === 'edit' ? 'Edit Pesanan Restok' : 'Buat Pesanan Restok Baru';
            if (trigger?.dataset.product) {
                const select = rows.querySelector('.restock-product');
                [...select.options].forEach((option) => {
                    option.selected = option.value === trigger.dataset.product;
                });
                rows.querySelector('.restock-qty').value = 50;
                updateRow(rows.querySelector('[data-restock-row]'));
            }
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function closeDrawer() {
            drawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-restock-open]').forEach((button) => {
            button.addEventListener('click', () => openDrawer(button.dataset.restockOpen, button));
        });
        document.querySelectorAll('[data-restock-close]').forEach((button) => button.addEventListener('click', closeDrawer));
        drawer.addEventListener('click', (event) => {
            if (event.target === drawer) closeDrawer();
        });

        supplier.addEventListener('change', () => supplierPreview.textContent = supplier.value);
        rows.querySelectorAll('[data-restock-row]').forEach(wireRow);
        addButton.addEventListener('click', () => {
            const clone = rows.querySelector('[data-restock-row]').cloneNode(true);
            clone.querySelector('.restock-qty').value = 25;
            rows.appendChild(clone);
            wireRow(clone);
            updateSummary();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const total = document.getElementById('summaryTotal').textContent;
            timeline.querySelector('.relative.space-y-6').insertAdjacentHTML('afterbegin', '<div class="flex gap-4"><div class="z-10 flex h-12 w-12 items-center justify-center rounded-full border-4 border-white bg-[#eff4ff] text-[#00685f]">PO</div><div class="flex-1"><div class="flex justify-between gap-3"><h3 class="font-extrabold text-[#0b1c30]">Draft Pesanan Dibuat</h3><span class="text-xs text-[#6d7a77]">Baru saja</span></div><p class="mt-1 text-sm text-[#3d4947]">Pesanan restok SupplierHub dibuat dengan total ' + total + '.</p></div></div>');
            closeDrawer();
        });
    })();
</script>
