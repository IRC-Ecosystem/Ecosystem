<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

// Proteksi Halaman: Hanya Role Client yang bisa akses dashboard utama UMKM
requireRole('client');

$dashboardData = (new App\Controllers\ClientAnalyticsController($pdo))->dashboard($_SESSION);
extract($dashboardData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <!-- Topbar -->
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px;">
        <!-- Greeting -->
        <div style="margin-bottom:24px;">
            <?php 
                $hour = date('H');
                $greet = ($hour < 12) ? 'Selamat Pagi' : (($hour < 17) ? 'Selamat Siang' : 'Selamat Malam');
            ?>
            <h1 class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-amber-400 via-amber-500 to-yellow-600 animate-pop-in drop-shadow-sm">
                <?php echo $greet . ", " . explode(' ', $user['nama_lengkap'])[0]; ?>! <span class="floating-element inline-block">👋</span>
            </h1>
            <p style="font-size:.875rem;margin-top:8px;" class="text-slate-500 dark:text-slate-400 animate-pop-in stagger-1 font-medium">Berikut ringkasan bisnis UMKM Anda hari ini.</p>
        </div>

        <!-- API Connection Warning -->
        <?php if(!$user['smartbank_id'] || !$user['warungpos_id']): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 flex justify-between items-center animate-pop-in stagger-1">
                <div class="flex gap-3 items-center">
                    <i class="ph-fill ph-warning-circle text-xl"></i>
                    <div>
                        <strong class="font-bold text-sm block">API Belum Terhubung Sepenuhnya</strong>
                        <span class="text-xs">
                            <?php 
                                $missing = [];
                                if(!$user['smartbank_id']) $missing[] = 'SmartBank';
                                if(!$user['warungpos_id']) $missing[] = 'WarungPos';
                                echo implode(' dan ', $missing) . ' belum terhubung. Data di bawah ini adalah rekaman histori terakhir dan tidak lagi terupdate secara otomatis.';
                            ?>
                        </span>
                    </div>
                </div>
                <a href="profile.php" class="btn btn-sm bg-rose-600 hover:bg-rose-700 text-white shadow-sm whitespace-nowrap">Hubungkan Sekarang</a>
            </div>
        <?php endif; ?>

        <!-- Profile + Integration Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- UMKM Profile (Spans 2 columns for wider Bento look) -->
            <div class="lg:col-span-2 card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 animate-pop-in stagger-2 p-8 flex flex-col justify-between relative overflow-hidden group">
                <!-- Decorative background elements -->
                <div class="absolute -right-20 -top-20 w-64 h-64 bg-brand-50 rounded-full dark:bg-brand-900/20 group-hover:scale-150 transition-transform duration-700 ease-out"></div>
                <div class="absolute right-10 bottom-10 w-32 h-32 bg-indigo-50 rounded-full dark:bg-indigo-900/20 group-hover:scale-150 transition-transform duration-700 ease-out delay-75"></div>
                
                <div class="flex items-center gap-5 mb-8 relative z-10">
                    <div class="w-16 h-16 rounded-2xl bg-brand-100 dark:bg-brand-900/50 flex items-center justify-center pulse-glow shadow-sm">
                        <i class="ph-fill ph-storefront text-4xl text-brand-600 dark:text-brand-400 floating-element"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight"><?php echo $user['nama_bisnis'] ?: 'Bisnis Belum Dinamai'; ?></h2>
                        <p class="text-sm font-medium text-brand-600 dark:text-brand-400 uppercase tracking-widest mt-1"><?php echo $user['kategori'] ?: 'Kategori belum diset'; ?></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 relative z-10 bg-slate-50 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Email</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate"><?php echo $user['email']; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">SmartBank ID</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 font-mono"><?php echo $user['smartbank_id'] ?: '—'; ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Status</p>
                        <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 pulse-glow"></span><span class="text-xs font-bold text-slate-700 dark:text-slate-200">Terdaftar</span></div>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider mb-1">Bergabung</p>
                        <p class="text-xs font-bold text-slate-700 dark:text-slate-200"><?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Integration Card -->
            <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 animate-pop-in stagger-3 p-8 flex flex-col justify-between relative overflow-hidden group">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-brand-50 dark:bg-brand-900/20 rounded-full group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div class="w-12 h-12 rounded-xl bg-brand-100 dark:bg-brand-900/50 text-brand-600 dark:text-brand-400 flex items-center justify-center">
                            <i class="ph-fill ph-plugs-connected text-3xl"></i>
                        </div>
                        <span class="badge bg-emerald-100 text-emerald-700 border-emerald-200 shadow-sm px-3 py-1">
                            CONNECTED
                        </span>
                    </div>
                    
                    <h3 class="text-xl font-extrabold text-slate-800 dark:text-white mb-2">API Gateway Ecosystem</h3>
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 leading-relaxed mb-6">Hubungkan transaksi bisnis UMKM Anda dengan API Integrator & SmartBank.</p>
                </div>

                <div class="flex flex-col gap-2 relative z-10 mt-auto">
                    <a href="api-integrator.php" data-tooltip="Kelola konfigurasi API SmartBank dan WarungPos" class="btn bg-brand-600 text-white hover:bg-brand-700 shadow-md transition-all btn-full"><i class="ph-fill ph-plugs-connected"></i> Buka Integrasi Gateway</a>
                </div>
            </div>
        </div>

        <!-- Vivid KPI Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- PENDAPATAN -->
            <div class="card animate-pop-in stagger-3 p-6 bg-gradient-to-br from-emerald-400 to-teal-600 text-white border-0 shadow-lg shadow-emerald-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300" data-tooltip="Total semua pemasukan bisnis yang tercatat dari SmartBank & transaksi">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Pendapatan</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-wallet text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($totalRevenue); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-emerald-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-trend-up"></i> Total Akumulasi</div>
            </div>
            
            <!-- PENGELUARAN -->
            <div class="card animate-pop-in stagger-4 p-6 bg-gradient-to-br from-rose-400 to-pink-600 text-white border-0 shadow-lg shadow-rose-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300" data-tooltip="Total pengeluaran operasional bulan berjalan dari kas bisnis">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Pengeluaran</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-shopping-cart text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($totalExpense); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-rose-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-trend-down"></i> Bulan Berjalan</div>
            </div>
            
            <!-- LABA BERSIH -->
            <div class="card animate-pop-in stagger-5 p-6 bg-gradient-to-br from-indigo-500 to-purple-600 text-white border-0 shadow-lg shadow-indigo-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300" data-tooltip="Selisih pendapatan dikurangi pengeluaran — estimasi keuntungan bersih">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Laba Bersih</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-chart-line-up text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($netProfit); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-indigo-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-sparkle"></i> Margin Estimasi</div>
            </div>
            
            <!-- TRANSAKSI -->
            <div class="card animate-pop-in stagger-6 p-6 bg-gradient-to-br from-amber-400 to-orange-500 text-white border-0 shadow-lg shadow-amber-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300" data-tooltip="Jumlah total transaksi yang tercatat dalam riwayat terakhir">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Transaksi</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-receipt text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo count($recentTransactions); ?> <span class="text-lg font-bold text-white/70">trx</span></div>
                <div class="text-[11px] mt-2 font-semibold text-amber-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-clock"></i> Riwayat Terbaru</div>
            </div>
        </div>

        <!-- Mini Charts -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="card animate-pop-in stagger-4 p-5 group" data-tooltip="Grafik tren pendapatan mingguan">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold flex items-center gap-2 text-slate-700 dark:text-slate-200"><i class="ph-fill ph-trend-up text-emerald-500 text-lg group-hover:scale-110 transition-transform"></i> Penjualan</h3>
                </div>
                <div style="height: 100px; position: relative;">
                    <canvas id="mini-sales"></canvas>
                </div>
                <a href="laporan-penjualan.php" class="text-[11px] text-brand-600 dark:text-brand-400 font-bold flex items-center gap-1 mt-4 hover:gap-2 transition-all">Detail Penjualan <i class="ph-bold ph-arrow-right"></i></a>
            </div>
            <div class="card animate-pop-in stagger-5 p-5 group" data-tooltip="Grafik arus kas masuk dan keluar">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold flex items-center gap-2 text-slate-700 dark:text-slate-200"><i class="ph-fill ph-money text-blue-500 text-lg group-hover:scale-110 transition-transform"></i> Arus Kas</h3>
                </div>
                <div style="height: 100px; position: relative;">
                    <canvas id="mini-cashflow"></canvas>
                </div>
                <a href="arus-kas.php" class="text-[11px] text-brand-600 dark:text-brand-400 font-bold flex items-center gap-1 mt-4 hover:gap-2 transition-all">Detail Arus Kas <i class="ph-bold ph-arrow-right"></i></a>
            </div>
            <div class="card animate-pop-in stagger-6 p-5 group" data-tooltip="Distribusi produk terlaris">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-sm font-bold flex items-center gap-2 text-slate-700 dark:text-slate-200"><i class="ph-fill ph-package text-amber-500 text-lg group-hover:scale-110 transition-transform"></i> Produk</h3>
                </div>
                <div style="height: 100px; position: relative;">
                    <canvas id="mini-product"></canvas>
                </div>
                <a href="performa-produk.php" class="text-[11px] text-brand-600 dark:text-brand-400 font-bold flex items-center gap-1 mt-4 hover:gap-2 transition-all">Detail Produk <i class="ph-bold ph-arrow-right"></i></a>
            </div>
        </div>

        <!-- Transactions & Info -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="card animate-pop-in stagger-5 p-5">
                <h3 class="text-sm font-bold mb-4 flex items-center gap-2 text-slate-800 dark:text-white"><i class="ph-fill ph-bell-ringing text-amber-500 animate-bounce" style="animation-duration: 3s;"></i> Info & Notifikasi</h3>
                <div class="flex flex-col gap-3" id="notif-list-dashboard">
                    <?php if(empty($notifications)): ?>
                        <div class="text-center py-6">
                            <p class="text-[10px] text-slate-400 dark:text-slate-500">Belum ada notifikasi.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($notifications as $n): ?>
                            <div class="p-3 rounded-xl bg-white/40 dark:bg-slate-800/40 border border-white/60 dark:border-slate-700/60 hover:bg-white/60 dark:hover:bg-slate-700/60 transition-colors shadow-sm backdrop-blur-md">
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 mb-1 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500 inline-block pulse-glow"></span>
                                    <?php echo $n['title']; ?>
                                </p>
                                <p class="text-[10px] text-slate-600 dark:text-slate-400 leading-relaxed pl-3"><?php echo $n['message']; ?></p>
                                <p class="text-[8px] text-slate-400 dark:text-slate-500 mt-2 pl-3"><?php echo date('d M Y, H:i', strtotime($n['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="lg:col-span-2 card animate-pop-in stagger-6 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 dark:border-slate-700/60 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50 backdrop-blur-md">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-white">Transaksi Terbaru</h3>
                        <p class="text-[10px] text-slate-500 dark:text-slate-400">Data terhubung dengan SmartBank & Marketplace</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="btnSync" data-tooltip="Tarik data transaksi terbaru dari SmartBank secara manual" class="btn btn-ghost bg-brand-50 text-brand-600 hover:bg-brand-100 border border-brand-200 btn-sm shadow-sm transition-all flex items-center gap-1">
                            <i class="ph-bold ph-arrows-clockwise"></i> Sinkronisasi Data
                        </button>
                        <a href="laporan-penjualan.php" data-tooltip="Buka halaman laporan penjualan lengkap" class="btn btn-ghost bg-white/50 dark:bg-slate-800/50 hover:bg-white dark:hover:bg-slate-700 border border-white/60 dark:border-slate-600 btn-sm shadow-sm transition-all hover:shadow-md dark:text-slate-200">Lihat Semua</a>
                    </div>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="data-table w-full">
                        <thead class="bg-slate-100/50 dark:bg-slate-900/30 backdrop-blur-sm border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="text-left font-bold text-slate-500 dark:text-slate-400">ID</th>
                                <th class="text-left font-bold text-slate-500 dark:text-slate-400">Tipe</th>
                                <th class="text-left font-bold text-slate-500 dark:text-slate-400">Sumber</th>
                                <th class="text-right font-bold text-slate-500 dark:text-slate-400">Nominal</th>
                                <th class="text-left font-bold text-slate-500 dark:text-slate-400">Status</th>
                            </tr>
                        </thead>
                        <tbody id="trx-body-dashboard">
                            <?php foreach($recentTransactions as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <td class="font-mono text-[10px] text-slate-500 dark:text-slate-400 font-medium"><?php echo $t['external_id']; ?></td>
                                    <td class="font-bold text-slate-700 dark:text-slate-200 text-xs flex items-center gap-1.5">
                                        <?php if($t['type'] === 'Income'): ?>
                                            <i class="ph-bold ph-arrow-down-left text-emerald-500"></i> 
                                        <?php else: ?>
                                            <i class="ph-bold ph-arrow-up-right text-rose-500"></i>
                                        <?php endif; ?>
                                        <?php echo $t['type']; ?>
                                    </td>
                                    <td><span class="badge bg-slate-100/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200/50 dark:border-slate-700/50 shadow-sm"><?php echo $t['source']; ?></span></td>
                                    <td class="text-right font-extrabold <?php echo $t['type'] === 'Income' ? 'text-emerald-600' : 'text-rose-600'; ?>">
                                        <?php echo ($t['type'] === 'Income' ? '+ ' : '- ') . formatRupiah($t['amount']); ?>
                                    </td>
                                    <td><span class="badge badge-success shadow-sm shadow-emerald-500/20"><i class="ph-bold ph-check text-[10px]"></i> Success</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <footer class="mt-12 text-center text-[11px] text-slate-400 py-6 border-t border-slate-100">
            &copy; <?php echo date('Y'); ?> Ekosistem Ekonomi UMKM. Simulasi Sistem Informasi RPL 2.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- MINI CHARTS (Tahap 4) ---
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = '#94a3b8';
        const miniOpts = {responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{display:false},y:{display:false}},elements:{point:{radius:0}}};

        // Revenue Chart (Mini)
        const labels = <?php echo json_encode($chartLabels); ?>;
        const dataValues = <?php echo json_encode($chartValues); ?>;

        new Chart(document.getElementById('mini-sales'), {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [{
                    data: dataValues.length ? dataValues : [0],
                    borderColor: '#14b8a6',
                    backgroundColor: 'rgba(20, 184, 166, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: miniOpts
        });
        
        // Placeholder static charts for mini display
        new Chart(document.getElementById('mini-cashflow'),{type:'bar',data:{labels:['M1','M2','M3','M4','M5'],datasets:[{data:[4,6,3,8,5],backgroundColor:'rgba(20, 184, 166, 0.6)',borderRadius:3},{data:[-2,-1,-4,-2,-3],backgroundColor:'rgba(244,63,94,0.4)',borderRadius:3}]},options:{...miniOpts,scales:{x:{display:false},y:{display:false}}}});
        new Chart(document.getElementById('mini-product'),{type:'doughnut',data:{labels:['A','B','C'],datasets:[{data:[40,35,25],backgroundColor:['#14b8a6','#3b82f6','#f59e0b'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},cutout:'75%'}});

        // Sync Data Logic
        const btnSync = document.getElementById('btnSync');
        if(btnSync) {
            btnSync.addEventListener('click', async () => {
                const icon = btnSync.querySelector('i');
                icon.classList.add('animate-spin');
                btnSync.disabled = true;

                try {
                    const res = await fetch('api/sync_data.php');
                    const data = await res.json();
                    if(data.status === 'success') {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                        icon.classList.remove('animate-spin');
                        btnSync.disabled = false;
                    }
                } catch(err) {
                    alert('Gagal menghubungi server.');
                    icon.classList.remove('animate-spin');
                    btnSync.disabled = false;
                }
            });
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
