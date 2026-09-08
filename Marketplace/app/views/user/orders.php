<?php
/** @var array $data */
$orders = $data['orders'] ?? [];
$orderItems = $data['orderItems'] ?? [];
$counts = ['all' => count($orders), 'pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($orders as $order) {
    $counts[$order['payment_status']] = ($counts[$order['payment_status']] ?? 0) + 1;
    $counts[$order['order_status']] = ($counts[$order['order_status']] ?? 0) + 1;
}

function order_badge(string $status): array
{
    return match ($status) {
        'completed' => ['Selesai', 'bg-slate-100 text-slate-700', 'Selesai'],
        'shipped' => ['Dikirim', 'bg-emerald-50 text-emerald-800', 'Estimasi sampai dalam 2-5 hari'],
        'cancelled' => ['Dibatalkan', 'bg-red-50 text-red-800', 'Pesanan dibatalkan'],
        default => ['Dikemas', 'bg-amber-50 text-amber-800', 'Penjual sedang menyiapkan pesanan'],
    };
}
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

    .orders-page {
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    .order-card {
        transition: box-shadow .2s ease, transform .2s ease;
    }

    .order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 30px -22px rgba(0, 104, 95, .35);
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>

<div class="orders-page space-y-6">
    <section>
        <h1 class="text-3xl font-extrabold text-slate-950">Pesanan Saya</h1>
        <p class="mt-2 text-sm text-slate-600">Pantau status order, pembayaran, dan proses pengiriman produk UMKM.</p>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="no-scrollbar flex gap-2 overflow-x-auto border-b border-slate-100 pb-3">
            <button class="whitespace-nowrap border-b-2 border-emerald-700 px-5 py-2 text-sm font-extrabold text-emerald-700">Semua (<?= $counts['all'] ?>)</button>
            <button class="whitespace-nowrap px-5 py-2 text-sm font-bold text-slate-500 hover:text-emerald-700">Belum Bayar (<?= $counts['pending'] ?? 0 ?>)</button>
            <button class="whitespace-nowrap px-5 py-2 text-sm font-bold text-slate-500 hover:text-emerald-700">Dikemas (<?= $counts['processing'] ?>)</button>
            <button class="whitespace-nowrap px-5 py-2 text-sm font-bold text-slate-500 hover:text-emerald-700">Dikirim (<?= $counts['shipped'] ?>)</button>
            <button class="whitespace-nowrap px-5 py-2 text-sm font-bold text-slate-500 hover:text-emerald-700">Selesai (<?= $counts['completed'] ?>)</button>
            <button class="whitespace-nowrap px-5 py-2 text-sm font-bold text-slate-500 hover:text-emerald-700">Dibatalkan (<?= $counts['cancelled'] ?>)</button>
        </div>

        <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-1 gap-2">
                <label class="relative flex-1">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold uppercase text-slate-400">Cari</span>
                    <input class="w-full rounded-xl border border-slate-300 bg-slate-50 py-3 pl-16 pr-4 text-sm focus:border-emerald-700 focus:ring-emerald-100" placeholder="Lacak pesanan atau cari kode order..." type="text">
                </label>
                <button class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-600 hover:border-emerald-700 hover:text-emerald-700">Filter</button>
            </div>
            <p class="text-sm text-slate-500">Menampilkan <b class="text-emerald-700"><?= count($orders) ?></b> pesanan</p>
        </div>
    </section>

    <?php if (!$orders): ?>
        <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-sm">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2h12l2 4v16H4V6z"/><path d="M6 6h12"/><path d="M8 11h8"/><path d="M8 15h8"/></svg>
            </div>
            <h2 class="mt-5 text-2xl font-extrabold text-slate-950">Belum Ada Pesanan</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500 max-w-md mx-auto">Anda belum memiliki riwayat belanja. Jelajahi katalog produk UMKM lokal PasarKita dan dapatkan promo menarik!</p>
            <a href="<?= BASEURL ?>user/catalog" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-6 py-3 text-sm font-extrabold text-white shadow-lg shadow-emerald-900/10 transition hover:bg-emerald-800 active:scale-95">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57L21 8H5.12"/></svg>
                Belanja Sekarang
            </a>
        </section>
    <?php else: ?>
        <section class="grid gap-5">
            <?php foreach ($orders as $order): ?>
                <?php
                [$label, $badgeClass, $note] = order_badge($order['order_status']);
                $items = $orderItems[$order['id']] ?? [];
                $firstItem = $items[0] ?? null;
                $qtyTotal = array_sum(array_map(fn ($item) => (int) $item['qty'], $items));
                ?>
                <article class="order-card overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="font-extrabold uppercase tracking-wide text-slate-950">PasarKita Order</span>
                            <span class="hidden h-4 w-px bg-slate-300 sm:block"></span>
                            <span class="text-sm font-bold text-slate-500"><?= htmlspecialchars($order['order_code']) ?></span>
                        </div>
                        <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold <?= $badgeClass ?>"><?= $label ?></span>
                    </div>

                    <div class="flex flex-col gap-5 p-5 md:flex-row">
                        <div class="h-24 w-24 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                            <?php if (!empty($firstItem['image_url'])): ?>
                                <img class="h-full w-full object-cover" src="<?= htmlspecialchars($firstItem['image_url']) ?>" alt="<?= htmlspecialchars($firstItem['product_name']) ?>">
                            <?php else: ?>
                                <div class="flex h-full w-full items-center justify-center text-xs font-bold text-slate-500">PasarKita</div>
                            <?php endif; ?>
                        </div>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-extrabold text-slate-950"><?= htmlspecialchars($firstItem['product_name'] ?? 'Order PasarKita') ?></h3>
                            <p class="mt-1 text-sm text-slate-500"><?= $qtyTotal ?> item dalam pesanan ini</p>
                            <p class="mt-3 line-clamp-1 text-sm text-slate-600"><?= htmlspecialchars($note) ?>. Dibuat pada <?= htmlspecialchars($order['created_at']) ?>.</p>
                        </div>

                        <div class="text-left md:w-48 md:text-right">
                            <p class="text-sm text-slate-500">Total Pesanan</p>
                            <p class="mt-1 text-xl font-extrabold text-emerald-700">Rp<?= number_format($order['total'], 0, ',', '.') ?></p>
                            <span class="mt-2 inline-flex rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-800"><?= htmlspecialchars($order['payment_status']) ?></span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <p class="text-sm text-slate-500">
                            <?= $order['order_status'] === 'completed' ? 'Pesanan telah selesai.' : 'Status terakhir: ' . htmlspecialchars($label) ?>
                        </p>
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="<?= BASEURL ?>user/chat" class="rounded-lg border border-emerald-700 px-5 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50">Hubungi Penjual</a>
                            <?php if ($order['payment_status'] === 'pending' && $order['order_status'] !== 'cancelled'): ?>
                                <form action="<?= BASEURL ?>user/paySmartBank/<?= (int) $order['id'] ?>" method="post" class="flex flex-wrap items-center gap-2">
                                    <?= csrf_field() ?>
                                    <div class="relative">
                                        <input name="pin" type="password" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="PIN 6-digit" class="w-36 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-mono tracking-widest text-slate-900 focus:border-emerald-700 focus:ring-emerald-100" required>
                                    </div>
                                    <button class="rounded-lg bg-emerald-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 active:scale-95">Bayar SmartBank</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($order['order_status'] !== 'completed' && $order['order_status'] !== 'cancelled'): ?>
                                <a href="<?= BASEURL ?>user/cancelOrder/<?= (int) $order['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-bold text-red-700 hover:bg-red-100">Batalkan Order</a>
                            <?php endif; ?>
                            <?php if ($order['order_status'] === 'completed'): ?>
                                <a href="<?= BASEURL ?>user/catalog" class="rounded-lg bg-emerald-700 px-6 py-2 text-sm font-bold text-white hover:bg-emerald-800">Beli Lagi</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>

        <div class="flex justify-center gap-3 pt-2">
            <button disabled class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-300">‹</button>
            <button class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-700 font-bold text-white">1</button>
            <button class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 text-slate-500 hover:bg-slate-50">›</button>
        </div>
    <?php endif; ?>
</div>
