<?php $smartBank = $data['smartBank'] ?? []; ?>
<section class="mx-auto max-w-3xl space-y-6">
    <div>
        <p class="text-sm font-extrabold uppercase tracking-wide text-emerald-700">SmartBank</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-950">Wallet Penerima Marketplace</h1>
        <p class="mt-2 text-sm text-slate-600">Dana pembayaran masuk ke wallet treasury Marketplace. Pencairan seller tetap dikelola terpisah.</p>
    </div>
    <article class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 md:flex-row md:justify-between">
            <div>
                <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">External ID</p>
                <code class="mt-2 block text-sm font-bold text-slate-950"><?= htmlspecialchars(SMARTBANK_MARKETPLACE_EXTERNAL_ID) ?></code>
                <p class="mt-4 text-sm text-slate-600"><?= !empty($smartBank['linked']) ? 'Wallet penerima sudah aktif.' : 'Tautkan akun SmartBank treasury dengan OTP.' ?></p>
                <?php if (!empty($smartBank['error']) && empty($smartBank['linked'])): ?><p class="mt-2 text-sm text-red-700"><?= htmlspecialchars($smartBank['error']) ?></p><?php endif; ?>
            </div>
            <span class="h-fit rounded-full px-3 py-1 text-xs font-extrabold <?= !empty($smartBank['linked']) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>"><?= !empty($smartBank['linked']) ? 'TERHUBUNG' : 'BELUM TERHUBUNG' ?></span>
        </div>
        <?php if (empty($smartBank['linked'])): ?>
            <?php if (empty($smartBank['request_id'])): ?>
                <form action="<?= BASEURL ?>admin/smartbankOtpRequest" method="post" class="mt-6 flex max-w-xl flex-col gap-3 sm:flex-row"><?= csrf_field() ?><input name="phone" type="tel" placeholder="Nomor SmartBank treasury" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3" required><button class="rounded-lg bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white">Kirim OTP</button></form>
            <?php elseif (empty($smartBank['verified'])): ?>
                <form action="<?= BASEURL ?>admin/smartbankOtpVerify" method="post" class="mt-6 flex max-w-xl gap-3"><?= csrf_field() ?><input name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="OTP 6 digit" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3 font-mono" required><button class="rounded-lg bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white">Verifikasi</button></form>
            <?php else: ?>
                <form action="<?= BASEURL ?>admin/smartbankLink" method="post" class="mt-6"><?= csrf_field() ?><button class="rounded-lg bg-emerald-700 px-5 py-3 text-sm font-extrabold text-white">Tetapkan Wallet Penerima</button></form>
            <?php endif; ?>
        <?php endif; ?>
    </article>
</section>
