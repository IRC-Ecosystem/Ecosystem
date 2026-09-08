<?php
/** @var array $data */
$user = $data['user'];
$orders = $data['orders'] ?? [];
$cart = $data['cart'] ?? ['items' => 0, 'total' => 0];
$vouchers = $data['vouchers'] ?? [];
$initials = strtoupper(substr($user['name'] ?? 'U', 0, 1));
$completedOrders = count(array_filter($orders, static fn($order) => ($order['status'] ?? '') === 'completed'));
$activeOrders = count(array_filter($orders, static fn($order) => in_array(($order['status'] ?? ''), ['pending', 'processing', 'shipped'], true)));
$points = 150 + ($completedOrders * 35);
$memberLevel = $points >= 750 ? 'Gold Member' : ($points >= 300 ? 'Silver Member' : 'Pembeli Setia');
$phone = trim((string) ($user['phone'] ?? ''));
$address = trim((string) ($user['address'] ?? ''));
?>

<style>
    .profile-tab-active {
        border-bottom-color: #00685f;
        color: #00685f;
        font-weight: 800;
    }
</style>

<section class="rounded-xl border border-[#bcc9c6] bg-white p-5 shadow-sm">
    <div class="grid gap-6 lg:grid-cols-[1.1fr_360px]">
        <div class="flex flex-col gap-5 md:flex-row md:items-center">
            <div class="relative mx-auto flex h-32 w-32 shrink-0 items-center justify-center rounded-full border-4 border-[#89f5e7]/40 bg-[#00685f] text-5xl font-extrabold text-white shadow-md md:mx-0">
                <?= htmlspecialchars($initials) ?>
                <button type="button" class="absolute bottom-1 right-1 flex h-10 w-10 items-center justify-center rounded-full bg-[#0b1c30] text-white shadow-lg transition hover:-translate-y-0.5" aria-label="Ubah foto profil">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                </button>
            </div>

            <div class="text-center md:text-left">
                <div class="flex flex-col items-center gap-3 md:flex-row md:items-center">
                    <h1 class="text-4xl font-extrabold text-[#0b1c30]"><?= htmlspecialchars($user['name']) ?></h1>
                    <span class="inline-flex items-center rounded-full bg-[#dae2fd] px-4 py-2 text-xs font-extrabold uppercase tracking-wide text-[#3f465c]">
                        <?= htmlspecialchars($memberLevel) ?>
                    </span>
                </div>
                <p class="mt-2 text-base text-[#3d4947]"><?= htmlspecialchars($user['email']) ?></p>
                <div class="mt-5 flex flex-wrap justify-center gap-3 md:justify-start">
                    <a href="#biodata" data-profile-target="biodata" class="rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-[#005049]">Edit Profil</a>
                    <a href="<?= BASEURL ?>/user/orders" class="rounded-lg border border-[#00685f] px-5 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Lihat Pesanan</a>
                </div>
            </div>
        </div>

        <aside class="rounded-xl bg-[#00685f] p-6 text-white">
            <p class="text-sm font-extrabold uppercase tracking-wide text-[#89f5e7]">SmartBank Balance</p>
            <p class="mt-3 text-4xl font-extrabold">Rp<?= number_format((float) $user['balance'], 0, ',', '.') ?></p>
            <p class="mt-3 text-sm leading-6 text-white/85">Saldo dipakai untuk checkout dan dicatat ke ledger transaksi PasarKita.</p>
            <div class="mt-5 grid grid-cols-2 gap-3">
                <div class="rounded-lg bg-white/10 p-3">
                    <p class="text-xs text-white/75">Keranjang</p>
                    <p class="mt-1 text-xl font-extrabold"><?= (int) ($cart['items'] ?? 0) ?> item</p>
                </div>
                <div class="rounded-lg bg-white/10 p-3">
                    <p class="text-xs text-white/75">Order Aktif</p>
                    <p class="mt-1 text-xl font-extrabold"><?= $activeOrders ?></p>
                </div>
            </div>
        </aside>
    </div>
</section>

<section class="mt-6 grid gap-4 md:grid-cols-4">
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-5">
        <p class="text-sm font-semibold text-[#3d4947]">Total Pesanan</p>
        <p class="mt-2 text-3xl font-extrabold text-[#0b1c30]"><?= count($orders) ?></p>
        <p class="mt-1 text-sm text-[#3d4947]"><?= $completedOrders ?> selesai</p>
    </article>
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-5">
        <p class="text-sm font-semibold text-[#3d4947]">Poin Pembeli</p>
        <p class="mt-2 text-3xl font-extrabold text-[#00685f]"><?= $points ?></p>
        <p class="mt-1 text-sm text-[#3d4947]">Simulasi loyalty</p>
    </article>
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-5">
        <p class="text-sm font-semibold text-[#3d4947]">Total Keranjang</p>
        <p class="mt-2 text-3xl font-extrabold text-[#825100]">Rp<?= number_format((float) ($cart['total'] ?? 0), 0, ',', '.') ?></p>
        <p class="mt-1 text-sm text-[#3d4947]"><?= (int) ($cart['items'] ?? 0) ?> produk</p>
    </article>
    <article class="rounded-xl border border-[#bcc9c6] bg-[#0b1c30] p-5 text-white">
        <p class="text-sm font-semibold text-white/75">Voucher Aktif</p>
        <p class="mt-2 text-3xl font-extrabold text-[#89f5e7]"><?= count($vouchers) ?></p>
        <p class="mt-1 text-sm text-white/75">Siap dipakai</p>
    </article>
</section>

<nav class="mt-6 flex overflow-x-auto border-b border-[#bcc9c6]">
    <button type="button" data-profile-tab="biodata" class="profile-tab profile-tab-active whitespace-nowrap border-b-2 px-6 py-4 text-sm transition">Biodata Diri</button>
    <button type="button" data-profile-tab="alamat" class="profile-tab whitespace-nowrap border-b-2 border-transparent px-6 py-4 text-sm text-[#3d4947] transition hover:text-[#00685f]">Daftar Alamat</button>
    <button type="button" data-profile-tab="keamanan" class="profile-tab whitespace-nowrap border-b-2 border-transparent px-6 py-4 text-sm text-[#3d4947] transition hover:text-[#00685f]">Keamanan</button>
    <button type="button" data-profile-tab="notifikasi" class="profile-tab whitespace-nowrap border-b-2 border-transparent px-6 py-4 text-sm text-[#3d4947] transition hover:text-[#00685f]">Notifikasi</button>
</nav>

<?php $smartBank = $data['smartBank'] ?? ['linked' => false]; $buyerLink = $_SESSION['smartbank_buyer_link'] ?? []; ?>
<section class="mt-6 rounded-xl border <?= !empty($smartBank['linked']) ? 'border-emerald-200 bg-emerald-50' : 'border-[#bcc9c6] bg-white' ?> p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-wide text-[#00685f]">SmartBank</p>
            <h2 class="mt-2 text-xl font-extrabold text-[#0b1c30]"><?= !empty($smartBank['linked']) ? 'Wallet terhubung' : 'Hubungkan wallet untuk pembayaran' ?></h2>
            <p class="mt-2 text-sm text-[#3d4947]">OTP dikirim ke Inbox SmartBank. PIN hanya diminta saat membayar order.</p>
            <?php if (!empty($smartBank['error']) && empty($smartBank['linked'])): ?><p class="mt-2 text-sm text-red-700"><?= htmlspecialchars($smartBank['error']) ?></p><?php endif; ?>
        </div>
        <span class="w-fit rounded-full px-3 py-1 text-xs font-extrabold <?= !empty($smartBank['linked']) ? 'bg-emerald-700 text-white' : 'bg-amber-100 text-amber-800' ?>"><?= !empty($smartBank['linked']) ? 'TERHUBUNG' : 'BELUM TERHUBUNG' ?></span>
    </div>
    <?php if (empty($smartBank['linked'])): ?>
        <?php if (empty($buyerLink['request_id'])): ?>
            <form action="<?= BASEURL ?>user/smartbankOtpRequest" method="post" class="mt-5"><?= csrf_field() ?><button class="rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white hover:bg-[#005049]">Kirim OTP SmartBank</button></form>
        <?php elseif (empty($buyerLink['verification_token'])): ?>
            <form action="<?= BASEURL ?>user/smartbankOtpVerify" method="post" class="mt-5 flex max-w-md gap-3"><?= csrf_field() ?><input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="OTP 6 digit" class="min-w-0 flex-1 rounded-lg border border-[#bcc9c6] px-4 py-3 font-mono" required><button class="rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white">Verifikasi</button></form>
        <?php else: ?>
            <form action="<?= BASEURL ?>user/smartbankLink" method="post" class="mt-5"><?= csrf_field() ?><button class="rounded-lg bg-[#00685f] px-5 py-3 text-sm font-extrabold text-white">Konfirmasi Hubungkan Wallet</button></form>
        <?php endif; ?>
    <?php endif; ?>
</section>

<section id="profile-panel-biodata" class="profile-panel mt-6 grid gap-6 lg:grid-cols-2">
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
        <h2 class="text-xl font-extrabold text-[#0b1c30]">Informasi Pribadi</h2>
        <div class="mt-5 space-y-4">
            <label class="block">
                <span class="text-sm font-bold text-[#3d4947]">Nama Lengkap</span>
                <input class="mt-2 w-full rounded-lg border border-[#bcc9c6] px-4 py-3 text-[#0b1c30] focus:border-[#00685f] focus:ring-[#00685f]/20" type="text" value="<?= htmlspecialchars($user['name']) ?>">
            </label>
            <label class="block">
                <span class="text-sm font-bold text-[#3d4947]">Tanggal Bergabung</span>
                <input class="mt-2 w-full rounded-lg border border-[#bcc9c6] bg-[#eff4ff] px-4 py-3 text-[#0b1c30]" type="text" value="<?= htmlspecialchars(date('d M Y', strtotime($user['created_at'] ?? 'now'))) ?>" readonly>
            </label>
            <label class="block">
                <span class="text-sm font-bold text-[#3d4947]">Status Member</span>
                <select class="mt-2 w-full rounded-lg border border-[#bcc9c6] bg-white px-4 py-3 text-[#0b1c30] focus:border-[#00685f] focus:ring-[#00685f]/20">
                    <option selected><?= htmlspecialchars($memberLevel) ?></option>
                </select>
            </label>
        </div>
    </article>

    <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
        <h2 class="text-xl font-extrabold text-[#0b1c30]">Kontak</h2>
        <div class="mt-5 space-y-4">
            <label class="block">
                <span class="text-sm font-bold text-[#3d4947]">Email</span>
                <input class="mt-2 w-full rounded-lg border border-[#bcc9c6] bg-[#eff4ff] px-4 py-3 text-[#0b1c30]" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
            </label>
            <label class="block">
                <span class="text-sm font-bold text-[#3d4947]">Nomor Telepon</span>
                <div class="mt-2 flex gap-2">
                    <span class="flex items-center rounded-lg border border-[#bcc9c6] bg-[#eff4ff] px-4 text-sm font-bold text-[#3d4947]">+62</span>
                    <input class="min-w-0 flex-1 rounded-lg border border-[#bcc9c6] px-4 py-3 text-[#0b1c30] focus:border-[#00685f] focus:ring-[#00685f]/20" type="tel" value="<?= htmlspecialchars($phone ?: '-') ?>">
                </div>
            </label>
            <div class="flex justify-end pt-2">
                <button type="button" class="rounded-lg bg-[#00685f] px-6 py-3 text-sm font-extrabold text-white transition hover:bg-[#005049]">Simpan Perubahan</button>
            </div>
        </div>
    </article>
</section>

<section id="profile-panel-alamat" class="profile-panel mt-6 hidden space-y-4">
    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
        <h2 class="text-xl font-extrabold text-[#0b1c30]">Alamat Saya</h2>
        <button type="button" class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#00685f] px-4 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"></path></svg>
            Tambah Alamat Baru
        </button>
    </div>

    <article class="relative rounded-xl border-2 border-[#00685f] bg-white p-6 shadow-sm">
        <span class="absolute right-4 top-4 rounded-full bg-[#00685f] px-3 py-1 text-xs font-extrabold uppercase tracking-wide text-white">Utama</span>
        <div class="flex gap-5">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#89f5e7] text-[#00201d]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8"></path><path d="M5 10v10h14V10"></path></svg>
            </div>
            <div class="pr-20">
                <p class="font-extrabold text-[#0b1c30]">Rumah (<?= htmlspecialchars($user['name']) ?>)</p>
                <p class="mt-1 text-sm text-[#3d4947]"><?= htmlspecialchars($phone ?: '-') ?></p>
                <p class="mt-3 text-sm leading-6 text-[#3d4947]"><?= htmlspecialchars($address ?: 'Alamat belum diisi. Tambahkan alamat agar checkout lebih cepat.') ?></p>
                <div class="mt-5 flex flex-wrap gap-3 text-sm font-extrabold">
                    <button type="button" class="text-[#00685f] hover:underline">Ubah Alamat</button>
                    <span class="text-[#bcc9c6]">|</span>
                    <button type="button" class="text-[#ba1a1a] hover:underline">Hapus</button>
                </div>
            </div>
        </div>
    </article>

    <article class="rounded-xl border border-[#bcc9c6] bg-[#eff4ff] p-6">
        <p class="font-extrabold text-[#0b1c30]">Alamat toko atau kantor belum ditambahkan</p>
        <p class="mt-2 text-sm text-[#3d4947]">Gunakan alamat tambahan untuk mempercepat pengiriman ke lokasi yang sering dipakai.</p>
    </article>
</section>

<section id="profile-panel-keamanan" class="profile-panel mt-6 hidden space-y-4">
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:items-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#89f5e7] text-[#00201d]">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="10" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <div class="flex-1">
                <h2 class="font-extrabold text-[#0b1c30]">Kata Sandi</h2>
            <p class="mt-1 text-sm text-[#3d4947]">Gunakan sandi kuat untuk menjaga akun Marketplace tetap aman.</p>
            </div>
            <button type="button" class="rounded-lg border border-[#00685f] px-5 py-3 text-sm font-extrabold text-[#00685f] transition hover:bg-[#00685f]/5">Ubah Sandi</button>
        </div>
    </article>

    <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
        <h2 class="font-extrabold text-[#0b1c30]">Akses Akun</h2>
        <div class="mt-4 space-y-3">
            <div class="flex items-center justify-between rounded-lg border border-[#bcc9c6] p-4">
                <span class="font-semibold text-[#0b1c30]">Email Login</span>
                <span class="text-sm font-extrabold text-[#00685f]">Aktif</span>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-[#bcc9c6] p-4">
                <span class="font-semibold text-[#0b1c30]">Role Akun</span>
                <span class="text-sm font-extrabold capitalize text-[#3d4947]"><?= htmlspecialchars($user['role']) ?></span>
            </div>
        </div>
    </article>
</section>

<section id="profile-panel-notifikasi" class="profile-panel mt-6 hidden">
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-6 shadow-sm">
        <h2 class="text-xl font-extrabold text-[#0b1c30]">Pengaturan Notifikasi</h2>
        <div class="mt-6 space-y-6">
            <?php
            $notifications = [
                ['title' => 'Update Pengiriman', 'desc' => 'Dapatkan notifikasi saat paket diproses, dikirim, atau selesai.', 'checked' => true],
                ['title' => 'Pembayaran', 'desc' => 'Info konfirmasi pembayaran dan perubahan status transaksi.', 'checked' => true],
                ['title' => 'Promo Khusus', 'desc' => 'Update voucher dan diskon UMKM pilihan.', 'checked' => false],
                ['title' => 'Pesan Baru', 'desc' => 'Notifikasi chat dari seller dan admin PasarKita.', 'checked' => true],
            ];
            foreach ($notifications as $notification):
            ?>
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="font-extrabold text-[#0b1c30]"><?= htmlspecialchars($notification['title']) ?></p>
                        <p class="mt-1 text-sm text-[#3d4947]"><?= htmlspecialchars($notification['desc']) ?></p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" <?= $notification['checked'] ? 'checked' : '' ?>>
                        <span class="h-6 w-11 rounded-full bg-[#bcc9c6] after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-[#00685f] peer-checked:after:translate-x-full"></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
    <article class="rounded-xl border border-[#bcc9c6] bg-white p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-extrabold text-[#0b1c30]">Level Pembeli</h2>
            <span class="rounded-lg bg-[#ffddb8] px-3 py-2 text-xs font-extrabold text-[#653e00]"><?= min(100, (int) (($points / 1000) * 100)) ?>% menuju VIP</span>
        </div>
        <div class="mt-5 flex items-center gap-5">
            <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-full bg-[#00685f] text-3xl font-extrabold text-white">L<?= $points >= 750 ? '3' : '2' ?></div>
            <div class="flex-1">
                <p class="text-2xl font-extrabold text-[#0b1c30]"><?= htmlspecialchars($memberLevel) ?></p>
                <p class="mt-2 text-sm leading-6 text-[#3d4947]">Dapatkan poin dari checkout, ulasan, dan eksplorasi katalog untuk membuka voucher tambahan.</p>
                <div class="mt-4 flex items-center justify-between text-sm font-bold text-[#0b1c30]">
                    <span>Progress Level</span>
                    <span><?= $points ?> / 1000 XP</span>
                </div>
                <div class="mt-2 h-3 rounded-full bg-[#dae2fd]">
                    <div class="h-3 rounded-full bg-[#00685f]" style="width: <?= min(100, (int) (($points / 1000) * 100)) ?>%"></div>
                </div>
            </div>
        </div>
    </article>

    <aside class="rounded-xl bg-[#00685f] p-6 text-white">
        <h2 class="text-sm font-extrabold uppercase tracking-wide text-[#89f5e7]">Voucher Pembeli</h2>
        <div class="mt-5 space-y-4">
            <?php foreach ($vouchers as $voucher): ?>
                <div class="rounded-lg bg-white/10 p-4">
                    <p class="font-extrabold text-[#89f5e7]"><?= htmlspecialchars($voucher['code']) ?></p>
                    <p class="mt-1 text-sm text-white/90"><?= htmlspecialchars($voucher['label']) ?></p>
                </div>
            <?php endforeach; ?>
            <?php if (!$vouchers): ?>
                <p class="text-sm text-white/80">Belum ada voucher aktif.</p>
            <?php endif; ?>
        </div>
    </aside>
</section>

<script>
    document.querySelectorAll('[data-profile-tab], [data-profile-target]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            const tab = trigger.dataset.profileTab || trigger.dataset.profileTarget;
            if (!tab) return;
            event.preventDefault();

            document.querySelectorAll('.profile-panel').forEach((panel) => panel.classList.add('hidden'));
            document.getElementById(`profile-panel-${tab}`)?.classList.remove('hidden');

            document.querySelectorAll('.profile-tab').forEach((button) => {
                button.classList.remove('profile-tab-active');
                button.classList.add('border-transparent', 'text-[#3d4947]');
            });

            const active = document.querySelector(`[data-profile-tab="${tab}"]`);
            active?.classList.add('profile-tab-active');
            active?.classList.remove('border-transparent', 'text-[#3d4947]');
            active?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        });
    });
</script>
