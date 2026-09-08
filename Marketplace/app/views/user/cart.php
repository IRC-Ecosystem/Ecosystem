<?php
/** @var array $data */
$summary = $data['summary'];
$itemCount = array_sum(array_map(fn ($item) => (int) $item['qty'], $summary['items']));
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

    .cart-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .cart-item {
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .cart-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 28px -20px rgba(0, 104, 95, .35);
    }
</style>

<div class="cart-page space-y-6">
    <section>
        <h1 class="text-3xl font-extrabold text-slate-950">Keranjang Belanja</h1>
        <p class="mt-2 text-sm text-slate-600">Kelola produk UMKM pilihan kamu sebelum melakukan pembayaran.</p>
    </section>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
        <div class="space-y-4 lg:col-span-2">
            <?php if (!$summary['items']): ?>
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                        <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57L21 8H5.12"/></svg>
                    </div>
                    <h2 class="mt-5 text-2xl font-extrabold text-slate-950">Keranjang Masih Kosong</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500 max-w-md mx-auto">Pilih produk berkualitas dari para pelaku UMKM Indonesia di katalog PasarKita dan masukkan ke keranjang belanja Anda.</p>
                    <a href="<?= BASEURL ?>user/catalog" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-900/10 transition hover:bg-emerald-800 active:scale-95">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/></svg>
                        Jelajahi Katalog
                    </a>
                </div>
            <?php endif; ?>

            <?php foreach ($summary['items'] as $item): ?>
                <?php $product = $item['product']; ?>
                <article class="cart-item flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:flex-row">
                    <div class="h-36 w-full shrink-0 overflow-hidden rounded-lg bg-slate-100 md:h-32 md:w-32">
                        <?php if (!empty($product['image_url'])): ?>
                            <img class="h-full w-full object-cover transition duration-500 hover:scale-105" src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php else: ?>
                            <div class="flex h-full w-full items-center justify-center text-sm font-bold text-slate-500">PasarKita</div>
                        <?php endif; ?>
                    </div>

                    <div class="flex min-w-0 flex-1 flex-col justify-between">
                        <div>
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h2 class="text-lg font-extrabold leading-6 text-slate-950"><?= htmlspecialchars($product['name']) ?></h2>
                                    <p class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700"><?= htmlspecialchars($product['store_name']) ?></p>
                                </div>
                                <a href="<?= BASEURL ?>user/removeCart/<?= (int) $product['id'] ?>" class="rounded-lg p-2 text-slate-500 hover:bg-red-50 hover:text-red-700" aria-label="Hapus item">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18"/>
                                        <path d="M8 6V4h8v2"/>
                                        <path d="M19 6l-1 14H6L5 6"/>
                                        <path d="M10 11v5"/>
                                        <path d="M14 11v5"/>
                                    </svg>
                                </a>
                            </div>
                            <p class="mt-3 text-sm text-slate-600">Harga satuan Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
                        </div>

                        <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="inline-flex w-fit items-center overflow-hidden rounded-lg border border-slate-300 bg-slate-50">
                                <button disabled class="flex h-10 w-10 items-center justify-center text-slate-400">-</button>
                                <span class="min-w-12 px-4 text-center font-extrabold"><?= (int) $item['qty'] ?></span>
                                <button disabled class="flex h-10 w-10 items-center justify-center text-slate-400">+</button>
                            </div>
                            <p class="text-xl font-extrabold text-emerald-700">Rp<?= number_format($item['subtotal'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <aside class="space-y-5">
            <div class="relative overflow-hidden rounded-xl bg-emerald-700 p-6 text-white shadow-lg">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-100">SmartBank Balance</span>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold">Ready</span>
                    </div>
                    <p class="text-3xl font-extrabold">Rp<?= number_format($data['user']['balance'] ?? 0, 0, ',', '.') ?></p>
                    <div class="mt-3 flex items-center gap-2 text-sm text-emerald-50">
                        <span class="h-2 w-2 rounded-full bg-emerald-200"></span>
                        <span>Saldo siap digunakan</span>
                    </div>
                </div>
                <div class="absolute -bottom-10 -right-10 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                <div class="absolute -left-10 -top-10 h-32 w-32 rounded-full bg-emerald-200/20 blur-2xl"></div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-extrabold text-slate-950">Ringkasan Pesanan</h2>
                <div class="mt-5 space-y-4">
                    <div class="flex justify-between gap-4 text-sm text-slate-600">
                        <span>Total Harga (<?= (int) $itemCount ?> Barang)</span>
                        <b class="text-slate-950">Rp<?= number_format($summary['subtotal'], 0, ',', '.') ?></b>
                    </div>
                    <div class="flex justify-between gap-4 text-sm text-slate-600">
                        <span>Fee marketplace 2%</span>
                        <b class="text-slate-950">Rp<?= number_format($summary['marketplaceFee'], 0, ',', '.') ?></b>
                    </div>
                    <div class="flex justify-between gap-4 text-sm text-slate-600">
                        <span>Fee gateway 0.5%</span>
                        <b class="text-slate-950">Rp<?= number_format($summary['gatewayFee'], 0, ',', '.') ?></b>
                    </div>
                    <div class="flex justify-between gap-4 text-sm text-slate-600">
                        <span>Fee bank 1%</span>
                        <b class="text-slate-950">Rp<?= number_format($summary['bankFee'], 0, ',', '.') ?></b>
                    </div>
                    <div class="flex justify-between gap-4 text-sm text-slate-600">
                        <span>Pajak 2%</span>
                        <b class="text-slate-950">Rp<?= number_format($summary['tax'], 0, ',', '.') ?></b>
                    </div>
                    <div class="flex justify-between gap-4 text-sm text-slate-600">
                        <span>Logistik</span>
                        <b class="text-slate-950">Rp<?= number_format($summary['shipping'], 0, ',', '.') ?></b>
                    </div>
                    <div class="border-t border-slate-200 pt-4">
                        <div class="flex justify-between gap-4 text-lg font-extrabold text-slate-950">
                            <span>Total Tagihan</span>
                            <span>Rp<?= number_format($summary['total'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>

                <form method="post" action="<?= BASEURL ?>user/applyVoucher" class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <?= csrf_field() ?>
                    <p class="text-sm font-extrabold text-slate-950">Gunakan Voucher Belanja</p>
                    <div class="mt-2 flex gap-2">
                        <input name="voucher_code" value="<?= e($summary['voucherCode'] ?? '') ?>" placeholder="Kode Voucher (misal: UMKM10)" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold uppercase">
                        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-800">Pakai</button>
                    </div>
                    <?php if (!empty($summary['discount']) && $summary['discount'] > 0): ?>
                        <p class="mt-2 text-xs font-bold text-emerald-700">Diskon diterapkan: -Rp<?= number_format($summary['discount'], 0, ',', '.') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($summary['voucherError'])): ?>
                        <p class="mt-2 text-xs font-bold text-red-600"><?= e($summary['voucherError']) ?></p>
                    <?php endif; ?>
                </form>

                <?php if ($summary['items']): ?>
                    <a href="<?= BASEURL ?>user/checkout" class="mt-5 block rounded-xl bg-emerald-700 px-5 py-4 text-center text-lg font-extrabold text-white shadow-md hover:bg-emerald-800 active:scale-[0.98]">
                        Checkout Sekarang
                    </a>
                <?php else: ?>
                    <button disabled class="mt-5 block w-full rounded-xl bg-slate-200 px-5 py-4 text-center text-lg font-extrabold text-slate-500">
                        Keranjang Kosong
                    </button>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <p class="text-xs text-slate-500">Metode Pembayaran</p>
                    <p class="mt-1 text-sm font-extrabold text-emerald-700">SmartBank</p>
                </div>
            </div>

            <div class="flex justify-center gap-8 text-center text-slate-400">
                <div>
                    <div class="text-lg">OK</div>
                    <div class="text-[10px] font-bold uppercase">Secure</div>
                </div>
                <div>
                    <div class="text-lg">TR</div>
                    <div class="text-[10px] font-bold uppercase">Tracked</div>
                </div>
                <div>
                    <div class="text-lg">CS</div>
                    <div class="text-[10px] font-bold uppercase">24/7 CS</div>
                </div>
            </div>
        </aside>
    </section>
</div>
