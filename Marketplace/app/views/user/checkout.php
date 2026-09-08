<?php
/** @var array $data */
$summary = $data['summary'];
$user = $data['user'] ?? current_user();
$itemCount = array_sum(array_map(fn ($item) => (int) $item['qty'], $summary['items']));
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .checkout-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .checkout-panel {
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .checkout-panel:hover {
        box-shadow: 0 14px 30px -22px rgba(0, 104, 95, .35);
    }
</style>

<div class="checkout-page space-y-6">
    <section>
        <h1 class="text-3xl font-extrabold text-slate-950">Checkout Keranjang</h1>
        <p class="mt-2 text-sm text-slate-600">Konfirmasi alamat, pengiriman, dan pembayaran sebelum order dibuat.</p>
    </section>

    <?php if (!$summary['items']): ?>
        <section class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-2xl font-extrabold text-emerald-700">0</div>
            <h2 class="mt-4 text-xl font-extrabold text-slate-950">Belum ada produk untuk checkout</h2>
            <p class="mt-2 text-sm text-slate-500">Keranjang kosong, jadi belum ada payment request yang bisa dibuat.</p>
            <a href="<?= BASEURL ?>user/catalog" class="mt-5 inline-flex rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-800">Pilih Produk</a>
        </section>
    <?php else: ?>
        <form method="post" class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_400px] lg:items-start">
            <?= csrf_field() ?>
            <div class="space-y-5">
                <section class="checkout-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <h2 class="flex items-center gap-2 text-xl font-extrabold text-slate-950">
                            <span class="text-emerald-700">Alamat</span> Pengiriman
                        </h2>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-emerald-800">Utama</span>
                    </div>
                    <div class="rounded-xl border-2 border-emerald-700 bg-emerald-50/60 p-4">
                        <p class="font-extrabold text-slate-950"><?= htmlspecialchars($user['name'] ?? current_user()['name']) ?> (Rumah)</p>
                        <p class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($user['phone'] ?? '-') ?></p>
                        <label class="mt-4 block">
                            <span class="mb-2 block text-sm font-bold text-slate-700">Alamat lengkap</span>
                            <textarea name="shipping_address" required class="min-h-28 w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm focus:border-emerald-700 focus:ring-emerald-100"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </label>
                    </div>
                </section>

                <section class="checkout-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-xl font-extrabold text-slate-950">Rincian Pesanan</h2>
                    <div class="space-y-4">
                        <?php foreach ($summary['items'] as $item): ?>
                            <?php $product = $item['product']; ?>
                            <article class="border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                                <div class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    <span class="text-emerald-700">Toko</span>
                                    <span><?= htmlspecialchars($product['store_name']) ?></span>
                                </div>
                                <div class="flex gap-4">
                                    <div class="h-24 w-24 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img class="h-full w-full object-cover" src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php else: ?>
                                            <div class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-500">PasarKita</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex min-w-0 flex-1 flex-col justify-between">
                                        <div>
                                            <h3 class="line-clamp-1 font-extrabold text-slate-950"><?= htmlspecialchars($product['name']) ?></h3>
                                            <p class="mt-1 text-xs text-slate-500"><?= htmlspecialchars($product['category']) ?></p>
                                        </div>
                                        <div class="flex items-end justify-between gap-3">
                                            <span class="text-sm text-slate-500"><?= (int) $item['qty'] ?> x Rp<?= number_format($product['price'], 0, ',', '.') ?></span>
                                            <span class="font-extrabold text-emerald-700">Rp<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="checkout-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-xl font-extrabold text-slate-950">Pilih Pengiriman</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="relative flex cursor-pointer flex-col rounded-xl border-2 border-emerald-700 bg-emerald-50 p-4">
                            <input checked class="absolute right-4 top-4 text-emerald-700 focus:ring-emerald-700" name="shipping_method" type="radio" value="local">
                            <span class="text-sm font-extrabold text-emerald-700">LogistiKita Standard</span>
                            <span class="mt-2 font-extrabold text-slate-950">Rp<?= number_format($summary['shipping'], 0, ',', '.') ?></span>
                            <span class="mt-1 text-xs text-slate-500">Estimasi tiba 2 - 5 hari</span>
                        </label>
                        <label class="relative flex cursor-pointer flex-col rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                            <input class="absolute right-4 top-4 text-emerald-700 focus:ring-emerald-700" name="shipping_method" type="radio" value="pickup">
                            <span class="text-sm font-extrabold text-slate-950">Ambil di Toko</span>
                            <span class="mt-2 font-extrabold text-slate-950">Rp0</span>
                            <span class="mt-1 text-xs text-slate-500">Simulasi opsi pickup</span>
                        </label>
                    </div>
                </section>

                <section class="checkout-panel rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-4 text-xl font-extrabold text-slate-950">Metode Pembayaran</h2>
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center justify-between rounded-xl border-2 border-emerald-700 bg-emerald-50 p-4">
                            <div class="flex items-center gap-3">
                                <input checked class="text-emerald-700 focus:ring-emerald-700" name="payment_method" type="radio" value="smartbank">
                                <div>
                                     <p class="font-extrabold text-slate-950">SmartBank</p>
                                     <p class="mt-1 text-xs text-slate-500"><?= !empty($data['smartBank']['linked']) ? 'Wallet terhubung. PIN diminta setelah order dibuat.' : 'Hubungkan wallet terlebih dahulu dari Profil.' ?></p>
                                </div>
                            </div>
                            <span class="text-xs font-extrabold text-emerald-700">INSTANT</span>
                        </label>
                        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 p-4 hover:bg-slate-50">
                            <div class="flex items-center gap-3">
                                <input disabled class="text-emerald-700 focus:ring-emerald-700" name="payment_method" type="radio" value="va">
                                <div>
                                    <p class="font-extrabold text-slate-950">Virtual Account</p>
                                    <p class="mt-1 text-xs text-slate-500">Metode tidak tersedia.</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-400">Soon</span>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="lg:sticky lg:top-28">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-lg">
                    <h2 class="text-xl font-extrabold text-slate-950">Ringkasan Belanja</h2>
                    <div class="mt-5 space-y-4">
                        <div class="flex justify-between gap-4 text-sm text-slate-600">
                            <span>Total Harga (<?= (int) $itemCount ?> Barang)</span>
                            <b class="text-slate-950">Rp<?= number_format($summary['subtotal'], 0, ',', '.') ?></b>
                        </div>
                        <div class="flex justify-between gap-4 text-sm text-slate-600">
                            <span>Ongkos Kirim</span>
                            <b class="text-slate-950">Rp<?= number_format($summary['shipping'], 0, ',', '.') ?></b>
                        </div>
                        <div class="flex justify-between gap-4 text-sm text-slate-600">
                            <span>Fee Marketplace</span>
                            <b class="text-slate-950">Rp<?= number_format($summary['marketplaceFee'], 0, ',', '.') ?></b>
                        </div>
                        <div class="flex justify-between gap-4 text-sm text-slate-600">
                            <span>Gateway + Bank</span>
                            <b class="text-slate-950">Rp<?= number_format($summary['gatewayFee'] + $summary['bankFee'], 0, ',', '.') ?></b>
                        </div>
                        <div class="flex justify-between gap-4 text-sm text-slate-600">
                            <span>Pajak</span>
                            <b class="text-slate-950">Rp<?= number_format($summary['tax'], 0, ',', '.') ?></b>
                        </div>
                        <div class="border-t border-slate-200 pt-4">
                            <div class="flex justify-between gap-4 text-lg font-extrabold text-emerald-700">
                                <span>Total Tagihan</span>
                                <span>Rp<?= number_format($summary['total'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <label class="relative block">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase text-slate-400">Promo</span>
                            <input class="w-full rounded-lg border border-slate-300 bg-slate-50 py-3 pl-20 pr-4 text-sm focus:border-emerald-700 focus:ring-emerald-100" placeholder="Masukkan kode promo" type="text">
                        </label>
                    </div>

                    <button class="mt-5 flex w-full items-center justify-center rounded-xl bg-emerald-700 px-5 py-4 text-lg font-extrabold text-white shadow-md hover:bg-emerald-800 active:scale-[0.98]">
                        Buat Order SmartBank
                    </button>
                    <p class="mt-4 text-center text-[11px] leading-5 text-slate-500">
                        Dengan menekan tombol di atas, kamu menyetujui syarat dan kebijakan PasarKita.
                    </p>

                    <div class="mt-6 flex justify-center gap-8 border-t border-slate-200 pt-5 text-center text-slate-400">
                        <div>
                            <div class="font-extrabold">OK</div>
                            <div class="text-[10px] font-bold uppercase">Secure</div>
                        </div>
                        <div>
                            <div class="font-extrabold">LOCK</div>
                            <div class="text-[10px] font-bold uppercase">Safe</div>
                        </div>
                        <div>
                            <div class="font-extrabold">PAY</div>
                            <div class="text-[10px] font-bold uppercase">SmartBank</div>
                        </div>
                    </div>
                </div>
            </aside>
        </form>
    <?php endif; ?>
</div>

<script>
    document.querySelectorAll('input[name="shipping_method"], input[name="payment_method"]').forEach(function (input) {
        input.addEventListener('change', function () {
            document.querySelectorAll('input[name="' + input.name + '"]').forEach(function (option) {
                const label = option.closest('label');
                if (!label) {
                    return;
                }
                label.classList.toggle('border-emerald-700', option.checked);
                label.classList.toggle('bg-emerald-50', option.checked);
                label.classList.toggle('border-slate-200', !option.checked);
            });
        });
    });
</script>
