<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('admin');

$user = getCurrentUser($pdo);
$pageTitle = "System Configuration";
$activePage = 'system-config';

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in" style="padding-top: 24px;">
        <div class="mb-8">
            <h1 class="text-2xl font-extrabold tracking-tight">Pengaturan Sistem Utama</h1>
            <p class="text-sm text-slate-500 mt-1">Konfigurasi variabel lingkungan, koneksi API pihak ketiga, dan pengaturan inti aplikasi.</p>
        </div>

        <div class="card p-10 flex flex-col items-center justify-center text-center border-l-4 border-amber-500">
            <div class="w-20 h-20 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center mb-6">
                <i class="ph ph-lock-key text-4xl"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-800 mb-2">Akses Terbatas</h2>
            <p class="text-slate-500 max-w-md">
                Konfigurasi sistem lanjutan dinonaktifkan pada versi simulasi ini untuk menjaga stabilitas prototipe. Pengaturan inti hanya dapat diubah melalui akses server langsung.
            </p>
            <a href="admin.php" class="btn btn-primary mt-6">
                <i class="ph ph-arrow-left"></i> Kembali ke Dashboard Admin
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
