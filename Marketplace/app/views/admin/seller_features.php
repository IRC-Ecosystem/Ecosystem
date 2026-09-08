<?php
/** @var array $data */
$stores = $data['stores'] ?? [];
$users = $data['users'] ?? [];
$orders = $data['orders'] ?? [];
$stats = $data['stats'] ?? [];
$roles = $data['charts']['roles'] ?? [];
$storeMetrics = $data['store_metrics'] ?? [];
$lowStockProducts = $data['low_stock_products'] ?? [];

$sellerCount = (int) ($roles['seller'] ?? 0);
$activeStores = count(array_filter($stores, static fn ($store) => ($store['status'] ?? '') === 'active'));
$totalProducts = (int) ($stats['products'] ?? 0);
$lowStockCount = (int) ($stats['low_stock'] ?? count($lowStockProducts));
$processingOrders = count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'processing'));
$shippedOrders = count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'shipped'));
$completedOrders = count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'completed'));
$paidRevenue = array_sum(array_map(static fn ($order) => (float) ($order['total'] ?? 0), array_filter($orders, static fn ($order) => ($order['payment_status'] ?? '') === 'paid')));
$sellerBalances = array_sum(array_map(static fn ($user) => ($user['role'] ?? '') === 'seller' ? (float) ($user['balance'] ?? 0) : 0, $users));
$storesWithProducts = count(array_filter($storeMetrics, static fn ($metric) => (int) ($metric['products'] ?? 0) > 0));
$storesWithOrders = count(array_filter($storeMetrics, static fn ($metric) => (int) ($metric['orders'] ?? 0) > 0));
$productCoverage = $activeStores > 0 ? min(100, round(($storesWithProducts / $activeStores) * 100)) : 0;
$orderCoverage = $activeStores > 0 ? min(100, round(($storesWithOrders / $activeStores) * 100)) : 0;
$chatCoverage = $activeStores > 0 ? min(100, 100 - max(0, $lowStockCount * 5)) : 0;
$restockPressure = $totalProducts > 0 ? min(100, round(($lowStockCount / $totalProducts) * 100)) : 0;

$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
$formatCurrency = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');
?>

<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                <a href="<?= BASEURL ?>admin" class="hover:text-emerald-700">Dashboard</a>
                <span>/</span>
                <span class="text-emerald-700">Fitur Penjual</span>
            </div>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">Fitur Penjual</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Pantau performa fitur seller: produk, pesanan, promosi, chat, keuangan, dan restock. Admin hanya monitoring.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" class="inline-flex items-center justify-center rounded-md border border-emerald-700 bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm hover:bg-emerald-50">
                Unduh Laporan
            </button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-12">
        <article class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-4">
            <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-emerald-50"></div>
            <div class="relative">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-xl font-extrabold text-slate-950">Kesehatan Finansial</h2>
                    <span class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-extrabold text-emerald-700">SmartBank</span>
                </div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total saldo seller</p>
                <p class="mt-2 text-3xl font-extrabold text-emerald-700"><?= $formatCurrency((float) $sellerBalances) ?></p>
                <p class="mt-2 text-sm font-semibold text-slate-500">GMV paid platform <?= $formatCurrency((float) $paidRevenue) ?></p>
                <div class="mt-6 grid grid-cols-2 gap-4 border-t border-slate-100 pt-5">
                    <div>
                        <p class="text-xs font-bold text-slate-500">Seller aktif</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-950"><?= $formatNumber($sellerCount) ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-500">Toko aktif</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-950"><?= $formatNumber($activeStores) ?></p>
                    </div>
                </div>
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-4">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-slate-950">Adopsi Fitur Toko</h2>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700">Live</span>
            </div>
            <div class="space-y-5">
                <?php foreach ([['Produk & Katalog', $productCoverage, 'bg-emerald-700'], ['Pesanan Seller', $orderCoverage, 'bg-sky-600'], ['Restock Alert', $restockPressure, 'bg-amber-500']] as $feature): ?>
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-bold text-slate-800"><?= htmlspecialchars($feature[0]) ?></span>
                            <span class="font-extrabold text-slate-500"><?= (int) $feature[1] ?>%</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full <?= $feature[2] ?>" style="width: <?= max(5, (int) $feature[1]) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-4">
            <h2 class="text-xl font-extrabold text-slate-950">Analitik Chat</h2>
            <div class="mt-6 flex flex-col items-center">
                <div class="relative h-32 w-32">
                    <svg class="h-full w-full -rotate-90" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e2e8f0" stroke-width="3"></path>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#047857" stroke-dasharray="<?= $chatCoverage ?>, 100" stroke-width="3"></path>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-2xl font-extrabold text-slate-950"><?= $chatCoverage ?>%</span>
                        <span class="text-xs font-bold text-slate-500">Estimasi sehat</span>
                    </div>
                </div>
                <p class="mt-4 text-center text-sm text-slate-600">Indikator dihitung dari tekanan stok dan toko aktif karena tabel chat belum tersedia di database.</p>
                <div class="mt-5 grid w-full grid-cols-2 border-t border-slate-100 pt-4">
                    <div class="border-r border-slate-100 text-center">
                        <p class="text-xs font-bold text-slate-500">Toko Aktif</p>
                        <p class="mt-1 text-xl font-extrabold text-slate-950"><?= $formatNumber($activeStores) ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-xs font-bold text-slate-500">Stok Kritis</p>
                        <p class="mt-1 text-xl font-extrabold text-red-700"><?= $formatNumber($lowStockCount) ?></p>
                    </div>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm xl:col-span-8">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-950">Peringatan Stok Global</h2>
                    <p class="mt-1 text-sm text-slate-600">Produk seller dengan stok kritis dari database produk.</p>
                </div>
                <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-extrabold text-red-700"><?= $formatNumber($lowStockCount) ?> item kritis</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">Nama Produk</th>
                            <th class="px-5 py-4">Penjual</th>
                            <th class="px-5 py-4 text-right">Stok Sisa</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($lowStockProducts as $product): ?>
                            <?php $stock = (int) ($product['stock'] ?? 0); ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-bold text-slate-950"><?= htmlspecialchars($product['name'] ?? '-') ?></td>
                                <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars($product['store_name'] ?? '-') ?></td>
                                <td class="px-5 py-4 text-right font-extrabold <?= $stock <= 3 ? 'text-red-700' : 'text-amber-700' ?>"><?= $formatNumber($stock) ?></td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-extrabold <?= $stock <= 3 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700' ?>"><?= $stock <= 3 ? 'KRITIS' : 'RENDAH' ?></span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <span class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-500">Monitoring</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($lowStockProducts)): ?>
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Tidak ada produk stok kritis.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-4">
            <h2 class="text-xl font-extrabold text-slate-950">Peringkat Penggunaan Fitur</h2>
            <div class="mt-6 space-y-5">
                <?php
                $rankings = [
                    ['Produk & Katalog', $productCoverage, '+data'],
                    ['Pesanan Seller', $orderCoverage, '+data'],
                    ['Restock SupplierHub', $restockPressure, 'audit'],
                    ['Chat Pembeli', $chatCoverage, 'estimasi'],
                ];
                ?>
                <?php foreach ($rankings as $index => $rank): ?>
                    <div class="flex items-center gap-4">
                        <span class="w-9 text-2xl font-extrabold text-emerald-100"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        <div class="min-w-0 flex-1">
                            <div class="mb-1 flex items-center justify-between gap-3">
                                <p class="truncate font-bold text-slate-950"><?= htmlspecialchars($rank[0]) ?></p>
                                <p class="text-xs font-extrabold text-emerald-700"><?= htmlspecialchars($rank[2]) ?></p>
                            </div>
                            <p class="text-sm font-semibold text-slate-500"><?= (int) $rank[1] ?>% indikator aktif</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                Halaman ini hanya memantau fitur. Perubahan produk, promosi, chat, dan restock tetap dilakukan oleh seller.
            </div>
        </article>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <article class="rounded-xl bg-emerald-700 p-5 text-white shadow-sm">
            <p class="text-3xl font-extrabold"><?= $formatNumber($totalProducts) ?></p>
            <p class="mt-1 text-sm font-bold text-emerald-100">Produk Terdaftar</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-3xl font-extrabold text-slate-950"><?= $formatNumber($shippedOrders) ?></p>
            <p class="mt-1 text-sm font-bold text-slate-500">Pengiriman Berjalan</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-3xl font-extrabold text-slate-950"><?= $formatNumber($completedOrders) ?></p>
            <p class="mt-1 text-sm font-bold text-slate-500">Pesanan Selesai</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-3xl font-extrabold text-slate-950"><?= $formatNumber($processingOrders) ?></p>
            <p class="mt-1 text-sm font-bold text-slate-500">Perlu Diproses Seller</p>
        </article>
    </div>
</section>
