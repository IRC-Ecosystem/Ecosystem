<?php
/** @var array $data */
$orders = $data['orders'] ?? [];
$users = $data['users'] ?? [];
$stores = $data['stores'] ?? [];
$metrics = $data['store_metrics'] ?? [];
$roles = $data['charts']['roles'] ?? [];
$orderStatus = $data['charts']['order_status'] ?? [];
$storeStatus = $data['charts']['store_status'] ?? [];
$stats = $data['stats'] ?? [];

$totalUsers = count($users);
$totalStores = count($stores);
$totalOrders = count($orders);
$totalProducts = (int) ($stats['products'] ?? 0);
$paidOrders = array_values(array_filter($orders, static fn ($order) => ($order['payment_status'] ?? '') === 'paid'));
$gmv = array_sum(array_map(static fn ($order) => (float) ($order['total'] ?? 0), $paidOrders));
$avgOrder = count($paidOrders) > 0 ? $gmv / count($paidOrders) : 0;
$completedOrders = (int) ($orderStatus['completed'] ?? 0);
$successRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 0;
$activeStores = (int) ($storeStatus['active'] ?? 0);
$inactiveStores = max(0, $totalStores - $activeStores);

$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
$formatCurrency = static fn (float $value): string => 'Rp' . number_format($value, 0, ',', '.');

$allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthlyData = array_fill_keys($allMonths, 0.0);

foreach ($orders as $order) {
    if (($order['payment_status'] ?? '') !== 'paid') {
        continue;
    }
    $m = date('M', strtotime($order['created_at'] ?? 'now'));
    if (isset($monthlyData[$m])) {
        $monthlyData[$m] += (float) ($order['total'] ?? 0);
    }
}
$maxMonthlyRevenue = max(1, ...array_values($monthlyData));

$roleTotal = max(1, array_sum(array_map('intval', $roles)));
$buyerPercent = round(((int) ($roles['user'] ?? 0) / $roleTotal) * 100);
$sellerPercent = round(((int) ($roles['seller'] ?? 0) / $roleTotal) * 100);
$adminPercent = max(0, 100 - $buyerPercent - $sellerPercent);

$orderTotal = max(1, array_sum(array_map('intval', $orderStatus)));
$completedPercent = round(((int) ($orderStatus['completed'] ?? 0) / $orderTotal) * 100);
$processingPercent = round(((int) ($orderStatus['processing'] ?? 0) / $orderTotal) * 100);
$cancelledPercent = round(((int) ($orderStatus['cancelled'] ?? 0) / $orderTotal) * 100);

$regional = [];
foreach ($stores as $store) {
    $region = trim((string) ($store['address'] ?? 'Tidak diketahui'));
    $region = $region !== '' ? strtok($region, ',') : 'Tidak diketahui';
    $storeId = (int) ($store['id'] ?? 0);
    $metric = $metrics[$storeId] ?? ['revenue' => 0, 'orders' => 0];
    if (!isset($regional[$region])) {
        $regional[$region] = ['stores' => 0, 'orders' => 0, 'revenue' => 0];
    }
    $regional[$region]['stores'] += 1;
    $regional[$region]['orders'] += (int) ($metric['orders'] ?? 0);
    $regional[$region]['revenue'] += (float) ($metric['revenue'] ?? 0);
}
uasort($regional, static fn ($a, $b) => $b['revenue'] <=> $a['revenue']);
?>

<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-700">Performance Dashboard</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">Analitik PasarKita</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Pantau pertumbuhan transaksi, komposisi user, status order, dan performa regional UMKM dari database aplikasi.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" class="inline-flex items-center justify-center rounded-md border border-emerald-700 bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm hover:bg-emerald-50">
                Export PDF
            </button>
            <button type="button" onclick="window.location.reload()" class="inline-flex items-center justify-center rounded-md bg-emerald-700 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-800">
                Update Data
            </button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <article class="rounded-xl bg-emerald-700 p-5 text-white shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-100">GMV Paid</p>
            <h2 class="mt-3 text-3xl font-extrabold"><?= $formatCurrency((float) $gmv) ?></h2>
            <p class="mt-3 text-sm font-bold text-emerald-100">Avg <?= $formatCurrency((float) $avgOrder) ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total User</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalUsers) ?></h2>
            <p class="mt-3 text-sm font-bold text-emerald-700"><?= $sellerPercent ?>% seller</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Toko</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalStores) ?></h2>
            <p class="mt-3 text-sm font-bold text-emerald-700"><?= $formatNumber($activeStores) ?> aktif</p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Success Rate</p>
            <h2 class="mt-3 text-3xl font-extrabold text-slate-950"><?= $successRate ?>%</h2>
            <p class="mt-3 text-sm font-bold text-slate-500"><?= $formatNumber($completedOrders) ?> order selesai</p>
        </article>
    </div>

    <div class="grid grid-cols-12 gap-6">
        <article class="col-span-12 rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-slate-950">Pertumbuhan Pendapatan Platform</h2>
                    <p class="mt-1 text-sm text-slate-600">Total volume transaksi bulanan berdasarkan order paid.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700">12 Periode Bulanan</span>
            </div>
            <div class="relative h-[340px] w-full rounded-lg bg-emerald-50/50 p-4">
                <canvas id="platformRevenueChart"></canvas>
            </div>
        </article>

        <article class="col-span-12 rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-4">
            <h2 class="text-xl font-extrabold text-slate-950">Distribusi Peran</h2>
            <div class="mt-6 flex flex-col items-center">
                <div class="flex h-48 w-48 items-center justify-center rounded-full border-[18px] border-slate-200" style="border-top-color:#047857;border-right-color:#047857;border-bottom-color:#047857;">
                    <div class="text-center">
                        <p class="text-2xl font-extrabold text-slate-950"><?= $formatNumber($totalUsers) ?></p>
                        <p class="text-xs font-bold text-slate-500">Total User</p>
                    </div>
                </div>
                <div class="mt-6 grid w-full grid-cols-3 gap-3 text-center">
                    <div class="rounded-lg bg-emerald-50 p-3"><p class="text-xs font-bold text-slate-500">Buyer</p><p class="font-extrabold text-emerald-700"><?= $buyerPercent ?>%</p></div>
                    <div class="rounded-lg bg-sky-50 p-3"><p class="text-xs font-bold text-slate-500">Seller</p><p class="font-extrabold text-sky-700"><?= $sellerPercent ?>%</p></div>
                    <div class="rounded-lg bg-slate-100 p-3"><p class="text-xs font-bold text-slate-500">Admin</p><p class="font-extrabold text-slate-700"><?= $adminPercent ?>%</p></div>
                </div>
            </div>
        </article>

        <article class="col-span-12 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-5">
            <h2 class="text-xl font-extrabold text-slate-950">Status Pesanan</h2>
            <div class="mt-6 flex justify-center">
                <div class="h-40 w-40 rounded-full" style="background: conic-gradient(#047857 0% <?= $completedPercent ?>%, #f59e0b <?= $completedPercent ?>% <?= min(100, $completedPercent + $processingPercent) ?>%, #dc2626 <?= min(100, $completedPercent + $processingPercent) ?>% 100%)"></div>
            </div>
            <div class="mt-6 space-y-3">
                <div class="flex justify-between border-b border-slate-100 pb-2 text-sm"><span class="font-bold text-slate-700">Selesai</span><span class="font-extrabold"><?= $completedPercent ?>%</span></div>
                <div class="flex justify-between border-b border-slate-100 pb-2 text-sm"><span class="font-bold text-slate-700">Processing</span><span class="font-extrabold"><?= $processingPercent ?>%</span></div>
                <div class="flex justify-between text-sm"><span class="font-bold text-slate-700">Dibatalkan</span><span class="font-extrabold"><?= $cancelledPercent ?>%</span></div>
            </div>
        </article>

        <article class="col-span-12 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:col-span-7">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-extrabold text-slate-950">Ringkasan Status Toko</h2>
                <span class="text-sm font-bold text-slate-500">Aktif vs Review</span>
            </div>
            <div class="flex h-[240px] items-end justify-around gap-6">
                <?php foreach ([['Aktif', $activeStores, 'bg-emerald-700'], ['Perlu Review', $inactiveStores, 'bg-amber-500'], ['Produk', $totalProducts, 'bg-sky-600'], ['Order', $totalOrders, 'bg-slate-800']] as $bar): ?>
                    <?php $height = max(16, round(((int) $bar[1] / max(1, $totalProducts, $totalOrders, $totalStores)) * 210)); ?>
                    <div class="flex flex-1 flex-col items-center gap-3">
                        <div class="w-12 rounded-t-md <?= $bar[2] ?>" style="height: <?= $height ?>px"></div>
                        <span class="text-center text-xs font-bold text-slate-500"><?= htmlspecialchars($bar[0]) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-200 p-5">
            <div>
                <h2 class="text-xl font-extrabold text-slate-950">Performa Regional UMKM</h2>
                <p class="mt-1 text-sm text-slate-600">Dikelompokkan dari alamat toko yang tersedia.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left">
                <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">Wilayah</th>
                        <th class="px-5 py-4 text-right">Total Toko</th>
                        <th class="px-5 py-4 text-right">Transaksi</th>
                        <th class="px-5 py-4 text-right">Rerata Penjualan</th>
                        <th class="px-5 py-4">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($regional as $region => $row): ?>
                        <?php $avg = $row['stores'] > 0 ? $row['revenue'] / $row['stores'] : 0; ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4 font-extrabold text-slate-950"><?= htmlspecialchars($region) ?></td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700"><?= $formatNumber((int) $row['stores']) ?></td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700"><?= $formatCurrency((float) $row['revenue']) ?></td>
                            <td class="px-5 py-4 text-right font-bold text-slate-700"><?= $formatCurrency((float) $avg) ?></td>
                            <td class="px-5 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-extrabold <?= $avg > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>"><?= $avg > 0 ? 'Good' : 'Belum aktif' ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($regional)): ?>
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data regional.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('platformRevenueChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const months = <?= json_encode(array_keys($monthlyData)) ?>;
    const revenues = <?= json_encode(array_values($monthlyData)) ?>;

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(4, 120, 87, 0.4)');
    gradient.addColorStop(1, 'rgba(4, 120, 87, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: revenues,
                borderColor: '#047857',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#047857'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            return ' Pendapatan: Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                        }
                    }
                }
            }
        }
    });
});
</script>
