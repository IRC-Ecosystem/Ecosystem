<?php
$user = current_user();
$currentPath = trim($_GET['url'] ?? '', '/');
$currentBase = explode('/', $currentPath)[0] ?: 'home';
$roleLabel = [
    'admin' => 'Administrator',
    'seller' => 'Seller',
    'user' => 'Pembeli',
][$user['role'] ?? ''] ?? 'Guest';

$sidebarMenus = [];
if ($user) {
    $sidebarMenus = match ($user['role']) {
        'admin' => [
            ['label' => 'Dashboard', 'href' => BASEURL . 'admin', 'active' => ['admin', ''], 'icon' => 'grid'],
            ['label' => 'User', 'href' => BASEURL . 'admin/users', 'active' => ['admin/users'], 'icon' => 'users'],
            ['label' => 'Toko', 'href' => BASEURL . 'admin/stores', 'active' => ['admin/stores'], 'icon' => 'store'],
            ['label' => 'Order', 'href' => BASEURL . 'admin/orders', 'active' => ['admin/orders'], 'icon' => 'orders'],
            ['label' => 'Wallet SmartBank', 'href' => BASEURL . 'admin/smartbank', 'active' => ['admin/smartbank'], 'icon' => 'wallet'],
            ['label' => 'Fitur Penjual', 'href' => BASEURL . 'admin/sellerFeatures', 'active' => ['admin/sellerFeatures'], 'icon' => 'box'],
            ['label' => 'Analitik', 'href' => BASEURL . 'admin/analytics', 'active' => ['admin/analytics'], 'icon' => 'chart'],
            ['label' => 'Tentang', 'href' => BASEURL . 'about', 'active' => ['about'], 'icon' => 'info'],
        ],
        'seller' => [
            ['label' => 'Dashboard Toko', 'href' => BASEURL . 'toko', 'active' => ['toko', ''], 'icon' => 'grid'],
            ['label' => 'Produk', 'href' => BASEURL . 'toko/products', 'active' => ['toko/products'], 'icon' => 'box'],
            ['label' => 'Pesanan', 'href' => BASEURL . 'toko/orders', 'active' => ['toko/orders'], 'icon' => 'orders'],
            ['label' => 'Promosi', 'href' => BASEURL . 'toko/promotions', 'active' => ['toko/promotions'], 'icon' => 'tag'],
            ['label' => 'Chat Pembeli', 'href' => BASEURL . 'toko/chat', 'active' => ['toko/chat'], 'icon' => 'chat'],
            ['label' => 'Keuangan', 'href' => BASEURL . 'toko/finance', 'active' => ['toko/finance'], 'icon' => 'wallet'],
            ['label' => 'Restock', 'href' => BASEURL . 'toko/restock', 'active' => ['toko/restock'], 'icon' => 'truck'],
            ['label' => 'Performa', 'href' => BASEURL . 'toko/performance', 'active' => ['toko/performance'], 'icon' => 'chart'],
            ['label' => 'Tentang', 'href' => BASEURL . 'about', 'active' => ['about'], 'icon' => 'info'],
        ],
        default => [
            ['label' => 'Dashboard Pembeli', 'href' => BASEURL . 'user', 'active' => ['user', ''], 'icon' => 'grid'],
            ['label' => 'Katalog', 'href' => BASEURL . 'user/catalog', 'active' => ['user/catalog'], 'icon' => 'box'],
            ['label' => 'Keranjang', 'href' => BASEURL . 'user/cart', 'active' => ['user/cart'], 'icon' => 'cart'],
            ['label' => 'Checkout', 'href' => BASEURL . 'user/checkout', 'active' => ['user/checkout'], 'icon' => 'checkout'],
            ['label' => 'Order Saya', 'href' => BASEURL . 'user/orders', 'active' => ['user/orders'], 'icon' => 'orders'],
            ['label' => 'Chat', 'href' => BASEURL . 'user/chat', 'active' => ['user/chat'], 'icon' => 'chat'],
            ['label' => 'Profil', 'href' => BASEURL . 'user/profile', 'active' => ['user/profile'], 'icon' => 'users'],
            ['label' => 'Buka Toko', 'href' => BASEURL . 'toko', 'active' => ['toko'], 'icon' => 'store'],
            ['label' => 'Tentang', 'href' => BASEURL . 'about', 'active' => ['about'], 'icon' => 'info'],
        ],
    };
}

function sidebar_icon(string $name): string
{
    return match ($name) {
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'store' => '<path d="m2 7 2-4h16l2 4"/><path d="M4 7v14h16V7"/><path d="M9 21v-8h6v8"/><path d="M2 7h20"/><path d="M6 7v3a2 2 0 0 0 4 0V7"/><path d="M14 7v3a2 2 0 0 0 4 0V7"/>',
        'orders' => '<path d="M6 2h12l2 4v16H4V6z"/><path d="M6 6h12"/><path d="M8 11h8"/><path d="M8 15h8"/>',
        'box' => '<path d="m21 8-9-5-9 5 9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'cart' => '<circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h8.78a2 2 0 0 0 1.95-1.57L21 8H5.12"/>',
        'checkout' => '<path d="M20 6 9 17l-5-5"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
        'tag' => '<path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><path d="M7 7h.01"/>',
        'chat' => '<path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
        'wallet' => '<path d="M20 7H5a2 2 0 0 0 0 4h15v8H5a4 4 0 0 1 0-8h15z"/><path d="M16 14h.01"/>',
        'truck' => '<path d="M10 17h4V5H2v12h3"/><path d="M14 8h4l4 4v5h-3"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
        'chart' => '<path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        default => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
    };
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($data['title'] ?? 'PasarKita') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
<?php if ($user): ?>
<div class="min-h-screen lg:flex">
    <aside class="border-b border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:left-0 lg:w-72 lg:border-b-0 lg:border-r">
        <div class="flex items-center justify-between px-4 py-4 lg:block lg:px-6">
            <a href="<?= BASEURL . role_home($user) ?>" class="block">
                <span class="text-xl font-bold text-emerald-700">PasarKita</span>
                <span class="mt-1 block text-xs font-medium uppercase tracking-wide text-slate-500"><?= htmlspecialchars($roleLabel) ?></span>
            </a>
            <a class="rounded-md bg-slate-900 px-3 py-2 text-sm text-white lg:hidden" href="<?= BASEURL ?>auth/logout">Logout</a>
        </div>
        <nav class="flex gap-2 overflow-x-auto px-4 pb-4 text-sm lg:block lg:space-y-1 lg:px-4 lg:pb-0">
            <?php foreach ($sidebarMenus as $item): ?>
                <?php $active = in_array($currentPath, $item['active'], true); ?>
                <a href="<?= $item['href'] ?>" class="flex min-w-max items-center gap-3 rounded-md px-3 py-2 font-medium <?= $active ? 'bg-emerald-50 text-emerald-800' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-950' ?>">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= sidebar_icon($item['icon']) ?></svg>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="hidden px-4 py-6 lg:absolute lg:bottom-0 lg:block lg:w-full">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="font-semibold"><?= htmlspecialchars($user['name']) ?></p>
                <p class="mt-1 truncate text-sm text-slate-500"><?= htmlspecialchars($user['email']) ?></p>
                <a href="<?= BASEURL ?>auth/logout" class="mt-3 block rounded-md bg-slate-900 px-3 py-2 text-center text-sm font-semibold text-white">Logout</a>
            </div>
        </div>
    </aside>
    <div class="min-w-0 flex-1 lg:pl-72">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/85 px-4 py-5 backdrop-blur lg:px-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-xl font-extrabold text-slate-950">Halo, <?= htmlspecialchars(explode(' ', trim($user['name']))[0]) ?>!</h1>
                    <p class="mt-1 text-sm text-slate-600"><?= htmlspecialchars($data['title'] ?? 'PasarKita') ?> - Senang melihatmu kembali. Ada yang ingin dikelola hari ini?</p>
                </div>
                <div class="flex items-center gap-5">
                    <?php if (($user['role'] ?? '') === 'user'): ?>
                        <a href="<?= BASEURL ?>user/cart" class="relative rounded-full p-3 text-slate-950 hover:bg-slate-100" aria-label="Keranjang">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?= sidebar_icon('cart') ?></svg>
                            <?php $headerCartItems = $data['cart']['items'] ?? $data['summary']['items'] ?? []; ?>
                            <?php if (!empty($headerCartItems)): ?>
                                <span class="absolute right-1 top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white"><?= count($headerCartItems) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="hidden h-10 w-px bg-slate-300 sm:block"></div>
                    <?php endif; ?>
                    <div class="text-right">
                        <?php if (($user['role'] ?? '') === 'user' && isset($data['user']['balance'])): ?>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700">SmartBank Balance</p>
                            <p class="text-2xl font-extrabold text-slate-950">Rp<?= number_format($data['user']['balance'], 0, ',', '.') ?></p>
                        <?php else: ?>
                            <p class="text-xs font-extrabold uppercase tracking-wide text-emerald-700"><?= htmlspecialchars($roleLabel) ?></p>
                            <p class="text-2xl font-extrabold text-slate-950"><?= htmlspecialchars($data['title'] ?? ucfirst($user['role'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        <main class="px-4 py-6 pb-20 lg:px-8 lg:pb-6">
<?php else: ?>
<header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
    <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
        <a href="<?= BASEURL ?>" class="text-xl font-bold text-emerald-700">PasarKita</a>
        <div class="flex items-center gap-3 text-sm">
            <a class="hover:text-emerald-700" href="<?= BASEURL ?>about">Tentang</a>
            <a class="hover:text-emerald-700" href="<?= BASEURL ?>auth/login">Login</a>
            <a class="rounded-md bg-emerald-700 px-3 py-2 text-white" href="<?= BASEURL ?>auth/register">Register</a>
        </div>
    </nav>
</header>
<main class="<?= $currentBase === 'home' ? 'px-0 py-0' : 'mx-auto max-w-7xl px-4 py-8' ?>">
<?php endif; ?>

<!-- Floating Toast Container -->
<div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3 max-w-md w-full pointer-events-none">
    <?php if ($message = flash('success')): ?>
        <div class="toast-item pointer-events-auto flex items-center gap-3 rounded-xl border border-emerald-200 bg-white p-4 shadow-xl text-slate-900 transition-all transform translate-y-0 duration-300">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="flex-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars($message) ?></div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-700">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($message = flash('error')): ?>
        <div class="toast-item pointer-events-auto flex items-center gap-3 rounded-xl border border-red-200 bg-white p-4 shadow-xl text-slate-900 transition-all transform translate-y-0 duration-300">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </div>
            <div class="flex-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars($message) ?></div>
            <button onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-700">&times;</button>
        </div>
    <?php endif; ?>
</div>

<script>
    setTimeout(() => {
        document.querySelectorAll('.toast-item').forEach(toast => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-12px)';
            setTimeout(() => toast.remove(), 300);
        });
    }, 4000);
</script>
