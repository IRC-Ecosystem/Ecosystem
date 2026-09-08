<?php
/** @var array $data */
$orders = $data['orders'] ?? [];
$statusCounts = $data['charts']['order_status'] ?? [];
$totalOrders = count($orders);
$totalRevenue = array_sum(array_map(static fn ($order) => (float) ($order['total'] ?? 0), array_filter($orders, static fn ($order) => ($order['payment_status'] ?? '') === 'paid')));
$paidOrders = count(array_filter($orders, static fn ($order) => ($order['payment_status'] ?? '') === 'paid'));
$pendingPayments = count(array_filter($orders, static fn ($order) => ($order['payment_status'] ?? '') === 'pending'));
$processingOrders = (int) ($statusCounts['processing'] ?? 0);
$shippedOrders = (int) ($statusCounts['shipped'] ?? 0);
$completedOrders = (int) ($statusCounts['completed'] ?? 0);
$cancelledOrders = (int) ($statusCounts['cancelled'] ?? 0);
$avgOrder = $paidOrders > 0 ? $totalRevenue / $paidOrders : 0;
$successRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;

$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
$formatCurrency = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? 'P', 0, 1) . substr($parts[1] ?? '', 0, 1));
};
$paymentClass = static function (string $status): string {
    return match ($status) {
        'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
        default => 'bg-amber-50 text-amber-700 ring-amber-200',
    };
};
$orderClass = static function (string $status): string {
    return match ($status) {
        'completed' => 'bg-emerald-700 text-white ring-emerald-700',
        'shipped' => 'bg-sky-50 text-sky-800 ring-sky-200',
        'cancelled' => 'bg-red-50 text-red-700 ring-red-200',
        default => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
};
?>

<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                <a href="<?= BASEURL ?>admin" class="hover:text-emerald-700">Dashboard</a>
                <span>/</span>
                <span class="text-emerald-700">Monitoring Pesanan</span>
            </div>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">Manajemen Pesanan Platform</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Pantau semua transaksi PasarKita. Admin hanya monitoring, tidak membuat atau mengubah pesanan.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" data-toggle-order-filters class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm hover:border-emerald-700 hover:text-emerald-700">
                Filter Lanjut
            </button>
            <button type="button" class="inline-flex items-center justify-center rounded-md border border-emerald-700 bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm hover:bg-emerald-50">
                Ekspor CSV
            </button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-extrabold text-emerald-700">ORDER</span>
                <span class="text-xs font-bold text-slate-500">Database</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Pesanan</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalOrders) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-sky-50 px-3 py-2 text-sm font-extrabold text-sky-700">PAID</span>
                <span class="text-xs font-bold text-slate-500"><?= $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100) : 0 ?>%</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Pembayaran Sukses</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($paidOrders) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-extrabold text-amber-700">PROSES</span>
                <span class="text-xs font-bold text-slate-500">Seller</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Perlu Diproses</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($processingOrders) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-red-50 px-3 py-2 text-sm font-extrabold text-red-700">AUDIT</span>
                <span class="text-xs font-bold text-slate-500">Cancelled</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Pesanan Batal</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($cancelledOrders) ?></h2>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-chart-url="<?= BASEURL ?>chart/adminOrders">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-950">Status Pemenuhan</h2>
                    <p class="mt-1 text-sm text-slate-600">Grafik mengambil data status pesanan terbaru dari database.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600" data-chart-updated>Memuat</span>
            </div>
            <div class="flex min-h-[220px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50" data-chart-body>
                <div class="flex items-center gap-3 text-sm font-bold text-slate-500">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-emerald-700 border-t-transparent"></span>
                    Mengecek data pesanan...
                </div>
            </div>
        </article>

        <article class="relative overflow-hidden rounded-xl bg-slate-950 p-6 text-white shadow-sm">
            <div class="relative z-10">
                <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-200">Ringkasan Transaksi</p>
                <h2 class="mt-3 text-3xl font-extrabold"><?= $formatCurrency((float) $totalRevenue) ?></h2>
                <p class="mt-3 text-sm text-slate-300">GMV dari pesanan berstatus pembayaran paid.</p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-white/10 p-4">
                        <p class="text-xs font-bold text-slate-300">Avg Order</p>
                        <p class="mt-1 text-xl font-extrabold"><?= $formatCurrency((float) $avgOrder) ?></p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4">
                        <p class="text-xs font-bold text-slate-300">Selesai</p>
                        <p class="mt-1 text-2xl font-extrabold"><?= $successRate ?>%</p>
                    </div>
                </div>
            </div>
            <div class="absolute -bottom-10 -right-10 h-40 w-40 rounded-full bg-emerald-500/15"></div>
        </article>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-1 flex-col gap-3 md:flex-row">
                <label class="relative flex-1">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-extrabold text-slate-400">CARI</span>
                    <input data-order-search class="w-full rounded-lg border border-slate-300 bg-slate-50 py-3 pl-16 pr-4 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100" placeholder="Cari ID pesanan, pelanggan, atau toko..." type="search">
                </label>
                <select data-payment-filter class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    <option value="">Semua Pembayaran</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="failed">Failed</option>
                </select>
                <select data-status-filter class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    <option value="">Semua Status</option>
                    <option value="processing">Processing</option>
                    <option value="shipped">Shipped</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="hidden border-b border-slate-200 bg-slate-50 p-5" data-order-filter-panel>
            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Mode halaman</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Monitoring only. Tidak ada aksi buat pesanan manual.</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Pending Payment</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700"><?= $formatNumber($pendingPayments) ?> pesanan perlu dipantau.</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Sedang Dikirim</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700"><?= $formatNumber($shippedOrders) ?> pesanan di fase shipped.</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Audit Batal</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700"><?= $formatNumber($cancelledOrders) ?> pesanan cancelled.</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1120px] text-left">
                <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">ID Pesanan</th>
                        <th class="px-5 py-4">Tanggal</th>
                        <th class="px-5 py-4">Pelanggan</th>
                        <th class="px-5 py-4">Toko/UMKM</th>
                        <th class="px-5 py-4 text-right">Total</th>
                        <th class="px-5 py-4">Pembayaran</th>
                        <th class="px-5 py-4">Pemenuhan</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($orders as $order): ?>
                        <?php
                        $paymentStatus = $order['payment_status'] ?? 'pending';
                        $orderStatus = $order['order_status'] ?? 'processing';
                        $search = strtolower(($order['order_code'] ?? '') . ' ' . ($order['customer_name'] ?? '') . ' ' . ($order['store_names'] ?? '') . ' ' . $paymentStatus . ' ' . $orderStatus);
                        ?>
                        <tr class="group hover:bg-slate-50" data-order-row data-search="<?= htmlspecialchars($search) ?>" data-payment="<?= htmlspecialchars($paymentStatus) ?>" data-status="<?= htmlspecialchars($orderStatus) ?>">
                            <td class="px-5 py-4">
                                <span class="font-extrabold text-emerald-700"><?= htmlspecialchars($order['order_code'] ?? ('#' . ($order['id'] ?? '-'))) ?></span>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars(date('d M Y, H:i', strtotime($order['created_at'] ?? 'now'))) ?></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-50 text-xs font-extrabold text-sky-800 ring-1 ring-sky-100"><?= htmlspecialchars($initials($order['customer_name'] ?? 'Pelanggan')) ?></span>
                                    <div>
                                        <p class="font-bold text-slate-950"><?= htmlspecialchars($order['customer_name'] ?? 'Pelanggan') ?></p>
                                        <p class="text-xs text-slate-500"><?= htmlspecialchars($order['shipping_address'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-800"><?= htmlspecialchars($order['store_names'] ?? '-') ?></p>
                                <p class="text-xs text-slate-500"><?= (int) ($order['item_count'] ?? 0) ?> item</p>
                            </td>
                            <td class="px-5 py-4 text-right font-extrabold text-slate-950"><?= $formatCurrency((float) ($order['total'] ?? 0)) ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase ring-1 <?= $paymentClass($paymentStatus) ?>"><?= htmlspecialchars($paymentStatus) ?></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase ring-1 <?= $orderClass($orderStatus) ?>"><?= htmlspecialchars($orderStatus) ?></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:border-emerald-700 hover:text-emerald-700">Detail</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada order.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm font-semibold text-slate-500">Menampilkan <span data-visible-count><?= $formatNumber($totalOrders) ?></span> dari <?= $formatNumber($totalOrders) ?> pesanan</p>
            <div class="flex items-center gap-1">
                <button class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400" disabled>&lt;</button>
                <button class="flex h-9 w-9 items-center justify-center rounded-md bg-emerald-700 text-sm font-bold text-white">1</button>
                <button class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700" disabled>&gt;</button>
            </div>
        </div>
    </section>
</section>

<script>
(() => {
    const chart = document.querySelector('[data-chart-url]');
    const chartBody = chart?.querySelector('[data-chart-body]');
    const updated = chart?.querySelector('[data-chart-updated]');
    const colorMap = {emerald: 'bg-emerald-700', red: 'bg-red-600', amber: 'bg-amber-500', sky: 'bg-sky-600'};

    const renderChart = (payload) => {
        const items = Array.isArray(payload.items) ? payload.items : [];
        const max = Math.max(1, ...items.map((item) => Number(item.value) || 0));
        if (!items.length) {
            chartBody.innerHTML = '<div class="text-sm font-semibold text-slate-500">Belum ada data grafik.</div>';
            return;
        }
        chartBody.innerHTML = `
            <div class="w-full space-y-4 p-5">
                ${items.map((item) => {
                    const value = Number(item.value) || 0;
                    const width = Math.max(8, Math.round((value / max) * 100));
                    const color = colorMap[item.color] || colorMap.emerald;
                    return `
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                                <span class="font-bold text-slate-700">${item.label}</span>
                                <span class="font-extrabold text-slate-950">${value.toLocaleString('id-ID')}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-white ring-1 ring-slate-200">
                                <div class="h-full rounded-full ${color} transition-all duration-500" style="width: ${width}%"></div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
        if (updated) updated.textContent = payload.updated_at ? `Update ${payload.updated_at}` : 'Data terbaru';
    };

    const loadChart = async () => {
        if (!chart || !chartBody) return;
        try {
            const response = await fetch(chart.dataset.chartUrl, {headers: {'Accept': 'application/json'}});
            renderChart(await response.json());
        } catch (error) {
            chartBody.innerHTML = '<div class="text-sm font-semibold text-red-600">Gagal memuat data grafik.</div>';
            if (updated) updated.textContent = 'Gagal update';
        }
    };

    const rows = [...document.querySelectorAll('[data-order-row]')];
    const search = document.querySelector('[data-order-search]');
    const paymentFilter = document.querySelector('[data-payment-filter]');
    const statusFilter = document.querySelector('[data-status-filter]');
    const visibleCount = document.querySelector('[data-visible-count]');
    const filterPanel = document.querySelector('[data-order-filter-panel]');
    const filterButton = document.querySelector('[data-toggle-order-filters]');

    const filterRows = () => {
        const query = (search?.value || '').toLowerCase().trim();
        const payment = paymentFilter?.value || '';
        const status = statusFilter?.value || '';
        let shown = 0;
        rows.forEach((row) => {
            const matchSearch = !query || row.dataset.search.includes(query);
            const matchPayment = !payment || row.dataset.payment === payment;
            const matchStatus = !status || row.dataset.status === status;
            const visible = matchSearch && matchPayment && matchStatus;
            row.classList.toggle('hidden', !visible);
            if (visible) shown += 1;
        });
        if (visibleCount) visibleCount.textContent = shown.toLocaleString('id-ID');
    };

    search?.addEventListener('input', filterRows);
    paymentFilter?.addEventListener('change', filterRows);
    statusFilter?.addEventListener('change', filterRows);
    filterButton?.addEventListener('click', () => filterPanel?.classList.toggle('hidden'));

    loadChart();
    window.setInterval(loadChart, 10000);
})();
</script>
