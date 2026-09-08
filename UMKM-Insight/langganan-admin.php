<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('operator');

$subscriptionAdminData = (new App\Controllers\SubscriptionController($pdo))->admin($_POST);
extract($subscriptionAdminData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px;">
        <div style="margin-bottom:24px;">
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-800 dark:text-white mb-2">Verifikasi Tagihan</h1>
            <p class="text-slate-500 dark:text-slate-400">Verifikasi bukti pembayaran UMKM dan aktifkan paket Premium.</p>
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

        <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 p-8">
            <div class="overflow-x-auto">
                <table class="data-table w-full text-sm text-left text-slate-500">
                    <thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-3">ID Bayar</th>
                            <th class="px-4 py-3">Nama UMKM</th>
                            <th class="px-4 py-3">SmartBank ID</th>
                            <th class="px-4 py-3">Bukti</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payments as $p): ?>
                        <tr class="border-b dark:border-slate-700">
                            <td class="px-4 py-3 font-bold">#<?php echo $p['id']; ?></td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800 dark:text-white"><?php echo $p['nama_bisnis']; ?></div>
                                <div class="text-xs"><?php echo $p['nama_lengkap']; ?></div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs"><?php echo $p['smartbank_id']; ?></td>
                            <td class="px-4 py-3">
                                <a href="assets/image/uploads/<?php echo $p['proof_image']; ?>" target="_blank" class="text-brand-600 hover:underline flex items-center gap-1">
                                    <i class="ph-bold ph-image"></i> Lihat Bukti
                                </a>
                            </td>
                            <td class="px-4 py-3"><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></td>
                            <td class="px-4 py-3">
                                <?php if ($p['status'] == 'pending'): ?>
                                    <span class="badge bg-amber-100 text-amber-700 text-xs">Menunggu</span>
                                <?php elseif ($p['status'] == 'approved'): ?>
                                    <span class="badge bg-emerald-100 text-emerald-700 text-xs">Disetujui</span>
                                <?php else: ?>
                                    <span class="badge bg-rose-100 text-rose-700 text-xs">Ditolak</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <?php if ($p['status'] == 'pending'): ?>
                                    <form method="POST" class="inline-flex gap-2">
                                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $p['user_id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-sm bg-emerald-500 text-white hover:bg-emerald-600 px-3" onclick="return confirm('Setujui pembayaran ini?');"><i class="ph-bold ph-check"></i></button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm bg-rose-500 text-white hover:bg-rose-600 px-3" onclick="return confirm('Tolak pembayaran ini?');"><i class="ph-bold ph-x"></i></button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-slate-400 text-xs italic">Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($payments)): ?>
                        <tr><td colspan="7" class="text-center py-4">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
