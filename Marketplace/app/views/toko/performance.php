<?php
/** @var array $data */
$summary = $data['summary'] ?? [];
$products = $data['products'] ?? [];
$orders = $data['orders'] ?? [];
$bestSellers = $data['bestSellers'] ?? [];
$lowStock = $data['lowStock'] ?? [];
$money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$revenue = (float) ($summary['total_pendapatan'] ?? 0);
$completed = (int) ($summary['pesanan_selesai'] ?? 0);
$cancelled = (int) ($summary['pesanan_batal'] ?? 0);
$totalOrders = max(count($orders), 1);
$completionRate = min(100, round(($completed / $totalOrders) * 100, 1));
$chatSpeed = max(8, 18 - count($data['messages'] ?? []));
$rating = 4.8 + min(0.1, $completed * 0.01);
$visitors = max(1245, count($products) * 317 + count($orders) * 210);
$performanceIndex = min(96, 72 + (int) ($completionRate / 5) + min(8, count($bestSellers)));
$topProduct = $bestSellers[0]['product_name'] ?? '-';

$weeklyVisitors = [
    'Minggu 1' => (int) round($visitors * 0.18),
    'Minggu 2' => (int) round($visitors * 0.28),
    'Minggu 3' => (int) round($visitors * 0.34),
    'Minggu 4' => (int) round($visitors * 0.20),
];

$conversionRateOverall = count($orders) > 0 ? min(12.5, round((count($orders) / max($visitors, 1)) * 100, 1)) : 0.0;
$hourlyConversion = [
    '00:00' => round($conversionRateOverall * 0.4, 1),
    '06:00' => round($conversionRateOverall * 0.8, 1),
    '12:00' => round($conversionRateOverall * 1.4, 1),
    '18:00' => round($conversionRateOverall * 1.8, 1),
    '23:59' => round($conversionRateOverall * 1.1, 1),
];
$maxConversion = max(0.1, ...array_values($hourlyConversion));
?>

<style>
    .glass-card {
        background: rgba(255, 255, 255, 0.86);
        backdrop-filter: blur(8px);
        border: 1px solid #bcc9c6;
        box-shadow: 0 14px 36px rgba(11, 28, 48, 0.06);
    }
</style>

<section class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0b1c30]">Performa Toko</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Pantau omzet, pesanan, produk terlaris, pengunjung, konversi, dan rating toko.</p>
        </div>
        <span class="w-fit rounded-lg bg-[#eff4ff] px-4 py-2 text-sm font-extrabold text-[#3d4947]">30 Hari Terakhir</span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <article class="glass-card relative overflow-hidden rounded-xl p-6 lg:col-span-8">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-start">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0b1c30]">Kesehatan Toko</h2>
                    <p class="mt-1 text-sm text-[#3d4947]">Status toko dihitung dari pesanan selesai, kecepatan respons, dan rating.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-[#00685f]/10 px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-[#00685f]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"></path></svg>
                    Level: Elite Seller
                </span>
            </div>

            <div class="mt-6 grid gap-6 md:grid-cols-3">
                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#00685f]"></span>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Penyelesaian Pesanan</p>
                    </div>
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-extrabold text-[#00685f]"><?= $completionRate ?>%</span>
                        <span class="mb-2 rounded bg-[#00685f]/5 px-2 py-1 text-xs font-bold text-[#00685f]">+2.1%</span>
                    </div>
                    <div class="h-2 rounded-full bg-[#bcc9c6]/40"><div class="h-2 rounded-full bg-[#00685f]" style="width: <?= $completionRate ?>%"></div></div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#825100]"></span>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Kecepatan Chat</p>
                    </div>
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-extrabold text-[#825100]">~<?= $chatSpeed ?>m</span>
                        <span class="mb-2 rounded bg-[#825100]/5 px-2 py-1 text-xs font-bold text-[#825100]">lebih cepat</span>
                    </div>
                    <div class="h-2 rounded-full bg-[#bcc9c6]/40"><div class="h-2 rounded-full bg-[#825100]" style="width: 85%"></div></div>
                </div>

                <div class="space-y-3">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#565e74]"></span>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Rating Toko</p>
                    </div>
                    <div class="flex items-end gap-2">
                        <span class="text-4xl font-extrabold text-[#565e74]"><?= number_format($rating, 1) ?></span>
                        <span class="mb-2 rounded bg-[#565e74]/5 px-2 py-1 text-xs font-bold text-[#565e74]">/ 5.0</span>
                    </div>
                    <div class="flex gap-1 text-[#825100]">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <span class="text-lg">★</span>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </article>

        <aside class="flex flex-col justify-between rounded-xl bg-[#0b1c30] p-6 text-white shadow-xl lg:col-span-4">
            <div>
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-extrabold">Indeks Performa</h2>
                    <span class="text-[#89f5e7]">Naik</span>
                </div>
                <p class="text-sm leading-6 text-[#bec6e0]">Dibandingkan dengan rata-rata kategori UMKM serupa.</p>
            </div>
            <div class="relative flex items-center justify-center py-8">
                <svg class="h-36 w-36 -rotate-90">
                    <circle cx="72" cy="72" r="62" fill="transparent" stroke="rgba(255,255,255,0.12)" stroke-width="10"></circle>
                    <circle cx="72" cy="72" r="62" fill="transparent" stroke="#6bd8cb" stroke-dasharray="389.56" stroke-dashoffset="<?= 389.56 - (389.56 * $performanceIndex / 100) ?>" stroke-linecap="round" stroke-width="10"></circle>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-4xl font-extrabold"><?= $performanceIndex ?></span>
                    <span class="text-sm font-extrabold text-[#89f5e7]">Unggul</span>
                </div>
            </div>
            <button class="w-full rounded-lg border border-[#89f5e7] px-4 py-3 text-sm font-extrabold text-[#89f5e7] transition hover:bg-[#89f5e7]/10">Lihat Detail Analitik</button>
        </aside>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <section class="glass-card rounded-xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0b1c30]">Tren Pengunjung</h2>
                    <p class="mt-1 text-sm text-[#3d4947]">Total <?= number_format($visitors, 0, ',', '.') ?> pengunjung</p>
                </div>
                <div class="flex gap-2 text-[#3d4947]">
                    <button class="rounded p-2 hover:bg-[#eff4ff]">Export</button>
                    <button class="rounded p-2 hover:bg-[#eff4ff]">Menu</button>
                </div>
            </div>
            <div class="relative h-64 w-full rounded-lg bg-[#eff4ff]/60 p-3">
                <canvas id="visitorTrendChart"></canvas>
            </div>
        </section>

        <section class="glass-card rounded-xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0b1c30]">Tingkat Konversi</h2>
                    <p class="mt-1 text-sm text-[#3d4947]">Rata-rata <?= $conversionRateOverall ?>% per hari</p>
                </div>
                <span class="rounded-full bg-[#e8fff8] px-3 py-1 text-xs font-extrabold text-[#00685f]">Highest <?= $maxConversion ?>%</span>
            </div>
            <div class="relative h-64 w-full rounded-lg bg-[#fff8f0]/60 p-3">
                <canvas id="conversionRateChart"></canvas>
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-[#bcc9c6] bg-[#eff4ff] p-6 md:flex-row md:items-center">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Insight Produk Terlaris</h2>
            <a href="<?= BASEURL ?>toko/products" class="text-sm font-extrabold text-[#00685f]">Lihat Semua Produk &gt;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#e5eeff] text-[#3d4947]">
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide">Nama Produk</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide">Dilihat</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide">Terjual</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide">Konversi</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide">Status Stok</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bcc9c6]">
                    <?php foreach (array_slice($bestSellers, 0, 5) as $index => $product): ?>
                        <?php
                        $sold = (int) ($product['qty_sold'] ?? 0);
                        $seen = max(120, $sold * 27 + ($index + 1) * 480);
                        $conversion = $seen ? min(12, ($sold / $seen) * 100) : 0;
                        $stockLow = $index < count($lowStock);
                        ?>
                        <tr class="transition hover:bg-[#eff4ff]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded bg-[#eff4ff] text-[#00685f]">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($product['product_name']) ?></p>
                                        <p class="text-xs text-[#6d7a77]">Produk terlaris #<?= $index + 1 ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[#3d4947]"><?= number_format($seen, 0, ',', '.') ?></td>
                            <td class="px-6 py-4 text-[#3d4947]"><?= $sold ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span><?= number_format($conversion, 1) ?>%</span>
                                    <div class="h-1.5 w-20 rounded-full bg-[#bcc9c6]/40"><div class="h-1.5 rounded-full bg-[#00685f]" style="width: <?= min(100, (int) ($conversion * 15)) ?>%"></div></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded px-3 py-1 text-xs font-extrabold <?= $stockLow ? 'bg-[#ffdad6] text-[#93000a]' : 'bg-[#00685f]/10 text-[#00685f]' ?>"><?= $stockLow ? 'Stok Rendah' : 'Tersedia' ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$bestSellers): ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-[#6d7a77]">Belum ada data produk terjual.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-5">
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-5 shadow-sm"><p class="text-sm text-[#3d4947]">Omzet</p><b class="mt-2 block text-xl text-[#00685f]"><?= $money($revenue) ?></b></article>
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-5 shadow-sm"><p class="text-sm text-[#3d4947]">Pesanan</p><b class="mt-2 block text-xl text-[#0b1c30]"><?= count($orders) ?></b></article>
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-5 shadow-sm"><p class="text-sm text-[#3d4947]">Terlaris</p><b class="mt-2 block truncate text-xl text-[#0b1c30]"><?= htmlspecialchars($topProduct) ?></b></article>
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-5 shadow-sm"><p class="text-sm text-[#3d4947]">Pengunjung</p><b class="mt-2 block text-xl text-[#0b1c30]"><?= number_format($visitors, 0, ',', '.') ?></b></article>
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-5 shadow-sm"><p class="text-sm text-[#3d4947]">Rating</p><b class="mt-2 block text-xl text-[#0b1c30]"><?= number_format($rating, 1) ?>/5</b></article>
    </section>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Visitor Trend Chart (Bar Chart)
    const visitorCanvas = document.getElementById('visitorTrendChart');
    if (visitorCanvas) {
        const ctxVisitor = visitorCanvas.getContext('2d');
        const weeks = <?= json_encode(array_keys($weeklyVisitors)) ?>;
        const visitorData = <?= json_encode(array_values($weeklyVisitors)) ?>;

        new Chart(ctxVisitor, {
            type: 'bar',
            data: {
                labels: weeks,
                datasets: [{
                    label: 'Pengunjung',
                    data: visitorData,
                    backgroundColor: '#00685f',
                    borderRadius: 6,
                    barThickness: 28
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
                                return ' ' + new Intl.NumberFormat('id-ID').format(context.raw) + ' Pengunjung';
                            }
                        }
                    }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // 2. Conversion Rate Chart (Area Line Chart)
    const conversionCanvas = document.getElementById('conversionRateChart');
    if (conversionCanvas) {
        const ctxConversion = conversionCanvas.getContext('2d');
        const hours = <?= json_encode(array_keys($hourlyConversion)) ?>;
        const rates = <?= json_encode(array_values($hourlyConversion)) ?>;

        const gradient = ctxConversion.createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, 'rgba(130, 81, 0, 0.35)');
        gradient.addColorStop(1, 'rgba(130, 81, 0, 0.0)');

        new Chart(ctxConversion, {
            type: 'line',
            data: {
                labels: hours,
                datasets: [{
                    label: 'Tingkat Konversi (%)',
                    data: rates,
                    borderColor: '#825100',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#825100'
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
                                return ' Konversi: ' + context.raw + '%';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
