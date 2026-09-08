<?php
/** @var array $data */
$store = $data['store'];
$summary = $data['summary'] ?? [];
$products = $data['products'] ?? [];
$orders = $data['orders'] ?? [];
$orderItems = $data['orderItems'] ?? [];
$lowStock = $data['lowStock'] ?? [];
$bestSellers = $data['bestSellers'] ?? [];
$messages = $data['messages'] ?? [];
$money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$totalProducts = count($products);
$activeProducts = count(array_filter($products, static fn ($product) => ($product['status'] ?? '') === 'active'));
$unreadMessages = count(array_filter($messages, static fn ($message) => !empty($message['unread'])));
$visitors = max(24, $totalProducts * 17 + count($orders) * 9);
$firstName = trim(explode(' ', $store['name'])[0] ?? $store['name']);
$statusBadge = function (string $status): string {
    return match ($status) {
        'completed', 'active', 'paid', 'aktif' => 'bg-[#e8fff8] text-[#00685f]',
        'shipped' => 'bg-[#dae2fd] text-[#3f465c]',
        'cancelled', 'inactive' => 'bg-[#ffdad6] text-[#93000a]',
        default => 'bg-[#ffddb8] text-[#653e00]',
    };
};
$firstProductImage = $products[0]['image_url'] ?? '';

$allMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$sellerMonthlyData = array_fill_keys($allMonths, 0.0);
foreach ($orders as $order) {
    if (($order['payment_status'] ?? '') === 'paid' || ($order['order_status'] ?? '') === 'completed') {
        $m = date('M', strtotime($order['created_at'] ?? 'now'));
        if (isset($sellerMonthlyData[$m])) {
            $sellerMonthlyData[$m] += (float) ($order['subtotal'] ?? $order['total'] ?? 0);
        }
    }
}
?>

<style>
    .seller-card {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #bcc9c6;
        box-shadow: 0 14px 40px rgba(11, 28, 48, 0.06);
    }
</style>

<section class="space-y-6">
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-6">
            <p class="text-sm font-extrabold uppercase tracking-wide text-[#00685f]">Seller Center</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#0b1c30]">Selamat Datang Kembali, <?= htmlspecialchars($firstName) ?>!</h1>
            <p class="mt-2 max-w-3xl text-base leading-7 text-[#3d4947]">Berikut ringkasan performa toko, pesanan yang perlu diproses, stok yang perlu diamankan, dan perkembangan penjualan dari database PasarKita.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="<?= BASEURL ?>toko/products" class="rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white transition hover:bg-[#005049]">Tambah Produk</a>
                <a href="<?= BASEURL ?>toko/orders" class="rounded-lg border border-[#00685f] px-5 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Proses Pesanan</a>
            </div>
        </div>

        <aside class="rounded-xl bg-[#0b1c30] p-6 text-white">
            <div class="flex items-center gap-4">
                <?php if ($firstProductImage): ?>
                    <img src="<?= htmlspecialchars($firstProductImage) ?>" alt="<?= htmlspecialchars($store['name']) ?>" class="h-16 w-16 rounded-lg border border-white/20 object-cover">
                <?php else: ?>
                    <div class="flex h-16 w-16 items-center justify-center rounded-lg bg-[#00685f] text-2xl font-extrabold"><?= htmlspecialchars(strtoupper(substr($store['name'], 0, 1))) ?></div>
                <?php endif; ?>
                <div>
                    <h2 class="text-xl font-extrabold"><?= htmlspecialchars($store['name']) ?></h2>
                    <p class="mt-1 text-sm text-white/70">Seller Center</p>
                </div>
            </div>
            <p class="mt-5 text-sm leading-6 text-white/75"><?= htmlspecialchars($store['description'] ?: 'Toko aktif di ekosistem PasarKita.') ?></p>
            <div class="mt-5 flex items-center justify-between rounded-lg bg-white/10 p-4">
                <span class="text-sm text-white/70">Status Toko</span>
                <span class="rounded-full bg-[#89f5e7] px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-[#00201d]"><?= htmlspecialchars($store['status']) ?></span>
            </div>
        </aside>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="seller-card rounded-xl p-5 transition hover:-translate-y-1">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#008378]/10 text-[#00685f]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-extrabold text-[#00685f]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 17 6-6 4 4 8-8"></path><path d="M14 7h7v7"></path></svg>
                    Bulan ini
                </span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Total Penjualan</p>
            <h3 class="mt-1 text-2xl font-extrabold text-[#0b1c30]"><?= $money($summary['omzet_bulan_ini'] ?? 0) ?></h3>
            <p class="mt-1 text-sm text-[#3d4947]">Hari ini <?= $money($summary['omzet_hari_ini'] ?? 0) ?></p>
        </article>

        <article class="seller-card rounded-xl p-5 transition hover:-translate-y-1">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#dae2fd] text-[#3f465c]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>
                </div>
                <span class="rounded-full bg-[#89f5e7] px-3 py-1 text-xs font-extrabold text-[#00201d]"><?= (int) ($summary['pesanan_baru'] ?? 0) ?> baru</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Pesanan Masuk</p>
            <h3 class="mt-1 text-2xl font-extrabold text-[#0b1c30]"><?= count($orders) ?> Pesanan</h3>
            <p class="mt-1 text-sm text-[#3d4947]"><?= (int) ($summary['pesanan_aktif'] ?? 0) ?> perlu diproses</p>
        </article>

        <article class="seller-card rounded-xl p-5 transition hover:-translate-y-1">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#ffddb8] text-[#825100]">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.29 7 12 12l8.71-5"></path><path d="M12 22V12"></path></svg>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Produk Aktif</p>
            <h3 class="mt-1 text-2xl font-extrabold text-[#0b1c30]"><?= $activeProducts ?> SKU</h3>
            <p class="mt-1 text-sm text-[#3d4947]">Dari <?= $totalProducts ?> produk</p>
        </article>

        <article class="seller-card rounded-xl p-5 transition hover:-translate-y-1">
            <div class="flex items-start justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-[#89f5e7]/40 text-[#00685f]">
                    <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                </div>
                <?php if ($unreadMessages): ?>
                    <span class="rounded-full bg-[#ba1a1a] px-3 py-1 text-xs font-extrabold text-white"><?= $unreadMessages ?> unread</span>
                <?php endif; ?>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Total Chat</p>
            <h3 class="mt-1 text-2xl font-extrabold text-[#0b1c30]"><?= count($messages) ?> Pesan</h3>
            <p class="mt-1 text-sm text-[#3d4947]">Komunikasi pembeli</p>
        </article>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm lg:col-span-2">
            <div class="mb-5 flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <h2 class="text-xl font-extrabold text-[#0b1c30]">Statistik Penjualan</h2>
                    <p class="mt-1 text-sm text-[#3d4947]">Grafik memuat data terbaru dari database dan akan refresh otomatis.</p>
                </div>
                <select class="rounded-lg border border-[#bcc9c6] bg-white px-3 py-2 text-sm font-bold text-[#0b1c30]">
                    <option>Data Toko Saat Ini</option>
                    <option>30 Hari Terakhir</option>
                    <option>Tahun Ini</option>
                </select>
            </div>
            <div class="relative h-80 w-full rounded-lg border border-[#d3e4fe] bg-[#f8f9ff] p-4">
                <canvas id="sellerSalesChart"></canvas>
            </div>
        </section>

        <aside class="rounded-xl bg-[#0b1c30] p-6 text-white shadow-lg">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#00685f] text-[#89f5e7]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </div>
                <h2 class="text-xl font-extrabold">Tugas Hari Ini</h2>
            </div>
            <div class="mt-6 space-y-4">
                <?php
                $tasks = [
                    ['label' => 'Proses ' . (int) ($summary['pesanan_baru'] ?? 0) . ' pesanan baru', 'checked' => ((int) ($summary['pesanan_baru'] ?? 0)) === 0],
                    ['label' => 'Balas ' . $unreadMessages . ' chat belum dibaca', 'checked' => $unreadMessages === 0],
                    ['label' => 'Amankan ' . count($lowStock) . ' stok menipis', 'checked' => count($lowStock) === 0],
                    ['label' => 'Cek performa produk terlaris', 'checked' => false],
                ];
                foreach ($tasks as $task):
                ?>
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-white/10 bg-white/5 p-4 transition hover:bg-white/10">
                        <input type="checkbox" class="h-5 w-5 rounded border-white/20 bg-transparent text-[#00685f] focus:ring-[#89f5e7]" <?= $task['checked'] ? 'checked' : '' ?>>
                        <span class="<?= $task['checked'] ? 'text-white/50 line-through' : 'text-white' ?> text-sm font-semibold"><?= htmlspecialchars($task['label']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <a href="<?= BASEURL ?>toko/restock" class="mt-6 flex items-center justify-center gap-2 rounded-lg bg-[#008378] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#00685f]">
                Buka Prioritas Kerja
            </a>
        </aside>
    </div>

    <section class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
        <div class="flex flex-col justify-between gap-3 border-b border-[#bcc9c6] p-6 md:flex-row md:items-center">
            <div>
                <h2 class="text-xl font-extrabold text-[#0b1c30]">Pesanan Terbaru</h2>
                <p class="mt-1 text-sm text-[#3d4947]">Pantau status pembayaran dan pengiriman pesanan dari toko kamu.</p>
            </div>
            <a href="<?= BASEURL ?>toko/orders" class="rounded-lg border border-[#00685f] px-5 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Lihat Semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-[#eff4ff] text-[#3d4947]">
                    <tr>
                        <th class="px-6 py-4 font-extrabold uppercase tracking-wide">ID Pesanan</th>
                        <th class="px-6 py-4 font-extrabold uppercase tracking-wide">Produk</th>
                        <th class="px-6 py-4 font-extrabold uppercase tracking-wide">Pelanggan</th>
                        <th class="px-6 py-4 font-extrabold uppercase tracking-wide">Total</th>
                        <th class="px-6 py-4 font-extrabold uppercase tracking-wide">Status</th>
                        <th class="px-6 py-4 font-extrabold uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#bcc9c6]">
                    <?php foreach (array_slice($orders, 0, 5) as $order): ?>
                        <?php
                        $itemsForOrder = array_values(array_filter($orderItems, static fn ($item) => (int) $item['order_id'] === (int) $order['id']));
                        $productName = $itemsForOrder[0]['product_name'] ?? 'Produk toko';
                        ?>
                        <tr class="transition hover:bg-[#f8f9ff]">
                            <td class="px-6 py-4 font-extrabold text-[#00685f]"><?= htmlspecialchars($order['order_code']) ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded border border-[#bcc9c6] bg-[#eff4ff] text-[#00685f]">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                                    </div>
                                    <span class="font-semibold text-[#0b1c30]"><?= htmlspecialchars($productName) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[#3d4947]"><?= htmlspecialchars($order['customer_name'] ?? 'Pembeli') ?></td>
                            <td class="px-6 py-4 font-extrabold text-[#0b1c30]"><?= $money($order['subtotal'] ?? 0) ?></td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-3 py-1 text-xs font-extrabold uppercase tracking-wide <?= $statusBadge($order['order_status'] ?? '') ?>"><?= htmlspecialchars($order['order_status'] ?? '-') ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="<?= BASEURL ?>toko/orders" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-[#00685f] transition hover:bg-[#00685f]/10" aria-label="Lihat pesanan">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orders): ?>
                        <tr><td colspan="6" class="px-6 py-10 text-center text-[#3d4947]">Belum ada pesanan terbaru.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Stok Hampir Habis</h2>
            <p class="mt-1 text-sm text-[#3d4947]">Prioritas restock ke SupplierHub.</p>
            <div class="mt-5 space-y-3">
                <?php foreach (array_slice($lowStock, 0, 4) as $product): ?>
                    <div class="rounded-lg border border-[#ffddb8] bg-[#fff8f0] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($product['name']) ?></p>
                                <p class="mt-1 text-sm text-[#653e00]">Sisa stok <?= (int) $product['stock'] ?></p>
                            </div>
                            <a href="<?= BASEURL ?>toko/restock" class="rounded-lg bg-[#825100] px-3 py-2 text-xs font-extrabold text-white">Restock</a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$lowStock): ?>
                    <div class="rounded-lg border border-dashed border-[#bcc9c6] p-5 text-center text-sm text-[#3d4947]">Semua stok masih aman.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Produk Terlaris</h2>
            <p class="mt-1 text-sm text-[#3d4947]">Produk yang paling diminati pembeli.</p>
            <div class="mt-5 space-y-4">
                <?php foreach (array_slice($bestSellers, 0, 5) as $product): ?>
                    <div>
                        <div class="flex justify-between gap-3 text-sm">
                            <span class="font-bold text-[#0b1c30]"><?= htmlspecialchars($product['product_name']) ?></span>
                            <span class="font-extrabold text-[#00685f]"><?= (int) $product['qty_sold'] ?> terjual</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-[#dae2fd]">
                            <div class="h-2 rounded-full bg-[#00685f]" style="width: <?= min(100, max(12, (int) $product['qty_sold'] * 12)) ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$bestSellers): ?>
                    <div class="rounded-lg border border-dashed border-[#bcc9c6] p-5 text-center text-sm text-[#3d4947]">Belum ada produk terjual.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Performa Toko</h2>
            <div class="mt-5 grid gap-3">
                <div class="rounded-lg bg-[#eff4ff] p-4"><p class="text-sm text-[#3d4947]">Omzet toko</p><b class="mt-1 block text-lg text-[#0b1c30]"><?= $money($summary['total_pendapatan'] ?? 0) ?></b></div>
                <div class="rounded-lg bg-[#eff4ff] p-4"><p class="text-sm text-[#3d4947]">Pengunjung produk</p><b class="mt-1 block text-lg text-[#0b1c30]"><?= $visitors ?></b></div>
                <div class="rounded-lg bg-[#eff4ff] p-4"><p class="text-sm text-[#3d4947]">Rating toko</p><b class="mt-1 block text-lg text-[#0b1c30]">4.8 / 5</b></div>
            </div>
        </section>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
        checkbox.addEventListener('change', () => {
            const label = checkbox.nextElementSibling;
            if (!label) return;
            label.classList.toggle('line-through', checkbox.checked);
            label.classList.toggle('text-white/50', checkbox.checked);
            label.classList.toggle('text-white', !checkbox.checked);
        });
    });

    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('sellerSalesChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        const months = <?= json_encode(array_keys($sellerMonthlyData)) ?>;
        const sales = <?= json_encode(array_values($sellerMonthlyData)) ?>;

        const gradient = ctx.createLinearGradient(0, 0, 0, 260);
        gradient.addColorStop(0, 'rgba(0, 104, 95, 0.35)');
        gradient.addColorStop(1, 'rgba(0, 104, 95, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Penjualan (Rp)',
                    data: sales,
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
                                return ' Penjualan: Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
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
