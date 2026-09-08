<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('admin');

$user = getCurrentUser($pdo);
$pageTitle = "Audit Logs";
$activePage = 'audit-logs';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in" style="padding-top: 24px;">
        <div class="mb-8">
            <h1 class="text-2xl font-extrabold tracking-tight">System Audit Logs</h1>
            <p class="text-sm text-slate-500 mt-1">Lacak dan pantau semua aktivitas kritis di dalam sistem UMKM Insight.</p>
        </div>

        <div class="card p-10 flex flex-col items-center justify-center text-center">
            <div class="w-20 h-20 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mb-6">
                <i class="ph ph-wrench text-4xl"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Modul Sedang Dikembangkan</h2>
            <p class="text-slate-500 max-w-md">
                Fitur perekaman log aktivitas sistem secara komprehensif sedang dalam tahap pengembangan dan akan segera hadir pada pembaruan berikutnya.
            </p>
            <a href="admin.php" class="btn btn-primary mt-6">
                <i class="ph ph-arrow-left"></i> Kembali ke Dashboard Admin
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
