<?php
/** @var array $data */
$users = $data['users'] ?? [];
$roles = $data['charts']['roles'] ?? [];
$stores = $data['stores'] ?? [];
$totalUsers = count($users);
$sellerCount = (int) ($roles['seller'] ?? 0);
$buyerCount = (int) ($roles['user'] ?? 0);
$adminCount = (int) ($roles['admin'] ?? 0);
$pendingStores = count(array_filter($stores, static fn ($store) => ($store['status'] ?? '') !== 'active'));
$activeUsers = max(0, $totalUsers - $adminCount);

$roleLabels = [
    'admin' => 'Admin',
    'seller' => 'Penjual',
    'user' => 'Pembeli',
];
$roleClasses = [
    'admin' => 'bg-slate-900 text-white',
    'seller' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
    'user' => 'bg-sky-50 text-sky-800 ring-sky-200',
];
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $first = strtoupper(substr($parts[0] ?? 'U', 0, 1));
    $last = strtoupper(substr($parts[1] ?? '', 0, 1));
    return $first . ($last ?: '');
};
$formatNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
?>

<section class="space-y-6">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-500">
                <a href="<?= BASEURL ?>admin" class="hover:text-emerald-700">Dashboard</a>
                <span>/</span>
                <span class="text-emerald-700">Manajemen Pengguna</span>
            </div>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-slate-950">Manajemen Pengguna</h1>
            <p class="mt-2 max-w-3xl text-slate-600">Pantau akun admin, pembeli, dan seller. Admin hanya monitoring data pengguna, bukan membuat akun baru.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button type="button" class="inline-flex items-center justify-center rounded-md border border-emerald-700 bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm hover:bg-emerald-50">
                Ekspor Data
            </button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-extrabold text-emerald-700">USER</span>
                <span class="text-xs font-bold text-slate-500">Database</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Pengguna</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($totalUsers) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-extrabold text-amber-700">SELLER</span>
                <span class="text-xs font-bold text-slate-500"><?= $totalUsers > 0 ? round(($sellerCount / $totalUsers) * 100) : 0 ?>%</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Penjual</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($sellerCount) ?></h2>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="rounded-lg bg-sky-50 px-3 py-2 text-sm font-extrabold text-sky-700">BUYER</span>
                <span class="text-xs font-bold text-slate-500"><?= $totalUsers > 0 ? round(($buyerCount / $totalUsers) * 100) : 0 ?>%</span>
            </div>
            <p class="mt-5 text-xs font-extrabold uppercase tracking-wide text-slate-500">Total Pembeli</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-950"><?= $formatNumber($buyerCount) ?></h2>
        </article>
        <article class="relative overflow-hidden rounded-xl bg-slate-950 p-5 text-white shadow-sm">
            <div class="relative z-10">
                <p class="text-lg font-extrabold text-emerald-200">Verifikasi Dokumen</p>
                <p class="mt-2 text-sm text-slate-300"><?= $formatNumber($pendingStores) ?> toko perlu ditinjau dari data toko saat ini.</p>
                <a href="<?= BASEURL ?>admin/stores" class="mt-5 inline-flex rounded-md bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800">Tinjau Sekarang</a>
            </div>
            <div class="absolute -bottom-8 -right-8 h-32 w-32 rounded-full bg-emerald-500/15"></div>
        </article>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-slate-200 p-5 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-1 flex-col gap-3 md:flex-row">
                <label class="relative flex-1">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-extrabold text-slate-400">CARI</span>
                    <input data-user-search class="w-full rounded-lg border border-slate-300 bg-slate-50 py-3 pl-16 pr-4 text-sm outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100" placeholder="Cari ID, nama, atau email pengguna..." type="search">
                </label>
                <select data-role-filter class="rounded-lg border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700 outline-none focus:border-emerald-700 focus:ring-2 focus:ring-emerald-100">
                    <option value="">Semua Peran</option>
                    <option value="user">Pembeli</option>
                    <option value="seller">Penjual</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="hidden items-center gap-3 rounded-lg bg-emerald-50 px-4 py-3" id="bulk-actions">
                <span class="text-sm font-extrabold text-emerald-800"><span id="selected-count">0</span> pengguna terpilih</span>
                <button type="button" class="rounded-md bg-white px-3 py-2 text-xs font-bold text-slate-700 ring-1 ring-slate-200">Tandai Review</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left">
                <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="w-12 px-5 py-4">
                            <input id="select-all" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" type="checkbox">
                        </th>
                        <th class="px-5 py-4">User ID</th>
                        <th class="px-5 py-4">Nama & Email</th>
                        <th class="px-5 py-4">Peran</th>
                        <th class="px-5 py-4">Tanggal Daftar</th>
                        <th class="px-5 py-4 text-right">Saldo</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($users as $row): ?>
                        <?php
                        $role = $row['role'] ?? 'user';
                        $search = strtolower(($row['id'] ?? '') . ' ' . ($row['name'] ?? '') . ' ' . ($row['email'] ?? '') . ' ' . $role);
                        ?>
                        <tr class="group hover:bg-slate-50" data-user-row data-role="<?= htmlspecialchars($role) ?>" data-search="<?= htmlspecialchars($search) ?>">
                            <td class="px-5 py-4">
                                <input class="row-checkbox rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" type="checkbox">
                            </td>
                            <td class="px-5 py-4 font-mono text-sm text-slate-500">PK-<?= str_pad((string) ($row['id'] ?? 0), 5, '0', STR_PAD_LEFT) ?></td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 text-sm font-extrabold text-emerald-800 ring-1 ring-emerald-100"><?= htmlspecialchars($initials($row['name'] ?? 'User')) ?></span>
                                    <div>
                                        <p class="font-extrabold text-slate-950"><?= htmlspecialchars($row['name'] ?? 'Pengguna') ?></p>
                                        <p class="text-sm text-slate-500"><?= htmlspecialchars($row['email'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1 <?= $roleClasses[$role] ?? $roleClasses['user'] ?>"><?= htmlspecialchars($roleLabels[$role] ?? ucfirst($role)) ?></span>
                            </td>
                            <td class="px-5 py-4 text-sm text-slate-600"><?= htmlspecialchars(date('d M Y', strtotime($row['created_at'] ?? 'now'))) ?></td>
                            <td class="px-5 py-4 text-right font-extrabold text-slate-950">Rp<?= number_format((float) ($row['balance'] ?? 0), 0, ',', '.') ?></td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2 opacity-100 md:opacity-0 md:transition-opacity md:group-hover:opacity-100">
                                    <?php if ($role !== 'admin'): ?>
                                        <form method="post" action="<?= BASEURL ?>admin/toggleUserStatus/<?= (int) $row['id'] ?>">
                                            <?= csrf_field() ?>
                                            <button class="rounded-md border border-slate-200 px-3 py-2 text-xs font-bold <?= ($row['status'] ?? 'active') === 'active' ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' ?>">
                                                <?= ($row['status'] ?? 'active') === 'active' ? 'Suspend' : 'Aktifkan' ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="rounded-md bg-slate-100 px-3 py-2 text-xs font-bold text-slate-500">Terkunci</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-sm text-slate-500">Belum ada data pengguna.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 md:flex-row md:items-center md:justify-between">
            <p class="text-sm font-semibold text-slate-500">Menampilkan <span data-visible-count><?= $formatNumber($totalUsers) ?></span> dari <?= $formatNumber($totalUsers) ?> pengguna</p>
            <div class="flex items-center gap-1">
                <button class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-400" disabled>&lt;</button>
                <button class="flex h-9 w-9 items-center justify-center rounded-md bg-emerald-700 text-sm font-bold text-white">1</button>
                <button class="flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-700" disabled>&gt;</button>
            </div>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
        <article class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full border border-slate-200 bg-white text-2xl font-extrabold text-emerald-700">%</div>
            <h2 class="mt-4 text-xl font-extrabold text-slate-950">Wawasan Pertumbuhan</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-slate-600">Komposisi pengguna saat ini berisi <?= $formatNumber($buyerCount) ?> pembeli dan <?= $formatNumber($sellerCount) ?> seller. Gunakan data ini untuk melihat kebutuhan aktivasi toko dan kampanye pembeli.</p>
            <a href="<?= BASEURL ?>admin/sellerFeatures" class="mt-4 inline-flex text-sm font-extrabold text-emerald-700 hover:text-emerald-900">Lihat Monitoring Seller</a>
        </article>
        <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Aktivitas Terkini</h2>
            <div class="mt-5 space-y-4">
                <?php foreach (array_slice($users, 0, 3) as $row): ?>
                    <div class="flex gap-3">
                        <span class="mt-1.5 h-2 w-2 rounded-full bg-emerald-700"></span>
                        <div>
                            <p class="text-sm text-slate-700"><b><?= htmlspecialchars($row['name']) ?></b> terdaftar sebagai <?= htmlspecialchars($roleLabels[$row['role']] ?? $row['role']) ?>.</p>
                            <p class="mt-1 text-xs font-bold uppercase text-slate-400"><?= htmlspecialchars(date('d M Y H:i', strtotime($row['created_at'] ?? 'now'))) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <p class="text-sm text-slate-500">Belum ada aktivitas pengguna.</p>
                <?php endif; ?>
            </div>
        </article>
    </div>
</section>

<script>
(() => {
    const rows = [...document.querySelectorAll('[data-user-row]')];
    const search = document.querySelector('[data-user-search]');
    const roleFilter = document.querySelector('[data-role-filter]');
    const visibleCount = document.querySelector('[data-visible-count]');
    const selectAll = document.getElementById('select-all');
    const checkboxes = [...document.querySelectorAll('.row-checkbox')];
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCount = document.getElementById('selected-count');

    const filterRows = () => {
        const query = (search?.value || '').toLowerCase().trim();
        const role = roleFilter?.value || '';
        let shown = 0;
        rows.forEach((row) => {
            const matchSearch = !query || row.dataset.search.includes(query);
            const matchRole = !role || row.dataset.role === role;
            const visible = matchSearch && matchRole;
            row.classList.toggle('hidden', !visible);
            if (visible) shown += 1;
        });
        if (visibleCount) visibleCount.textContent = shown.toLocaleString('id-ID');
    };
    search?.addEventListener('input', filterRows);
    roleFilter?.addEventListener('change', filterRows);

    const updateBulk = () => {
        const total = checkboxes.filter((checkbox) => checkbox.checked).length;
        bulkActions?.classList.toggle('hidden', total === 0);
        bulkActions?.classList.toggle('flex', total > 0);
        if (selectedCount) selectedCount.textContent = total;
    };
    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateBulk();
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateBulk));
})();
</script>
