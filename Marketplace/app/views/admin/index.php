<?php
/** @var array $data */
$stats = $data['stats'] ?? [];
$orders = array_slice($data['orders'] ?? [], 0, 5);
$stores = $data['stores'] ?? [];
$users = $data['users'] ?? [];
$charts = $data['charts'] ?? [];

$totalOrders = (int) ($stats['orders'] ?? 0);
$revenue = (float) ($stats['revenue'] ?? 0);
$totalProducts = (int) ($stats['products'] ?? 0);
$totalStores = (int) ($stats['stores'] ?? count($stores));
$lowStock = (int) ($stats['low_stock'] ?? 0);
$totalUsers = count($users);
$activeStores = (int) ($charts['store_status']['active'] ?? 0);
$completedOrders = (int) ($charts['order_status']['completed'] ?? 0);
$cancelledOrders = (int) ($charts['order_status']['cancelled'] ?? 0);
$successRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;
$refundRate = $totalOrders > 0 ? number_format(($cancelledOrders / $totalOrders) * 100, 1, ',', '.') : '0,0';
$avgOrder = $totalOrders > 0 ? $revenue / $totalOrders : 0;
$sellerCount = (int) ($charts['roles']['seller'] ?? 0);
$pendingStores = max(0, $totalStores - $activeStores);

$statusClasses = [
    'processing' => 'bg-sky-50 text-sky-700 ring-sky-200',
    'shipped' => 'bg-amber-50 text-amber-700 ring-amber-200',
    'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
];

$formatCurrency = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');
$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
?>

<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-700">Area Administrator</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">Ringkasan Performa</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Pantau transaksi, toko, produk, stok, dan aktivitas platform dari satu dashboard.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= BASEURL ?>admin/orders" class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm hover:border-emerald-600 hover:text-emerald-700">
                Export Laporan
            </a>
            <a href="<?= BASEURL ?>admin/stores" class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">
                Verifikasi Toko
            </a>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <article class="relative overflow-hidden rounded-xl bg-emerald-700 p-5 text-white shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-100">Total Pendapatan</p>
            <h2 class="mt-3 text-3xl font-extrabold"><?= $formatCurrency($revenue) ?></h2>
            <p class="mt-4 text-sm font-semibold text-emerald-100">Rata-rata order <?= $formatCurrency($avgOrder) ?></p>
            <div class="absolute -bottom-8 -right-6 h-28 w-28 rounded-full bg-white/10"></div>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Pengguna</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalUsers) ?></h2>
            <p class="mt-4 text-sm font-semibold text-emerald-700"><?= $formatNumber($sellerCount) ?> seller terdaftar</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Toko</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalStores) ?></h2>
            <p class="mt-4 text-sm font-semibold text-amber-700"><?= $formatNumber($activeStores) ?> aktif</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Produk</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalProducts) ?></h2>
            <p class="mt-4 text-sm font-semibold text-slate-600"><?= $formatNumber($lowStock) ?> stok menipis</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Pesanan</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalOrders) ?></h2>
            <p class="mt-4 text-sm font-semibold text-emerald-700"><?= $successRate ?>% selesai</p>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 p-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-950">Platform Revenue Summary</h2>
                    <p class="mt-1 text-sm text-slate-600">GMV dan kesehatan transaksi berdasarkan data order saat ini.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-extrabold text-emerald-700">System Online</span>
            </div>
            <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_220px]">
                <div class="relative min-h-[330px] p-5">
                    <div class="absolute left-5 right-5 top-5 flex items-start justify-between">
                        <div class="rounded-lg bg-slate-950 px-3 py-2 text-xs font-bold text-white shadow-lg">
                            GMV: <?= $formatCurrency($revenue) ?>
                        </div>
                        <div class="text-right text-xs font-semibold text-slate-500">
                            Update <?= date('H:i') ?> WIB
                        </div>
                    </div>
                    <div class="h-[300px] rounded-lg bg-gradient-to-b from-emerald-50 to-white pt-12">
                        <svg class="h-full w-full" viewBox="0 0 820 300" role="img" aria-label="Grafik pendapatan platform">
                            <path d="M20 250 Q120 190 210 215 T410 125 T610 160 T800 58" fill="none" stroke="#047857" stroke-linecap="round" stroke-width="5"></path>
                            <path d="M20 250 Q120 190 210 215 T410 125 T610 160 T800 58 L800 300 L20 300 Z" fill="#047857" opacity="0.08"></path>
                            <g stroke="#cbd5e1" stroke-width="1">
                                <line x1="20" x2="800" y1="80" y2="80"></line>
                                <line x1="20" x2="800" y1="150" y2="150"></line>
                                <line x1="20" x2="800" y1="220" y2="220"></line>
                            </g>
                            <circle cx="410" cy="125" fill="#047857" r="7"></circle>
                            <circle cx="800" cy="58" fill="#047857" r="7"></circle>
                        </svg>
                    </div>
                </div>
                <div class="grid border-t border-slate-200 bg-slate-50 lg:border-l lg:border-t-0">
                    <div class="border-b border-slate-200 p-5">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Rata-rata Order</p>
                        <p class="mt-2 text-xl font-extrabold text-emerald-700"><?= $formatCurrency($avgOrder) ?></p>
                    </div>
                    <div class="border-b border-slate-200 p-5">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Order Selesai</p>
                        <p class="mt-2 text-xl font-extrabold text-emerald-700"><?= $formatNumber($completedOrders) ?></p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Refund Rate</p>
                        <p class="mt-2 text-xl font-extrabold text-amber-700"><?= $refundRate ?>%</p>
                    </div>
                </div>
            </div>
        </article>

        <aside class="space-y-6">
            <article class="overflow-hidden rounded-xl border border-red-100 bg-red-50 shadow-sm">
                <div class="border-b border-red-100 bg-red-100/70 p-5">
                    <h2 class="text-lg font-extrabold text-red-800">Peringatan Kritis</h2>
                </div>
                <div class="space-y-3 p-5">
                    <a href="<?= BASEURL ?>admin/sellerFeatures" class="block rounded-lg border border-red-100 bg-white/80 p-4 hover:border-red-300">
                        <p class="font-extrabold text-slate-950">Stok Menipis (<?= $formatNumber($lowStock) ?> Item)</p>
                        <p class="mt-1 text-sm text-slate-600">Produk perlu dipantau agar seller segera restock.</p>
                    </a>
                    <a href="<?= BASEURL ?>admin/stores" class="block rounded-lg border border-amber-100 bg-white/80 p-4 hover:border-amber-300">
                        <p class="font-extrabold text-slate-950">Verifikasi Pending (<?= $formatNumber($pendingStores) ?> Toko)</p>
                        <p class="mt-1 text-sm text-slate-600">Cek kelengkapan toko yang belum aktif.</p>
                    </a>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-lg font-extrabold text-slate-950">Quick Stats</h2>
                </div>
                <div class="grid grid-cols-2">
                    <div class="border-b border-r border-slate-100 p-5">
                        <p class="text-xs font-bold text-slate-500">Tiket Support</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950"><?= max(0, $pendingStores + $lowStock) ?></p>
                        <span class="text-xs font-bold text-red-600">Perlu cek</span>
                    </div>
                    <div class="border-b border-slate-100 p-5">
                        <p class="text-xs font-bold text-slate-500">Toko Aktif</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950"><?= $formatNumber($activeStores) ?></p>
                        <span class="text-xs font-bold text-amber-600">Dari status toko</span>
                    </div>
                    <div class="border-r border-slate-100 p-5">
                        <p class="text-xs font-bold text-slate-500">Refund Rate</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950"><?= $refundRate ?>%</p>
                        <span class="text-xs font-bold text-emerald-700">Dari status order</span>
                    </div>
                    <div class="p-5">
                        <p class="text-xs font-bold text-slate-500">SLA Pengiriman</p>
                        <p class="mt-2 text-2xl font-extrabold text-slate-950"><?= $successRate ?>%</p>
                        <span class="text-xs font-bold text-slate-600">Order selesai</span>
                    </div>
                </div>
            </article>
        </aside>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-chart-url="<?= BASEURL ?>chart/adminSummary">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-950">Kesehatan Platform</h2>
                    <p class="mt-1 text-sm text-slate-600">Grafik otomatis mengambil data terbaru dari database.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600" data-chart-updated>Memuat</span>
            </div>
            <div class="flex min-h-[220px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50" data-chart-body>
                <div class="flex items-center gap-3 text-sm font-bold text-slate-500">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-emerald-700 border-t-transparent"></span>
                    Mengecek data grafik...
                </div>
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-extrabold text-slate-950">Distribusi Role</h2>
            <div class="mt-5 space-y-4">
                <?php foreach (($charts['roles'] ?? []) as $role => $count): ?>
                    <?php $percent = $totalUsers > 0 ? min(100, round(((int) $count / $totalUsers) * 100)) : 0; ?>
                    <div>
                        <div class="mb-2 flex items-center justify-between text-sm">
                            <span class="font-bold capitalize text-slate-700"><?= htmlspecialchars($role) ?></span>
                            <span class="font-extrabold text-slate-950"><?= (int) $count ?></span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-emerald-700" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($charts['roles'])): ?>
                    <p class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">Belum ada data role pengguna.</p>
                <?php endif; ?>
            </div>
        </article>
    </div>

    <section>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-extrabold text-slate-950">Pesanan Terbaru</h2>
                <p class="mt-1 text-sm text-slate-600">Order terbaru dari transaksi pelanggan PasarKita.</p>
            </div>
            <a href="<?= BASEURL ?>admin/orders" class="text-sm font-extrabold text-emerald-700 hover:text-emerald-900">Lihat Semua</a>
        </div>
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] text-left">
                    <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-4">ID Pesanan</th>
                            <th class="px-5 py-4">Pelanggan</th>
                            <th class="px-5 py-4">Total</th>
                            <th class="px-5 py-4">Pembayaran</th>
                            <th class="px-5 py-4">Status</th>
                            <th class="px-5 py-4">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($orders as $order): ?>
                            <?php $status = $order['order_status'] ?? 'processing'; ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4 font-extrabold text-slate-950"><?= htmlspecialchars($order['order_code'] ?? ('#' . ($order['id'] ?? '-'))) ?></td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-emerald-50 text-sm font-extrabold text-emerald-700"><?= strtoupper(substr($order['customer_name'] ?? 'P', 0, 1)) ?></span>
                                        <div>
                                            <p class="font-bold text-slate-800"><?= htmlspecialchars($order['customer_name'] ?? 'Pelanggan') ?></p>
                                            <p class="text-xs text-slate-500">PasarKita order</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-extrabold text-slate-950"><?= $formatCurrency((float) ($order['total'] ?? 0)) ?></td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold uppercase text-emerald-700"><?= htmlspecialchars($order['payment_status'] ?? 'paid') ?></span>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold uppercase ring-1 <?= $statusClasses[$status] ?? 'bg-slate-50 text-slate-700 ring-slate-200' ?>"><?= htmlspecialchars($status) ?></span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars(date('d M Y H:i', strtotime($order['created_at'] ?? 'now'))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-slate-500">Belum ada pesanan yang masuk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</section>

<script>
(() => {
    const chart = document.querySelector('[data-chart-url]');
    if (!chart) return;

    const body = chart.querySelector('[data-chart-body]');
    const updated = chart.querySelector('[data-chart-updated]');
    const colors = {
        emerald: 'bg-emerald-700',
        sky: 'bg-sky-600',
        amber: 'bg-amber-500',
        red: 'bg-red-600'
    };

    const render = (payload) => {
        const items = Array.isArray(payload.items) ? payload.items : [];
        const max = Math.max(1, ...items.map((item) => Number(item.value) || 0));
        if (!items.length) {
            body.innerHTML = '<div class="text-sm font-semibold text-slate-500">Belum ada data grafik.</div>';
            return;
        }

        body.innerHTML = `
            <div class="w-full space-y-4 p-5">
                ${items.map((item) => {
                    const value = Number(item.value) || 0;
                    const width = Math.max(8, Math.round((value / max) * 100));
                    const color = colors[item.color] || colors.emerald;
                    return `
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                <span class="font-bold text-slate-700">${item.label}</span>
                                <span class="font-extrabold text-slate-950">${item.formatted || value.toLocaleString('id-ID')}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                                <div class="h-full rounded-full ${color} transition-all duration-500" style="width: ${width}%"></div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
        if (updated) {
            updated.textContent = payload.updated_at ? `Update ${payload.updated_at}` : 'Data terbaru';
        }
    };

    const load = async () => {
        try {
            const response = await fetch(chart.dataset.chartUrl, {headers: {'Accept': 'application/json'}});
            render(await response.json());
        } catch (error) {
            body.innerHTML = '<div class="text-sm font-semibold text-red-600">Gagal memuat data grafik.</div>';
            if (updated) updated.textContent = 'Gagal update';
        }
    };

    load();
    window.setInterval(load, 10000);
})();
</script>
