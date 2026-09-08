<?php
/** @var array $data */
$stores = $data['stores'] ?? [];
$metrics = $data['store_metrics'] ?? [];
$stats = $data['stats'] ?? [];
$storeStatus = $data['charts']['store_status'] ?? [];
$totalStores = count($stores);
$activeStores = (int) ($storeStatus['active'] ?? 0);
$inactiveStores = max(0, $totalStores - $activeStores);
$totalProducts = (int) ($stats['products'] ?? 0);
$totalRevenue = array_sum(array_map(static fn ($item) => (float) ($item['revenue'] ?? 0), $metrics));
$starSellers = count(array_filter($metrics, static fn ($item) => (float) ($item['revenue'] ?? 0) >= 100000 || (int) ($item['orders'] ?? 0) >= 3));

$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
$formatCurrency = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    return strtoupper(substr($parts[0] ?? 'T', 0, 1) . substr($parts[1] ?? '', 0, 1));
};
$statusClass = static function (string $status): string {
    return $status === 'active'
        ? 'bg-emerald-50 text-emerald-800 ring-emerald-200'
        : 'bg-red-50 text-red-700 ring-red-200';
};
$badgeFor = static function (array $metric): array {
    $revenue = (float) ($metric['revenue'] ?? 0);
    $orders = (int) ($metric['orders'] ?? 0);
    if ($revenue >= 100000 || $orders >= 3) {
        return ['Star Seller', 'bg-amber-50 text-amber-700 ring-amber-200'];
    }
    if ($revenue > 0 || $orders > 0) {
        return ['Top UMKM', 'bg-emerald-50 text-emerald-800 ring-emerald-200'];
    }
    return ['Reguler', 'bg-slate-100 text-slate-600 ring-slate-200'];
};
?>

<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                <a href="<?= BASEURL ?>admin" class="hover:text-emerald-700">Dashboard</a>
                <span>/</span>
                <span class="text-emerald-700">Monitoring Toko</span>
            </div>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">Monitoring Toko UMKM</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Pantau performa dan kepatuhan seluruh mitra PasarKita. Admin hanya memonitor, bukan menambah toko.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" data-toggle-store-filters class="inline-flex items-center justify-center rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm hover:border-emerald-700 hover:text-emerald-700">
                Filter Lanjut
            </button>
            <button type="button" class="inline-flex items-center justify-center rounded-md border border-emerald-700 bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm hover:bg-emerald-50">
                Ekspor Data
            </button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-extrabold text-emerald-700">TOKO</span>
                <span class="text-xs font-bold text-slate-500">Database</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Toko</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalStores) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-extrabold text-amber-700">STAR</span>
                <span class="text-xs font-bold text-slate-500">Performa</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Star Sellers</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($starSellers) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-sky-50 px-3 py-2 text-sm font-extrabold text-sky-700">PRODUK</span>
                <span class="text-xs font-bold text-slate-500">UMKM</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Produk</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalProducts) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-red-50 px-3 py-2 text-sm font-extrabold text-red-700">REVIEW</span>
                <span class="text-xs font-bold text-slate-500">Status</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Perlu Ditinjau</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($inactiveStores) ?></h2>
        </article>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" data-chart-url="<?= BASEURL ?>chart/adminStores">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-950">Status Toko</h2>
                    <p class="mt-1 text-sm text-slate-600">Grafik status toko mengambil data terbaru dari database.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600" data-chart-updated>Memuat</span>
            </div>
            <div class="flex min-h-[210px] items-center justify-center rounded-lg border border-slate-200 bg-slate-50" data-chart-body>
                <div class="flex items-center gap-3 text-sm font-bold text-slate-500">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-emerald-700 border-t-transparent"></span>
                    Mengecek data toko...
                </div>
            </div>
        </article>

        <article class="relative overflow-hidden rounded-xl bg-slate-950 p-6 text-white shadow-sm">
            <div class="relative z-10">
                <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-200">Ringkasan Omzet Toko</p>
                <h2 class="mt-3 text-3xl font-extrabold"><?= $formatCurrency((float) $totalRevenue) ?></h2>
                <p class="mt-3 text-sm text-slate-300">Total omzet dihitung dari item order berstatus pembayaran paid.</p>
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-white/10 p-4">
                        <p class="text-xs font-bold text-slate-300">Aktif</p>
                        <p class="mt-1 text-2xl font-extrabold"><?= $formatNumber($activeStores) ?></p>
                    </div>
                    <div class="rounded-lg bg-white/10 p-4">
                        <p class="text-xs font-bold text-slate-300">Review</p>
                        <p class="mt-1 text-2xl font-extrabold"><?= $formatNumber($inactiveStores) ?></p>
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
                    <input data-store-search class="w-full rounded-lg border border-slate-300 bg-slate-50 py-3 pl-16 pr-4 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100" placeholder="Cari nama toko atau pemilik..." type="search">
                </label>
                <select data-status-filter class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
                <select data-sort-store class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    <option value="newest">Terbaru Bergabung</option>
                    <option value="revenue">Omzet Tertinggi</option>
                    <option value="products">Produk Terbanyak</option>
                    <option value="orders">Order Terbanyak</option>
                </select>
            </div>
        </div>

        <div class="hidden border-b border-slate-200 bg-slate-50 p-5" data-store-filter-panel>
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Mode halaman</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Monitoring only. Tidak ada aksi tambah toko.</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Ambang star seller</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Omzet minimal Rp100.000 atau 3 order.</p>
                </div>
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Sumber omzet</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Order item dengan payment_status paid.</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left">
                <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Toko & Pemilik</th>
                        <th class="px-5 py-4 text-right">Produk</th>
                        <th class="px-5 py-4 text-right">Order</th>
                        <th class="px-5 py-4 text-right">Total Penjualan</th>
                        <th class="px-5 py-4">Badge Performa</th>
                        <th class="px-5 py-4">Status</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100" data-store-table-body>
                    <?php foreach ($stores as $store): ?>
                        <?php
                        $storeId = (int) ($store['id'] ?? 0);
                        $metric = $metrics[$storeId] ?? ['products' => 0, 'orders' => 0, 'revenue' => 0];
                        $badge = $badgeFor($metric);
                        $status = $store['status'] ?? 'inactive';
                        $search = strtolower(($store['name'] ?? '') . ' ' . ($store['owner_name'] ?? '') . ' ' . $status);
                        ?>
                        <tr class="group hover:bg-slate-50" data-store-row data-search="<?= htmlspecialchars($search) ?>" data-status="<?= htmlspecialchars($status) ?>" data-products="<?= (int) $metric['products'] ?>" data-orders="<?= (int) $metric['orders'] ?>" data-revenue="<?= (float) $metric['revenue'] ?>" data-created="<?= htmlspecialchars($store['created_at'] ?? '') ?>">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-50 text-sm font-extrabold text-emerald-800 ring-1 ring-emerald-100"><?= htmlspecialchars($initials($store['name'] ?? 'Toko')) ?></span>
                                    <div>
                                        <p class="font-extrabold text-slate-950"><?= htmlspecialchars($store['name'] ?? 'Toko') ?></p>
                                        <p class="text-sm text-slate-500"><?= htmlspecialchars($store['owner_name'] ?? 'Pemilik') ?></p>
                                        <?php if (!empty($store['address'])): ?>
                                            <p class="mt-1 max-w-xs truncate text-xs text-slate-400"><?= htmlspecialchars($store['address']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700"><?= $formatNumber((int) $metric['products']) ?></td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700"><?= $formatNumber((int) $metric['orders']) ?></td>
                            <td class="px-5 py-4 text-right font-extrabold text-slate-950"><?= $formatCurrency((float) $metric['revenue']) ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1 <?= $badge[1] ?>"><?= htmlspecialchars($badge[0]) ?></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold uppercase ring-1 <?= $statusClass($status) ?>"><?= htmlspecialchars($status) ?></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button" class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 hover:border-emerald-700 hover:text-emerald-700">Detail</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($stores)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada toko.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm font-semibold text-slate-500">Menampilkan <span data-visible-count><?= $formatNumber($totalStores) ?></span> dari <?= $formatNumber($totalStores) ?> toko</p>
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

    const rows = [...document.querySelectorAll('[data-store-row]')];
    const tbody = document.querySelector('[data-store-table-body]');
    const search = document.querySelector('[data-store-search]');
    const statusFilter = document.querySelector('[data-status-filter]');
    const sortSelect = document.querySelector('[data-sort-store]');
    const visibleCount = document.querySelector('[data-visible-count]');
    const filterPanel = document.querySelector('[data-store-filter-panel]');
    const filterButton = document.querySelector('[data-toggle-store-filters]');

    const filterRows = () => {
        const query = (search?.value || '').toLowerCase().trim();
        const status = statusFilter?.value || '';
        let shown = 0;
        rows.forEach((row) => {
            const matchSearch = !query || row.dataset.search.includes(query);
            const matchStatus = !status || row.dataset.status === status;
            const visible = matchSearch && matchStatus;
            row.classList.toggle('hidden', !visible);
            if (visible) shown += 1;
        });
        if (visibleCount) visibleCount.textContent = shown.toLocaleString('id-ID');
    };

    const sortRows = () => {
        const mode = sortSelect?.value || 'newest';
        const sorted = [...rows].sort((a, b) => {
            if (mode === 'revenue') return Number(b.dataset.revenue) - Number(a.dataset.revenue);
            if (mode === 'products') return Number(b.dataset.products) - Number(a.dataset.products);
            if (mode === 'orders') return Number(b.dataset.orders) - Number(a.dataset.orders);
            return String(b.dataset.created).localeCompare(String(a.dataset.created));
        });
        sorted.forEach((row) => tbody?.appendChild(row));
        filterRows();
    };

    search?.addEventListener('input', filterRows);
    statusFilter?.addEventListener('change', filterRows);
    sortSelect?.addEventListener('change', sortRows);
    filterButton?.addEventListener('click', () => filterPanel?.classList.toggle('hidden'));

    loadChart();
    window.setInterval(loadChart, 10000);
})();
</script>
