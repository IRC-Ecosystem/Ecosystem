<?php
/** @var array $data */
$summary = $data['summary'] ?? [];
$orderItems = $data['orderItems'] ?? [];
$orders = $data['orders'] ?? [];
$money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$revenue = (float) ($summary['total_pendapatan'] ?? 0);
$monthRevenue = (float) ($summary['omzet_bulan_ini'] ?? 0);
$todayRevenue = (float) ($summary['omzet_hari_ini'] ?? 0);
$marketplaceFee = (float) ($summary['total_fee_marketplace'] ?? 0);
$withdrawable = max(0, $revenue - $marketplaceFee);
$target = max(55000000, $monthRevenue * 1.3, 1);
$targetPercent = min(100, (int) (($monthRevenue / $target) * 100));
$processedWithdrawals = max(1, (int) ceil(count($orders) / 3));

$dailyRevenue = [];
for ($i = 6; $i >= 0; $i--) {
    $timestamp = strtotime("-$i days");
    $dayLabel = match (date('D', $timestamp)) {
        'Mon' => 'Sen',
        'Tue' => 'Sel',
        'Wed' => 'Rab',
        'Thu' => 'Kam',
        'Fri' => 'Jum',
        'Sat' => 'Sab',
        'Sun' => 'Min',
        default => date('D', $timestamp),
    };
    $dailyRevenue[$dayLabel] = 0.0;
}

foreach ($orders as $order) {
    if (($order['payment_status'] ?? '') === 'paid' || ($order['order_status'] ?? '') === 'completed') {
        $timestamp = strtotime($order['created_at'] ?? 'now');
        $dayLabel = match (date('D', $timestamp)) {
            'Mon' => 'Sen',
            'Tue' => 'Sel',
            'Wed' => 'Rab',
            'Thu' => 'Kam',
            'Fri' => 'Jum',
            'Sat' => 'Sab',
            'Sun' => 'Min',
            default => date('D', $timestamp),
        };
        if (isset($dailyRevenue[$dayLabel])) {
            $dailyRevenue[$dayLabel] += (float) ($order['subtotal'] ?? $order['total'] ?? 0);
        }
    }
}
?>

<style>
    .finance-chart-line {
        filter: drop-shadow(0 8px 12px rgba(0, 104, 95, 0.14));
    }
</style>

<section class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0b1c30]">Manajemen Keuangan</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Pantau pendapatan, status pencairan dana, fee marketplace, dan transaksi order toko kamu.</p>
        </div>
        <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049] active:scale-95">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H4v4"></path><path d="M4 12v8h16v-8"></path><path d="M8 8V4h8v4"></path></svg>
            Tarik Dana
        </button>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <article class="relative overflow-hidden rounded-xl bg-[#00685f] p-6 text-white shadow-lg">
            <div class="relative z-10">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs font-extrabold uppercase tracking-wide text-white/85">Total Saldo</span>
                    <span class="text-white/70">Info</span>
                </div>
                <h2 class="text-4xl font-extrabold"><?= $money($revenue) ?></h2>
                <div class="mt-4 flex items-center gap-2 text-sm font-bold text-[#89f5e7]">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 17 6-6 4 4 8-8"></path><path d="M14 7h7v7"></path></svg>
                    <span><?= $todayRevenue > 0 ? $money($todayRevenue) . ' hari ini' : 'Belum ada omzet hari ini' ?></span>
                </div>
            </div>
            <div class="absolute -bottom-8 -right-6 text-white/10">
                <svg class="h-32 w-32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <div>
                <div class="mb-3 flex items-center justify-between text-[#3d4947]">
                    <span class="text-xs font-extrabold uppercase tracking-wide">Saldo Bisa Ditarik</span>
                    <span class="text-sm font-bold text-[#00685f]">Verified</span>
                </div>
                <h2 class="text-3xl font-extrabold text-[#0b1c30]"><?= $money($withdrawable) ?></h2>
            </div>
            <div class="mt-5 flex items-center justify-between border-t border-[#bcc9c6] pt-4">
                <p class="text-xs text-[#6d7a77]">Terakhir diperbarui: Hari ini</p>
                <button type="button" class="text-sm font-extrabold text-[#00685f] hover:underline">Rincian</button>
            </div>
        </article>

        <article class="flex flex-col justify-between rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <div>
                <div class="mb-3 flex items-center justify-between text-[#3d4947]">
                    <span class="text-xs font-extrabold uppercase tracking-wide">Pendapatan Bulan Ini</span>
                    <span class="text-sm font-bold"><?= date('M Y') ?></span>
                </div>
                <h2 class="text-3xl font-extrabold text-[#0b1c30]"><?= $money($monthRevenue) ?></h2>
            </div>
            <div class="mt-5 space-y-2">
                <div class="h-2 overflow-hidden rounded-full bg-[#dce9ff]">
                    <div class="h-full rounded-full bg-[#00685f]" style="width: <?= $targetPercent ?>%"></div>
                </div>
                <p class="text-xs text-[#6d7a77]"><?= $targetPercent ?>% dari target bulan ini (<?= $money($target) ?>)</p>
            </div>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <h2 class="text-xl font-extrabold text-[#0b1c30]">Analisis Pendapatan</h2>
                <select class="rounded-lg border border-[#bcc9c6] bg-[#eff4ff] px-3 py-2 text-sm font-bold text-[#0b1c30]">
                    <option>7 Hari Terakhir</option>
                    <option>30 Hari Terakhir</option>
                    <option>Tahun Ini</option>
                </select>
            </div>
            <div class="relative h-72 w-full rounded-lg border border-[#d3e4fe] bg-[#f8f9ff] p-4">
                <canvas id="sellerRevenueChart"></canvas>
            </div>
        </section>

        <aside class="relative overflow-hidden rounded-xl bg-[#0b1c30] p-6 text-white shadow-lg">
            <div class="relative z-10 space-y-4">
                <h2 class="text-xl font-extrabold">Wawasan Pekan Ini</h2>
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-[#89f5e7]">Insight</span>
                        <p class="text-sm leading-6 text-white/90">Pesanan aktif perlu diproses cepat agar saldo lebih cepat masuk ke pencairan SmartBank.</p>
                    </div>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <div class="flex items-start gap-3">
                        <span class="text-[#ffb95f]">Fee</span>
                        <p class="text-sm leading-6 text-white/90">Total fee marketplace tercatat <?= $money($marketplaceFee) ?> dari seluruh transaksi.</p>
                    </div>
                </div>
                <div class="rounded-lg border border-[#008378]/30 bg-[#008378]/20 p-4">
                    <p class="text-xs font-extrabold uppercase tracking-wide text-[#89f5e7]">Status Pencairan</p>
                    <p class="mt-1 text-sm font-bold">Menunggu SmartBank - <?= $processedWithdrawals ?> batch simulasi</p>
                </div>
            </div>
        </aside>
    </div>

    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-4 border-b border-[#bcc9c6] p-6 md:flex-row md:items-center">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Riwayat Transaksi</h2>
            <div class="flex gap-2 overflow-x-auto">
                <button class="rounded-full bg-[#00685f] px-4 py-2 text-xs font-extrabold text-white">Semua</button>
                <button class="rounded-full bg-[#eff4ff] px-4 py-2 text-xs font-extrabold text-[#3d4947] transition hover:bg-[#dce9ff]">Penjualan</button>
                <button class="rounded-full bg-[#eff4ff] px-4 py-2 text-xs font-extrabold text-[#3d4947] transition hover:bg-[#dce9ff]">Penarikan</button>
                <button class="rounded-full bg-[#eff4ff] px-4 py-2 text-xs font-extrabold text-[#3d4947] transition hover:bg-[#dce9ff]">Lainnya</button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#eff4ff]">
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Transaksi</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Tanggal & Waktu</th>
                        <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bcc9c6]">
                    <?php foreach (array_slice($orderItems, 0, 10) as $item): ?>
                        <tr class="group cursor-pointer transition hover:bg-[#eff4ff]/60">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#00685f]/10 text-[#00685f] transition group-hover:bg-[#00685f] group-hover:text-white">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"></path><path d="M3 6h18"></path><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-[#0b1c30]">Pesanan #<?= htmlspecialchars($item['order_code']) ?></p>
                                        <p class="text-xs text-[#3d4947]">Penjualan Produk: <?= htmlspecialchars($item['product_name']) ?> (<?= (int) $item['qty'] ?> pcs)</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[#3d4947]"><?= htmlspecialchars(date('d M Y, H:i', strtotime($item['created_at'] ?? 'now'))) ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2 rounded-full bg-[#00685f]/10 px-4 py-1 text-xs font-extrabold text-[#00685f]">
                                    <span class="h-1.5 w-1.5 rounded-full bg-[#00685f]"></span>
                                    Berhasil
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-lg font-extrabold text-[#0b1c30]">+<?= $money($item['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orderItems): ?>
                        <tr><td colspan="4" class="px-6 py-12 text-center text-sm text-[#6d7a77]">Belum ada transaksi order.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="flex justify-center border-t border-[#bcc9c6] bg-[#eff4ff] p-4">
            <button class="inline-flex items-center gap-2 text-sm font-extrabold text-[#00685f] transition hover:gap-3">
                Lihat Semua Transaksi
                <span>&rarr;</span>
            </button>
        </div>
    </section>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('sellerRevenueChart');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    const days = <?= json_encode(array_keys($dailyRevenue)) ?>;
    const revenues = <?= json_encode(array_values($dailyRevenue)) ?>;

    const gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(0, 104, 95, 0.35)');
    gradient.addColorStop(1, 'rgba(0, 104, 95, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: days,
            datasets: [{
                label: 'Pendapatan (Rp)',
                data: revenues,
                borderColor: '#00685f',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#00685f'
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
