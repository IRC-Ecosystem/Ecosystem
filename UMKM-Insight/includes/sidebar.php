<?php
/**
 * Sidebar Navigation
 * Merender menu berdasarkan role user yang sedang login
 */
$current_role = $_SESSION['role'] ?? 'client';
$is_premium = ($_SESSION['tier'] ?? 'free') === 'premium';
?>

<aside class="app-sidebar" id="sidebar">
    <div class="sidebar-brand">
        <img src="assets/image/logo_desai.png" alt="Logo UMKM Insight" style="width: 24px; height: 24px; object-fit: contain;">
        <span>UMKM Insight</span>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <?php if($current_role === 'client'): ?>
                <li class="nav-item">
                    <a href="dashboard.php"
                       class="<?php echo ($activePage == 'dashboard') ? 'active' : ''; ?>"
                       data-tooltip="Ringkasan kinerja bisnis Anda: omzet, transaksi, dan tren terkini"
                       data-tooltip-pos="right">
                        <i class="ph ph-squares-four"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan-penjualan.php"
                       class="<?php echo ($activePage == 'laporan') ? 'active' : ''; ?>"
                       data-tooltip="Laporan penjualan harian, mingguan, dan bulanan beserta grafik tren"
                       data-tooltip-pos="right">
                        <i class="ph ph-trend-up"></i> Laporan Penjualan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="arus-kas.php"
                       class="<?php echo ($activePage == 'arus-kas') ? 'active' : ''; ?>"
                       data-tooltip="Pantau pemasukan dan pengeluaran kas bisnis secara real-time dari SmartBank"
                       data-tooltip-pos="right">
                        <i class="ph ph-money"></i> Arus Kas
                    </a>
                </li>
                <li class="nav-item">
                    <a href="performa-produk.php"
                       class="<?php echo ($activePage == 'performa-produk') ? 'active' : ''; ?>"
                       data-tooltip="Analisis produk terlaris, margin keuntungan, dan kategori penjualan"
                       data-tooltip-pos="right">
                        <i class="ph ph-package"></i> Performa Produk
                    </a>
                </li>
                <li class="nav-item">
                    <a href="api-integrator.php"
                       class="<?php echo ($activePage == 'api-integrator') ? 'active' : ''; ?>"
                       data-tooltip="Kelola koneksi API ke SmartBank dan layanan eksternal lainnya"
                       data-tooltip-pos="right">
                        <i class="ph ph-plugs-connected"></i> Integrasi Gateway
                    </a>
                </li>
                <li class="nav-item">
                    <a href="langganan.php"
                       class="<?php echo ($activePage == 'langganan') ? 'active' : ''; ?>"
                       data-tooltip="Kelola paket berlangganan dan lakukan pembayaran via SmartBank Wallet"
                       data-tooltip-pos="right">
                        <i class="ph ph-crown"></i> Langganan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pengaduan.php"
                       class="<?php echo ($activePage == 'pengaduan') ? 'active' : ''; ?>"
                       data-tooltip="Kirim laporan kendala atau pertanyaan ke tim dukungan kami"
                       data-tooltip-pos="right">
                        <i class="ph ph-chat-circle-dots"></i> Pengaduan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php"
                       class="<?php echo ($activePage == 'profile') ? 'active' : ''; ?>"
                       data-tooltip="Ubah informasi profil, foto, dan data bisnis UMKM Anda"
                       data-tooltip-pos="right">
                        <i class="ph ph-user-circle"></i> Profil Bisnis
                    </a>
                </li>

            <?php elseif($current_role === 'operator'): ?>
                <li class="nav-item">
                    <a href="operator.php"
                       class="<?php echo ($activePage == 'operator_dashboard') ? 'active' : ''; ?>"
                       data-tooltip="Dashboard ringkasan semua aktivitas pengguna yang perlu ditangani"
                       data-tooltip-pos="right">
                        <i class="ph ph-squares-four"></i> Dashboard Operator
                    </a>
                </li>
                <li class="nav-item">
                    <a href="pengaduan-admin.php"
                       class="<?php echo ($activePage == 'pengaduan_admin') ? 'active' : ''; ?>"
                       data-tooltip="Lihat dan respons pengaduan yang masuk dari pengguna"
                       data-tooltip-pos="right">
                        <i class="ph ph-envelope-open"></i> Kelola Pengaduan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="langganan-admin.php"
                       class="<?php echo ($activePage == 'langganan_admin') ? 'active' : ''; ?>"
                       data-tooltip="Verifikasi pembayaran dan aktifkan akun premium pengguna"
                       data-tooltip-pos="right">
                        <i class="ph ph-check-circle"></i> Verifikasi Tagihan
                    </a>
                </li>

            <?php elseif($current_role === 'admin'): ?>
                <li class="nav-item">
                    <a href="admin.php"
                       class="<?php echo ($activePage == 'admin') ? 'active' : ''; ?>"
                       data-tooltip="Kelola semua akun pengguna: tambah, edit, non-aktifkan"
                       data-tooltip-pos="right">
                        <i class="ph ph-users-three"></i> Manajemen User
                    </a>
                </li>
                <li class="nav-item">
                    <a href="audit-logs.php"
                       class="<?php echo ($activePage == 'audit-logs') ? 'active' : ''; ?>"
                       data-tooltip="Riwayat seluruh aktivitas sistem untuk keperluan keamanan dan audit"
                       data-tooltip-pos="right">
                        <i class="ph ph-list-magnifying-glass"></i> Audit Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="system-config.php"
                       class="<?php echo ($activePage == 'system-config') ? 'active' : ''; ?>"
                       data-tooltip="Pengaturan teknis sistem: API key, integrasi, dan konfigurasi global"
                       data-tooltip-pos="right">
                        <i class="ph ph-gear"></i> System Config
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="logout.php" class="btn btn-ghost btn-sm btn-full" style="justify-content:flex-start;"
           data-tooltip="Keluar dari sesi Anda saat ini" data-tooltip-pos="right">
            <i class="ph ph-sign-out"></i> Keluar
        </a>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Main content wrap starts after sidebar for flex layout -->
<div class="main-wrap">
