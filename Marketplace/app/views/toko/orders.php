<?php
/** @var array $data */
$orders = $data['orders'] ?? [];
$orderItems = $data['orderItems'] ?? [];
$summary = $data['summary'] ?? [];
$money = fn ($value) => 'Rp ' . number_format((float) $value, 0, ',', '.');
$statusLabel = fn (string $status): string => match ($status) {
    'processing' => 'Pesanan Baru',
    'shipped' => 'Dalam Pengiriman',
    'completed' => 'Selesai',
    'cancelled' => 'Dibatalkan',
    default => ucfirst($status),
};
$statusClass = fn (string $status): string => match ($status) {
    'processing' => 'bg-[#ffdad6] text-[#93000a]',
    'shipped' => 'bg-[#dce9ff] text-[#565e74]',
    'completed' => 'bg-[#008378]/10 text-[#00685f]',
    'cancelled' => 'bg-[#bcc9c6]/30 text-[#3d4947]',
    default => 'bg-[#ffddb8] text-[#653e00]',
};
$paymentClass = fn (string $status): string => match ($status) {
    'paid' => 'bg-[#008378]/10 text-[#00685f]',
    'failed' => 'bg-[#ffdad6] text-[#93000a]',
    default => 'bg-[#ffddb8] text-[#653e00]',
};
$itemsByOrder = [];
foreach ($orderItems as $item) {
    $itemsByOrder[(int) $item['order_id']][] = $item;
}
$counts = [
    'all' => count($orders),
    'processing' => count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'processing')),
    'shipped' => count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'shipped')),
    'completed' => count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'completed')),
    'cancelled' => count(array_filter($orders, static fn ($order) => ($order['order_status'] ?? '') === 'cancelled')),
];
?>

<section class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0b1c30]">Kelola Pesanan</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Pantau dan proses semua pesanan pelanggan dari toko kamu.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-[#6d7a77] px-4 py-3 text-sm font-extrabold text-[#0b1c30] transition hover:bg-[#eff4ff]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
                Ekspor Laporan
            </button>
            <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-[#00685f] px-4 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak Massal
            </button>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-4 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Pesanan Baru</p>
            <p class="mt-2 text-2xl font-extrabold text-[#93000a]"><?= $counts['processing'] ?></p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-4 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Dalam Pengiriman</p>
            <p class="mt-2 text-2xl font-extrabold text-[#565e74]"><?= $counts['shipped'] ?></p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-white p-4 shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Selesai</p>
            <p class="mt-2 text-2xl font-extrabold text-[#00685f]"><?= $counts['completed'] ?></p>
        </article>
        <article class="rounded-xl border border-[#bcc9c6] bg-[#0b1c30] p-4 text-white shadow-sm">
            <p class="text-xs font-extrabold uppercase tracking-wide text-white/70">Total Pendapatan</p>
            <p class="mt-2 text-2xl font-extrabold text-[#89f5e7]"><?= $money($summary['total_pendapatan'] ?? 0) ?></p>
        </article>
    </div>

    <div class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
        <div class="flex overflow-x-auto border-b border-[#bcc9c6]">
            <?php
            $tabs = [
                'all' => 'Semua',
                'processing' => 'Pesanan Baru',
                'shipped' => 'Dalam Pengiriman',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan',
            ];
            foreach ($tabs as $key => $label):
            ?>
                <button type="button" data-order-tab="<?= $key ?>" class="order-tab whitespace-nowrap border-b-2 px-6 py-4 text-sm font-extrabold transition <?= $key === 'all' ? 'border-[#00685f] text-[#00685f]' : 'border-transparent text-[#3d4947] hover:text-[#00685f]' ?>">
                    <?= htmlspecialchars($label) ?>
                    <span class="ml-1 rounded-full <?= $key === 'processing' ? 'bg-[#ffdad6] text-[#93000a]' : 'bg-[#eff4ff] text-[#3d4947]' ?> px-2 py-0.5 text-[10px]"><?= (int) $counts[$key] ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="flex flex-col gap-4 bg-[#eff4ff]/60 p-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="relative w-full xl:max-w-md">
                <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-[#6d7a77]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>
                <input id="orderSearch" class="w-full rounded-lg border border-[#bcc9c6] bg-white py-3 pl-10 pr-4 text-sm text-[#0b1c30] outline-none transition focus:border-[#00685f] focus:ring-2 focus:ring-[#00685f]/20" placeholder="Cari No. Pesanan atau Nama Pembeli..." type="text">
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm font-bold text-[#3d4947]">
                    Urutkan:
                    <select class="rounded-lg border border-[#bcc9c6] bg-white px-3 py-2 text-sm">
                        <option>Terbaru</option>
                        <option>Terlama</option>
                        <option>Harga Tertinggi</option>
                    </select>
                </label>
                <button type="button" class="inline-flex items-center gap-2 text-sm font-extrabold text-[#00685f]">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"></path></svg>
                    Filter Lanjutan
                </button>
            </div>
        </div>
    </div>

    <div id="orderList" class="grid grid-cols-1 gap-4">
        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int) $order['id'];
            $items = $itemsByOrder[$orderId] ?? [];
            $firstItem = $items[0] ?? null;
            $itemCount = array_sum(array_map(static fn ($item) => (int) $item['qty'], $items));
            $productName = $firstItem['product_name'] ?? 'Produk toko';
            $status = (string) ($order['order_status'] ?? '');
            $payment = (string) ($order['payment_status'] ?? '');
            $created = !empty($order['created_at']) ? date('d M Y, H:i', strtotime($order['created_at'])) : '-';
            $searchText = strtolower(($order['order_code'] ?? '') . ' ' . ($order['customer_name'] ?? '') . ' ' . $productName);
            ?>
            <article class="order-card overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm transition hover:shadow-md" data-status="<?= htmlspecialchars($status) ?>" data-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                <div class="flex flex-col gap-6 p-5 lg:flex-row">
                    <div class="flex-1">
                        <div class="mb-4 flex flex-wrap items-center gap-2">
                            <span class="rounded px-3 py-1 text-[10px] font-extrabold uppercase tracking-wide <?= $statusClass($status) ?>"><?= htmlspecialchars($statusLabel($status)) ?></span>
                            <span class="text-[#bcc9c6]">|</span>
                            <span class="text-xs font-extrabold text-[#3d4947]"><?= htmlspecialchars($order['order_code']) ?></span>
                            <span class="text-[#bcc9c6]">-</span>
                            <span class="text-sm text-[#3d4947]"><?= htmlspecialchars($created) ?></span>
                            <span class="rounded px-2 py-1 text-[10px] font-extrabold uppercase <?= $paymentClass($payment) ?>"><?= htmlspecialchars($payment) ?></span>
                        </div>

                        <div class="flex gap-4">
                            <div class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-[#bcc9c6] bg-[#eff4ff] text-[#00685f]">
                                <svg class="h-9 w-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><path d="M3.3 7 12 12l8.7-5"></path><path d="M12 22V12"></path></svg>
                                <?php if (count($items) > 1): ?>
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/45 text-xs font-extrabold text-white">+<?= count($items) - 1 ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h2 class="truncate text-lg font-extrabold text-[#0b1c30]"><?= htmlspecialchars($productName) ?></h2>
                                <p class="mt-1 text-sm text-[#3d4947]"><?= max(1, $itemCount) ?> Barang<?= $firstItem ? ' x ' . $money($firstItem['price']) : '' ?></p>
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-[#3d4947]">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21a8 8 0 1 0-16 0"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <?= htmlspecialchars($order['customer_name'] ?? 'Pembeli') ?>
                                    </span>
                                    <span class="text-[#bcc9c6]">-</span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 17h4V5H2v12h3"></path><path d="M14 17h1m2 0h5v-6l-3-4h-5"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>
                                        LogistiKita - Reguler
                                    </span>
                                </div>
                                <p class="mt-2 line-clamp-1 text-sm text-[#6d7a77]"><?= htmlspecialchars($order['shipping_address']) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-between border-t border-[#bcc9c6] pt-4 lg:w-72 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        <div>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Total Pesanan</p>
                            <p class="mt-1 text-2xl font-extrabold text-[#00685f]"><?= $money($order['total']) ?></p>
                            <p class="mt-1 text-xs text-[#6d7a77]">Subtotal toko <?= $money($order['subtotal']) ?></p>
                        </div>
                        <div class="mt-4 grid gap-2">
                            <?php if ($status === 'processing'): ?>
                                <form method="post" action="<?= BASEURL ?>toko/orderStatus">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                    <input type="hidden" name="status" value="shipped">
                                    <button class="w-full rounded-lg bg-[#00685f] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#005049]">Terima & Kirim</button>
                                </form>
                                <form method="post" action="<?= BASEURL ?>toko/orderStatus" class="grid grid-cols-[1fr_auto] gap-2">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                    <input type="hidden" name="status" value="cancelled">
                                    <button class="rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm font-extrabold text-[#0b1c30] transition hover:bg-[#eff4ff]">Tolak</button>
                                    <a href="<?= BASEURL ?>toko/chat" class="inline-flex items-center justify-center rounded-lg border border-[#bcc9c6] px-4 py-3 text-[#0b1c30] transition hover:bg-[#eff4ff]" aria-label="Chat pembeli">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>
                                    </a>
                                </form>
                            <?php elseif ($status === 'shipped'): ?>
                                <form method="post" action="<?= BASEURL ?>toko/orderStatus">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>">
                                    <input type="hidden" name="status" value="completed">
                                    <button class="w-full rounded-lg bg-[#00685f] px-4 py-3 text-sm font-extrabold text-white transition hover:bg-[#005049]">Tandai Selesai</button>
                                </form>
                                <a href="<?= BASEURL ?>toko/chat" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[#bcc9c6] px-4 py-3 text-sm font-extrabold text-[#0b1c30] transition hover:bg-[#eff4ff]">
                                    Hubungi Pembeli
                                </a>
                            <?php else: ?>
                                <a href="<?= BASEURL ?>toko/chat" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-[#00685f] px-4 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Detail & Chat</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>

        <div id="ordersEmptyState" class="<?= $orders ? 'hidden' : '' ?> flex-col items-center justify-center rounded-xl border border-dashed border-[#bcc9c6] bg-white py-16 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-[#eff4ff] text-[#6d7a77]">
                <svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
            </div>
            <h2 class="mt-4 text-xl font-extrabold text-[#0b1c30]">Tidak ada pesanan</h2>
            <p class="mt-2 text-sm text-[#3d4947]">Pesanan yang cocok dengan filter akan tampil di sini.</p>
        </div>
    </div>

    <div class="flex flex-col justify-between gap-3 pb-4 text-sm text-[#3d4947] md:flex-row md:items-center">
        <p>Menampilkan <span id="visibleOrderCount"><?= count($orders) ?></span> dari <?= count($orders) ?> pesanan</p>
        <div class="flex gap-2">
            <button class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#bcc9c6] text-[#6d7a77]">&lt;</button>
            <button class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#00685f] text-sm font-extrabold text-white">1</button>
            <button class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#bcc9c6] text-sm font-bold text-[#3d4947]">2</button>
            <button class="flex h-10 w-10 items-center justify-center rounded-lg border border-[#bcc9c6] text-[#6d7a77]">&gt;</button>
        </div>
    </div>
</section>

<script>
    (() => {
        const tabs = document.querySelectorAll('[data-order-tab]');
        const cards = document.querySelectorAll('.order-card');
        const search = document.getElementById('orderSearch');
        const visibleCount = document.getElementById('visibleOrderCount');
        const emptyState = document.getElementById('ordersEmptyState');
        let activeStatus = 'all';

        function applyFilter() {
            const keyword = search.value.trim().toLowerCase();
            let shown = 0;

            cards.forEach((card) => {
                const matchesTab = activeStatus === 'all' || card.dataset.status === activeStatus;
                const matchesSearch = !keyword || card.dataset.search.includes(keyword);
                const visible = matchesTab && matchesSearch;
                card.classList.toggle('hidden', !visible);
                if (visible) shown++;
            });

            visibleCount.textContent = shown;
            emptyState.classList.toggle('hidden', shown > 0);
            emptyState.classList.toggle('flex', shown === 0);
        }

        tabs.forEach((tab) => {
            tab.addEventListener('click', () => {
                activeStatus = tab.dataset.orderTab;
                tabs.forEach((item) => {
                    item.classList.remove('border-[#00685f]', 'text-[#00685f]');
                    item.classList.add('border-transparent', 'text-[#3d4947]');
                });
                tab.classList.add('border-[#00685f]', 'text-[#00685f]');
                tab.classList.remove('border-transparent', 'text-[#3d4947]');
                applyFilter();
            });
        });

        search.addEventListener('input', applyFilter);
    })();
</script>
