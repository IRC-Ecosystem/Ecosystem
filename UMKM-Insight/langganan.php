<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$subscriptionData = (new App\Controllers\SubscriptionController($pdo))->client($_SESSION, $_FILES);
extract($subscriptionData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px;">
        <div style="margin-bottom:24px;">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 dark:text-white mb-2">Tagihan & Langganan</h1>
            <p class="text-slate-500 dark:text-slate-400">Kelola paket langganan dan unggah bukti pembayaran Anda.</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-xl mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 p-4 rounded-xl mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Paket Saat Ini -->
            <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 p-8">
                <h3 class="text-lg font-bold mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">Status Paket Anda</h3>
                
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <p class="text-sm text-slate-500">Tier Saat Ini</p>
                        <p class="text-2xl font-black <?php echo $user['tier'] == 'premium' ? 'text-amber-500' : 'text-emerald-500'; ?> uppercase">
                            <?php echo $user['tier']; ?>
                        </p>
                    </div>
                    <?php if ($user['tier'] == 'premium'): ?>
                    <div class="text-right">
                        <p class="text-sm text-slate-500">Berlaku Hingga</p>
                        <p class="text-lg font-bold text-slate-700 dark:text-slate-200"><?php echo date('d M Y', strtotime($user['tier_expiry'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800 p-4 rounded-xl mb-6">
                    <h4 class="font-bold text-sm mb-2">Instruksi Pembayaran</h4>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mb-2">
                        Silakan transfer sebesar <strong>Rp 99.000</strong> ke rekening SmartBank berikut:
                    </p>
                    <p class="font-mono text-lg font-bold text-brand-600 mb-2">SB-0000-999 (PT UMKM Insight)</p>
                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        Pastikan Anda mentransfer dari rekening SmartBank yang telah terdaftar agar proses verifikasi lebih cepat.
                    </p>
                </div>

                <div class="mb-4">
                    <a href="pembayaran.php" class="btn bg-blue-600 hover:bg-blue-700 text-white w-full shadow-lg">
                        <i class="ph-bold ph-bank"></i> Bayar Instan dengan SmartBank
                    </a>
                </div>

                <div class="relative flex py-4 items-center">
                    <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                    <span class="flex-shrink-0 mx-4 text-slate-400 text-xs">ATAU TRANSFER MANUAL</span>
                    <div class="flex-grow border-t border-slate-200 dark:border-slate-700"></div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="mt-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Unggah Bukti Pembayaran (Screenshot)</label>
                        <input type="file" name="proof_image" required accept="image/png, image/jpeg, application/pdf" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                    </div>
                    <button type="submit" class="btn btn-outline w-full">Kirim Bukti Pembayaran</button>
                </form>
            </div>

            <!-- Riwayat Pembayaran -->
            <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 p-8">
                <h3 class="text-lg font-bold mb-4 border-b border-slate-100 dark:border-slate-700 pb-2">Riwayat Pengajuan</h3>
                
                <?php if (empty($payments)): ?>
                    <p class="text-sm text-slate-500 text-center py-4">Belum ada riwayat pembayaran.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($payments as $p): ?>
                            <div class="flex items-center justify-between p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-slate-100 dark:bg-slate-800 rounded-lg flex items-center justify-center">
                                        <i class="ph-bold ph-receipt text-slate-500"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold">Bukti #<?php echo $p['id']; ?></p>
                                        <p class="text-xs text-slate-500"><?php echo date('d M Y, H:i', strtotime($p['created_at'])); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <?php if ($p['status'] == 'pending'): ?>
                                        <span class="badge bg-amber-100 text-amber-700 text-xs">Menunggu</span>
                                    <?php elseif ($p['status'] == 'approved'): ?>
                                        <span class="badge bg-emerald-100 text-emerald-700 text-xs">Disetujui</span>
                                    <?php else: ?>
                                        <span class="badge bg-rose-100 text-rose-700 text-xs">Ditolak</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
