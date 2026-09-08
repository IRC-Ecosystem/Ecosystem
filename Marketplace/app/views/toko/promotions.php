<?php
/** @var array $data */
$promotions = $data['promotions'] ?? [];
$products = $data['products'] ?? [];
$activePromos = count(array_filter($promotions, static fn ($promo) => in_array(($promo['status'] ?? ''), ['aktif', 'active'], true)));
$voucherCount = count(array_filter($promotions, static fn ($promo) => stripos((string) ($promo['type'] ?? ''), 'voucher') !== false));
$reach = max(4250, count($products) * 850 + count($promotions) * 1200);
$used = max(24, $activePromos * 128 + $voucherCount * 42);
$cost = max(150000, count($promotions) * 275000);
$statusClass = static fn (string $status): string => match ($status) {
    'aktif', 'active' => 'text-[#00685f]',
    'draft' => 'text-[#565e74]',
    'mendatang' => 'text-[#825100]',
    default => 'text-[#3d4947]',
};
$typeClass = static fn (string $type): string => match (strtolower($type)) {
    'voucher' => 'bg-[#e5eeff] text-[#3f465c]',
    'flash sale' => 'bg-[#ffdad6] text-[#93000a]',
    'ongkir' => 'bg-[#ffddb8] text-[#653e00]',
    default => 'bg-[#eff4ff] text-[#3d4947]',
};
$iconForType = static fn (string $type): string => match (strtolower($type)) {
    'flash sale' => '<path d="m13 2-10 12h8l-1 8 10-12h-8l1-8Z"></path>',
    'ongkir' => '<path d="M10 17h4V5H2v12h3"></path><path d="M14 17h1m2 0h5v-6l-3-4h-5"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle>',
    default => '<path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9Z"></path><path d="M9 9h.01M15 15h.01M16 8l-8 8"></path>',
};
?>

<style>
    .promo-drawer {
        transition: opacity 180ms ease;
    }
    .promo-drawer[aria-hidden="true"] {
        opacity: 0;
        pointer-events: none;
    }
    .promo-drawer[aria-hidden="true"] .promo-drawer-panel {
        transform: translateX(24px);
    }
</style>

<section class="space-y-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <p class="text-sm font-extrabold uppercase tracking-wide text-[#00685f]">Promosi Seller</p>
            <h1 class="mt-2 text-3xl font-extrabold text-[#0b1c30]">Promosi Toko</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Kelola voucher toko, diskon produk, flash sale, dan produk unggulan untuk meningkatkan penjualan.</p>
        </div>
        <button type="button" data-promo-open="create" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049] active:scale-95">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            Buat Promosi Baru
        </button>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-[#bcc9c6] bg-white/90 p-5 shadow-sm">
            <div class="flex justify-between gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#00685f]/10 text-[#00685f]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </span>
                <span class="text-xs font-extrabold text-[#00685f]">+12%</span>
            </div>
            <p class="mt-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Total Jangkauan</p>
            <h2 class="mt-1 text-3xl font-extrabold text-[#0b1c30]"><?= number_format($reach, 0, ',', '.') ?></h2>
            <p class="mt-1 text-sm text-[#6d7a77]">Tayangan promosi 30 hari terakhir</p>
        </article>

        <article class="rounded-xl border border-[#bcc9c6] border-l-4 border-l-[#825100] bg-white/90 p-5 shadow-sm">
            <div class="flex justify-between gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#825100]/10 text-[#825100]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>
                </span>
                <span class="text-xs font-extrabold text-[#00685f]">+5.4%</span>
            </div>
            <p class="mt-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Tingkat Konversi</p>
            <h2 class="mt-1 text-3xl font-extrabold text-[#0b1c30]">8.2%</h2>
            <p class="mt-1 text-sm text-[#6d7a77]">Klik promosi menjadi pesanan</p>
        </article>

        <article class="rounded-xl border border-[#bcc9c6] bg-white/90 p-5 shadow-sm">
            <div class="flex justify-between gap-4">
                <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-[#dae2fd] text-[#3f465c]">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </span>
                <span class="text-xs font-extrabold text-[#ba1a1a]">-2.1%</span>
            </div>
            <p class="mt-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Biaya Promosi</p>
            <h2 class="mt-1 text-3xl font-extrabold text-[#0b1c30]">Rp <?= number_format($cost, 0, ',', '.') ?></h2>
            <p class="mt-1 text-sm text-[#6d7a77]">Investasi pemasaran bulan ini</p>
        </article>

        <article class="rounded-xl bg-[#008378] p-5 text-white shadow-sm">
            <span class="flex h-11 w-11 items-center justify-center rounded-lg bg-white/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9Z"></path></svg>
            </span>
            <p class="mt-4 text-xs font-extrabold uppercase tracking-wide text-white/80">Voucher Terpakai</p>
            <h2 class="mt-1 text-3xl font-extrabold"><?= number_format($used, 0, ',', '.') ?></h2>
            <p class="mt-1 text-sm text-white/80">Voucher aktif yang telah diklaim</p>
        </article>
    </div>

    <section class="space-y-4">
        <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Promosi Berjalan</h2>
            <button type="button" class="text-sm font-extrabold text-[#00685f] hover:underline">Lihat Semua Riwayat</button>
        </div>

        <div class="overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-[#bcc9c6] bg-[#eff4ff]">
                        <tr>
                            <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Detail Kampanye</th>
                            <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Tipe</th>
                            <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Status</th>
                            <th class="px-6 py-4 text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Periode</th>
                            <th class="px-6 py-4 text-right text-xs font-extrabold uppercase tracking-wide text-[#3d4947]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="promoRows" class="divide-y divide-[#bcc9c6]">
                        <?php foreach ($promotions as $index => $promo): ?>
                            <?php
                            $type = (string) ($promo['type'] ?? 'Voucher');
                            $status = (string) ($promo['status'] ?? 'draft');
                            $value = (string) ($promo['value'] ?? '');
                            $period = $promo['period'] ?? ($status === 'draft' ? 'Belum dijadwalkan' : '01 Jan - 31 Des 2024');
                            ?>
                            <tr class="promo-row transition hover:bg-[#f8f9ff]">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded bg-[#00685f]/10 text-[#00685f]">
                                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $iconForType($type) ?></svg>
                                        </div>
                                        <div>
                                            <p class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($promo['name']) ?></p>
                                            <p class="mt-1 text-sm text-[#6d7a77]"><?= htmlspecialchars($value) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="rounded px-3 py-1 text-xs font-extrabold <?= $typeClass($type) ?>"><?= htmlspecialchars($type) ?></span></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 font-extrabold <?= $statusClass($status) ?>">
                                        <span class="h-2 w-2 rounded-full bg-current <?= in_array($status, ['aktif', 'active'], true) ? 'animate-pulse' : '' ?>"></span>
                                        <span class="text-xs uppercase"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-[#3d4947]"><?= htmlspecialchars($period) ?></td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button"
                                        class="rounded p-2 text-[#6d7a77] transition hover:bg-[#00685f]/10 hover:text-[#00685f]"
                                        data-promo-open="edit"
                                        data-row-index="<?= (int) $index ?>"
                                        data-name="<?= htmlspecialchars($promo['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        data-type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>"
                                        data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                        data-value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"
                                        data-period="<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Edit promosi">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                    </button>
                                    <button type="button" class="rounded p-2 text-[#6d7a77] transition hover:bg-[#ffdad6] hover:text-[#ba1a1a]" data-promo-delete aria-label="Hapus promosi">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4h8v2"></path><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6M14 11v6"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col justify-between gap-3 border-t border-[#bcc9c6] bg-[#eff4ff] px-6 py-4 text-sm text-[#6d7a77] md:flex-row md:items-center">
                <p>Menampilkan <span id="promoCount"><?= count($promotions) ?></span> dari <?= max(count($promotions), 12) ?> promosi</p>
                <div class="flex gap-2">
                    <button class="flex h-8 w-8 items-center justify-center rounded border border-[#bcc9c6]">&lt;</button>
                    <button class="flex h-8 w-8 items-center justify-center rounded bg-[#00685f] text-xs font-extrabold text-white">1</button>
                    <button class="flex h-8 w-8 items-center justify-center rounded border border-[#bcc9c6] text-xs font-bold">2</button>
                    <button class="flex h-8 w-8 items-center justify-center rounded border border-[#bcc9c6]">&gt;</button>
                </div>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <article class="relative overflow-hidden rounded-2xl bg-[#0b1c30] p-8 text-white md:col-span-2">
            <div class="relative z-10">
                <h2 class="text-3xl font-extrabold">Tingkatkan Penjualan hingga 2x Lipat!</h2>
                <p class="mt-4 max-w-xl text-sm leading-6 text-white/80">Berdasarkan data toko, pembeli cenderung berbelanja lebih banyak saat akhir pekan. Gunakan voucher terbatas pada hari Jumat untuk memaksimalkan traffic.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="button" data-promo-open="create" class="rounded-lg bg-white px-5 py-3 text-sm font-extrabold text-[#0b1c30]">Buat Voucher Jumat</button>
                    <button type="button" class="rounded-lg border border-white/30 px-5 py-3 text-sm font-extrabold text-white">Nanti Saja</button>
                </div>
            </div>
        </article>
        <article class="rounded-2xl bg-[#a36700] p-6 text-white">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white/20">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18h6"></path><path d="M10 22h4"></path><path d="M12 2a7 7 0 0 0-4 12c.6.6 1 1.3 1 2h6c0-.7.4-1.4 1-2A7 7 0 0 0 12 2z"></path></svg>
            </div>
            <h3 class="mt-4 text-xl font-extrabold">Ide Promosi Hari Ini</h3>
            <ul class="mt-4 space-y-3 text-sm leading-6">
                <li>Aktifkan combo hemat untuk produk terlaris.</li>
                <li>Berikan voucher pengikut baru.</li>
                <li>Jadikan SKU stok tinggi sebagai flash sale.</li>
            </ul>
        </article>
    </section>
</section>

<div id="promoDrawer" class="promo-drawer fixed inset-0 z-[70] bg-[#0b1c30]/45" aria-hidden="true">
    <div class="absolute inset-y-0 right-0 flex w-full justify-end">
        <form id="promoForm" class="promo-drawer-panel flex h-full w-full max-w-5xl flex-col overflow-y-auto bg-[#f8f9ff] shadow-2xl transition-transform">
            <input type="hidden" id="promoEditIndex" value="">
            <div class="sticky top-0 z-10 flex items-center justify-between border-b border-[#bcc9c6] bg-white px-6 py-5">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-wide text-[#00685f]">Promosi Seller</p>
                    <h2 id="promoFormTitle" class="text-2xl font-extrabold text-[#0b1c30]">Buat Promosi Baru</h2>
                </div>
                <button type="button" data-promo-close class="rounded-lg border border-[#bcc9c6] p-2 text-[#3d4947] transition hover:bg-[#eff4ff]" aria-label="Tutup form">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="grid gap-6 p-6 lg:grid-cols-12">
                <div class="space-y-6 lg:col-span-8">
                    <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                        <h3 class="mb-4 flex items-center gap-2 text-xl font-extrabold text-[#0b1c30]">
                            <svg class="h-5 w-5 text-[#00685f]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9Z"></path></svg>
                            1. Jenis Promosi
                        </h3>
                        <div class="grid grid-cols-2 gap-3 rounded-lg border border-[#bcc9c6] bg-[#eff4ff] p-1">
                            <button type="button" data-promo-type="Voucher" class="promo-type-btn rounded-md border border-[#00685f]/20 bg-white py-4 text-sm font-extrabold text-[#00685f] shadow-sm">Voucher Toko</button>
                            <button type="button" data-promo-type="Flash Sale" class="promo-type-btn rounded-md py-4 text-sm font-extrabold text-[#3d4947] transition hover:bg-white">Flash Sale Toko</button>
                        </div>
                        <input type="hidden" id="promoType" value="Voucher">
                    </section>

                    <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-xl font-extrabold text-[#0b1c30]">2. Informasi Dasar</h3>
                        <div class="space-y-4">
                            <label class="block">
                                <span class="text-sm font-extrabold text-[#3d4947]">Nama Promosi</span>
                                <input id="promoName" required class="mt-2 w-full rounded-lg border border-[#bcc9c6] p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="Contoh: Promo Gajian Mantap" type="text">
                                <span class="mt-1 block text-xs italic text-[#6d7a77]">Nama promosi hanya terlihat oleh seller.</span>
                            </label>
                            <label class="block">
                                <span class="text-sm font-extrabold text-[#3d4947]">Kode Voucher</span>
                                <div class="mt-2 flex">
                                    <span class="rounded-l-lg border border-r-0 border-[#bcc9c6] bg-[#dce9ff] px-4 py-3 text-sm font-extrabold text-[#3d4947]">PK-</span>
                                    <input id="promoCode" class="w-full rounded-r-lg border border-[#bcc9c6] p-3 text-sm uppercase focus:border-[#00685f] focus:ring-[#00685f]/20" placeholder="XXXXXX" type="text" maxlength="12">
                                </div>
                            </label>
                        </div>
                    </section>

                    <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-xl font-extrabold text-[#0b1c30]">3. Periode Promosi</h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">Waktu Mulai</span>
                                <input id="promoStart" class="mt-2 w-full rounded-lg border border-[#bcc9c6] p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" type="datetime-local">
                            </label>
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">Waktu Berakhir</span>
                                <input id="promoEnd" class="mt-2 w-full rounded-lg border border-[#bcc9c6] p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" type="datetime-local">
                            </label>
                        </div>
                    </section>

                    <section class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-xl font-extrabold text-[#0b1c30]">4. Detail Diskon & Kuota</h3>
                        <div class="grid gap-4 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">Tipe Diskon</span>
                                <select id="discountType" class="mt-2 w-full rounded-lg border border-[#bcc9c6] bg-white p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20">
                                    <option value="percent">Persentase (%)</option>
                                    <option value="nominal">Nominal (Rp)</option>
                                </select>
                            </label>
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">Nilai Diskon</span>
                                <input id="discountValue" class="mt-2 w-full rounded-lg border border-[#bcc9c6] p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" type="number" value="10">
                            </label>
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">Minimum Belanja (Rp)</span>
                                <input id="minimumSpend" class="mt-2 w-full rounded-lg border border-[#bcc9c6] p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" type="number" value="50000">
                            </label>
                            <label>
                                <span class="text-sm font-extrabold text-[#3d4947]">Kuota Pemakaian</span>
                                <input id="promoQuota" class="mt-2 w-full rounded-lg border border-[#bcc9c6] p-3 text-sm focus:border-[#00685f] focus:ring-[#00685f]/20" type="number" value="100">
                            </label>
                        </div>
                    </section>
                </div>

                <aside class="lg:col-span-4">
                    <div class="sticky top-24 overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm">
                        <div class="flex items-center justify-between bg-[#00685f] p-4">
                            <span class="text-sm font-extrabold text-white">PREVIEW VOUCHER</span>
                            <span class="text-white/60">Preview</span>
                        </div>
                        <div class="p-6">
                            <div class="relative flex aspect-[2/1] overflow-hidden rounded-xl bg-[#008378] shadow-md">
                                <div class="relative flex w-1/3 flex-col items-center justify-center border-r-2 border-dashed border-white/30 bg-[#00685f] text-white">
                                    <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9Z"></path></svg>
                                    <p id="previewDiscount" class="mt-1 text-2xl font-extrabold">10%</p>
                                    <p class="text-[10px] font-bold uppercase text-white/80">Potongan</p>
                                </div>
                                <div class="flex w-2/3 flex-col justify-center bg-white p-4">
                                    <p class="text-xs font-extrabold uppercase text-[#0b1c30]">Voucher Belanja</p>
                                    <p id="previewCode" class="mt-1 text-xl font-extrabold text-[#00685f]">PK-NEWBIE</p>
                                    <p class="mt-2 text-[10px] text-[#6d7a77]">Berlaku untuk promo toko</p>
                                    <button type="button" class="mt-2 rounded-lg border border-[#00685f]/20 bg-[#00685f]/10 py-2 text-[10px] font-extrabold uppercase text-[#00685f]">Pakai Sekarang</button>
                                </div>
                            </div>

                            <div class="mt-6 space-y-3">
                                <h3 class="font-extrabold text-[#0b1c30]">Detail Aturan:</h3>
                                <p class="text-sm text-[#3d4947]">Minimal belanja <b id="previewMin">Rp50.000</b></p>
                                <p class="text-sm text-[#3d4947]">Berlaku untuk <b>Semua Produk</b></p>
                                <p class="text-sm text-[#3d4947]">Kuota terbatas <b id="previewQuota">100 pengguna</b></p>
                            </div>
                            <div class="mt-6 rounded-lg border border-[#825100]/20 bg-[#ffddb8] p-4 text-sm italic text-[#653e00]">Tip: voucher diskon 10-15% biasanya lebih mudah diklaim pembeli baru.</div>
                        </div>
                    </div>
                </aside>
            </div>

            <div class="sticky bottom-0 flex flex-col justify-end gap-3 border-t border-[#bcc9c6] bg-white/90 px-6 py-4 backdrop-blur sm:flex-row">
                <button type="button" data-promo-close class="rounded-lg border border-[#00685f] px-6 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Batal</button>
                <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00685f] px-8 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049]">
                    <span id="promoSubmitText">Aktifkan Promosi</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4.5 16.5c-1.5 1.26-2 5-2 5s3.74-.5 5-2c.71-.84.7-2.13-.09-2.91a2.18 2.18 0 0 0-2.91-.09z"></path><path d="m12 15-3-3a22 22 0 0 1 2-3.95A12.88 12.88 0 0 1 22 2c0 2.72-.78 7.5-6 11a22 22 0 0 1-4 2z"></path></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const drawer = document.getElementById('promoDrawer');
        const form = document.getElementById('promoForm');
        const title = document.getElementById('promoFormTitle');
        const submitText = document.getElementById('promoSubmitText');
        const typeInput = document.getElementById('promoType');
        const editIndex = document.getElementById('promoEditIndex');
        const nameInput = document.getElementById('promoName');
        const codeInput = document.getElementById('promoCode');
        const discountType = document.getElementById('discountType');
        const discountValue = document.getElementById('discountValue');
        const minimumSpend = document.getElementById('minimumSpend');
        const quota = document.getElementById('promoQuota');
        const previewDiscount = document.getElementById('previewDiscount');
        const previewCode = document.getElementById('previewCode');
        const previewMin = document.getElementById('previewMin');
        const previewQuota = document.getElementById('previewQuota');
        const rows = document.getElementById('promoRows');
        const promoCount = document.getElementById('promoCount');

        function formatCurrency(value) {
            return 'Rp' + Number(value || 0).toLocaleString('id-ID');
        }

        function setType(type) {
            typeInput.value = type;
            document.querySelectorAll('.promo-type-btn').forEach((button) => {
                const active = button.dataset.promoType === type;
                button.classList.toggle('bg-white', active);
                button.classList.toggle('text-[#00685f]', active);
                button.classList.toggle('border', active);
                button.classList.toggle('border-[#00685f]/20', active);
                button.classList.toggle('shadow-sm', active);
                button.classList.toggle('text-[#3d4947]', !active);
            });
            updatePreview();
        }

        function updatePreview() {
            const value = discountValue.value || 0;
            previewDiscount.textContent = discountType.value === 'nominal' ? formatCurrency(value).replace('Rp', 'Rp ') : value + '%';
            previewCode.textContent = 'PK-' + (codeInput.value.trim().toUpperCase() || 'NEWBIE');
            previewMin.textContent = formatCurrency(minimumSpend.value || 0);
            previewQuota.textContent = (quota.value || 0) + ' pengguna';
        }

        function openDrawer(mode, trigger) {
            form.reset();
            setType('Voucher');
            editIndex.value = '';
            title.textContent = 'Buat Promosi Baru';
            submitText.textContent = 'Aktifkan Promosi';
            discountValue.value = 10;
            minimumSpend.value = 50000;
            quota.value = 100;

            if (mode === 'edit') {
                editIndex.value = trigger.dataset.rowIndex || '';
                title.textContent = 'Edit Promosi';
                submitText.textContent = 'Update Promosi';
                nameInput.value = trigger.dataset.name || '';
                setType(trigger.dataset.type || 'Voucher');
                codeInput.value = (trigger.dataset.name || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
                discountValue.value = (trigger.dataset.value || '10').match(/\d+/)?.[0] || 10;
            }

            updatePreview();
            drawer.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function closeDrawer() {
            drawer.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }

        function rowHtml(name, type, value) {
            const safeName = name.replace(/[&<>"']/g, (char) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
            const safeType = type.replace(/[&<>"']/g, (char) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
            const safeValue = value.replace(/[&<>"']/g, (char) => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
            return `
                <tr class="promo-row transition hover:bg-[#f8f9ff]">
                    <td class="px-6 py-4"><div class="flex items-center gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded bg-[#00685f]/10 text-[#00685f]"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9Z"></path></svg></div><div><p class="font-extrabold text-[#0b1c30]">${safeName}</p><p class="mt-1 text-sm text-[#6d7a77]">${safeValue}</p></div></div></td>
                    <td class="px-6 py-4"><span class="rounded bg-[#e5eeff] px-3 py-1 text-xs font-extrabold text-[#3f465c]">${safeType}</span></td>
                    <td class="px-6 py-4"><div class="flex items-center gap-2 font-extrabold text-[#00685f]"><span class="h-2 w-2 rounded-full bg-current animate-pulse"></span><span class="text-xs uppercase">aktif</span></div></td>
                    <td class="px-6 py-4 text-[#3d4947]">Baru dibuat</td>
                    <td class="px-6 py-4 text-right"><button type="button" class="rounded p-2 text-[#6d7a77] transition hover:bg-[#00685f]/10 hover:text-[#00685f]" data-promo-open="edit" data-name="${safeName}" data-type="${safeType}" data-value="${safeValue}" data-status="aktif" data-period="Baru dibuat" aria-label="Edit promosi">Edit</button><button type="button" class="rounded p-2 text-[#6d7a77] transition hover:bg-[#ffdad6] hover:text-[#ba1a1a]" data-promo-delete>Hapus</button></td>
                </tr>`;
        }

        document.querySelectorAll('[data-promo-open]').forEach((button) => {
            button.addEventListener('click', () => openDrawer(button.dataset.promoOpen, button));
        });

        document.querySelectorAll('[data-promo-close]').forEach((button) => button.addEventListener('click', closeDrawer));
        drawer.addEventListener('click', (event) => {
            if (event.target === drawer) closeDrawer();
        });

        document.querySelectorAll('.promo-type-btn').forEach((button) => {
            button.addEventListener('click', () => setType(button.dataset.promoType));
        });

        [codeInput, discountType, discountValue, minimumSpend, quota].forEach((input) => input.addEventListener('input', updatePreview));
        discountType.addEventListener('change', updatePreview);

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const type = typeInput.value;
            const name = nameInput.value.trim() || 'Promosi Baru';
            const value = discountType.value === 'nominal' ? 'Potongan ' + formatCurrency(discountValue.value) : 'Diskon ' + discountValue.value + '%';
            const index = editIndex.value;

            if (index !== '' && rows.children[Number(index)]) {
                rows.children[Number(index)].outerHTML = rowHtml(name, type, value);
            } else {
                rows.insertAdjacentHTML('afterbegin', rowHtml(name, type, value));
            }
            promoCount.textContent = rows.querySelectorAll('tr').length;
            closeDrawer();
        });

        rows.addEventListener('click', (event) => {
            const deleteButton = event.target.closest('[data-promo-delete]');
            if (deleteButton) {
                deleteButton.closest('tr')?.remove();
                promoCount.textContent = rows.querySelectorAll('tr').length;
            }
            const editButton = event.target.closest('[data-promo-open="edit"]');
            if (editButton) openDrawer('edit', editButton);
        });
    })();
</script>
