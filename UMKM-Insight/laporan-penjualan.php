<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$salesReportData = (new App\Controllers\ClientAnalyticsController($pdo))->salesReport($_SESSION);
extract($salesReportData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px; padding-left: 24px; padding-right: 24px;">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-emerald-400 via-teal-500 to-emerald-600 animate-pop-in drop-shadow-sm mb-1">
                    Laporan Penjualan
                </h1>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 flex items-center gap-2 animate-pop-in stagger-1">
                    Analisis pendapatan dan tren penjualan 
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold border border-emerald-200 dark:border-emerald-800/50">
                        <i class="ph-fill ph-bank"></i> SmartBank Connected
                    </span>
                </p>
            </div>
            <div class="flex gap-3 animate-pop-in stagger-2">
                <?php if($isPremium): ?>
                    <button class="btn bg-white/60 dark:bg-slate-800/60 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-white/60 dark:border-slate-600 shadow-sm transition-all btn-sm"><i class="ph-bold ph-download-simple"></i> Export CSV</button>
                <?php endif; ?>
                <button onclick="location.reload()" class="btn bg-gradient-to-r from-emerald-500 to-teal-500 text-white hover:from-emerald-600 hover:to-teal-600 shadow-md hover:shadow-lg transition-all btn-sm"><i class="ph-bold ph-arrows-clockwise"></i> Refresh Data</button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card animate-pop-in stagger-2 p-6 mb-6 flex flex-wrap gap-4 items-end bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700">
            <div class="form-group flex-1 min-w-[150px] mb-0">
                <label class="form-label text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Periode Mulai</label>
                <input type="date" class="form-input bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 shadow-inner" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
            </div>
            <div class="form-group flex-1 min-w-[150px] mb-0">
                <label class="form-label text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Periode Akhir</label>
                <input type="date" class="form-input bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 shadow-inner" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group flex-1 min-w-[150px] mb-0">
                <label class="form-label text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Tipe Transaksi</label>
                <select class="form-select bg-slate-50 dark:bg-slate-900/50 border-slate-200 dark:border-slate-700 shadow-inner">
                    <option>Semua Transaksi</option>
                    <option>Penjualan POS</option>
                    <option>Marketplace</option>
                </select>
            </div>
            <button class="btn bg-slate-800 hover:bg-slate-900 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-800 shadow-md transition-all px-6 py-2.5 rounded-xl font-bold text-sm">Terapkan Filter</button>
        </div>

        <!-- KPI Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
            <!-- TOTAL PENDAPATAN -->
            <div class="card animate-pop-in stagger-3 p-6 bg-gradient-to-br from-emerald-400 to-teal-600 text-white border-0 shadow-lg shadow-emerald-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Total Pendapatan</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-wallet text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($totalRevenue); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-emerald-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-chart-line-up"></i> Kumulatif</div>
            </div>

            <!-- TOTAL TRANSAKSI -->
            <div class="card animate-pop-in stagger-4 p-6 bg-gradient-to-br from-blue-400 to-indigo-600 text-white border-0 shadow-lg shadow-blue-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Total Transaksi</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-receipt text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo $totalSalesCount; ?> <span class="text-lg font-bold text-white/70">Pesanan</span></div>
                <div class="text-[11px] mt-2 font-semibold text-blue-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-clock"></i> Update Hari Ini</div>
            </div>

            <!-- RATA-RATA PESANAN -->
            <div class="card animate-pop-in stagger-5 p-6 bg-gradient-to-br from-amber-400 to-orange-500 text-white border-0 shadow-lg shadow-amber-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Rata-rata Pesanan</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-chart-bar text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($avgOrder); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-amber-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-shopping-bag"></i> Per Transaksi</div>
            </div>
        </div>

        <!-- Main Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 card animate-pop-in stagger-5 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400"><i class="ph-bold ph-chart-line"></i></div>
                    Tren Pendapatan 
                    <?php if(!$isPremium): ?>
                        <span class="text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-full ml-2 border border-slate-200 dark:border-slate-700">Terbatas (3 Bulan)</span>
                    <?php endif; ?>
                </h3>
                <div style="height: 300px; position: relative;">
                    <canvas id="chart-sales-main"></canvas>
                </div>
            </div>
            <div class="card animate-pop-in stagger-6 p-6 flex flex-col">
                <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-blue-100 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400"><i class="ph-bold ph-chart-pie"></i></div>
                    Sumber Penjualan
                </h3>
                <div style="height: 220px; position: relative;" class="flex-1">
                    <canvas id="chart-sales-source"></canvas>
                </div>
                <div class="mt-6 space-y-2 bg-white/40 dark:bg-slate-800/40 p-4 rounded-xl border border-white/60 dark:border-slate-700/60 backdrop-blur-sm" id="source-legend">
                    <!-- Legend content -->
                </div>
            </div>
        </div>

        <!-- Premium Feature Section -->
        <div class="card animate-pop-in stagger-6 p-8 mb-6 relative overflow-hidden <?php echo !$isPremium ? 'premium-locked' : ''; ?>">
            <!-- Background Glow -->
            <div class="absolute -right-20 -bottom-20 w-80 h-80 bg-amber-500 rounded-full mix-blend-multiply dark:mix-blend-screen opacity-5 dark:opacity-10 pointer-events-none"></div>

            <div class="flex justify-between items-center mb-6 relative z-10">
                <h3 class="text-lg font-bold flex items-center gap-2 text-slate-800 dark:text-white">
                    <div class="p-2 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 shadow-sm"><i class="ph-bold ph-lightning"></i></div>
                    Analisis Pertumbuhan Lanjutan 
                    <span class="badge bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-400 border-amber-300 dark:border-amber-700 ml-2 shadow-sm">PRO</span>
                </h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 relative z-10">
                <div class="p-5 rounded-2xl bg-white/60 dark:bg-slate-800/60 border border-white/80 dark:border-slate-700/80 backdrop-blur-md hover:bg-white dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Growth Month-over-Month</p>
                    <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-2">+14.2%</p>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 mt-2">Meningkat pesat di kategori Makanan</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/60 dark:bg-slate-800/60 border border-white/80 dark:border-slate-700/80 backdrop-blur-md hover:bg-white dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Customer Retention</p>
                    <p class="text-2xl font-black text-brand-600 dark:text-brand-400 mt-2">68%</p>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 mt-2">Repeat order via Marketplace</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/60 dark:bg-slate-800/60 border border-white/80 dark:border-slate-700/80 backdrop-blur-md hover:bg-white dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Average Order Value</p>
                    <p class="text-2xl font-black text-slate-800 dark:text-white mt-2">Rp 42.500</p>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 mt-2 text-rose-500"><i class="ph-bold ph-trend-down"></i> Turun 2% dari periode sebelumnya</p>
                </div>
                <div class="p-5 rounded-2xl bg-white/60 dark:bg-slate-800/60 border border-white/80 dark:border-slate-700/80 backdrop-blur-md hover:bg-white dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Peak Sales Hour</p>
                    <p class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2">18:00 - 20:00</p>
                    <p class="text-[10px] font-medium text-slate-500 dark:text-slate-400 mt-2">Waktu paling ramai transaksi POS</p>
                </div>
            </div>

            <?php if(!$isPremium): ?>
                <div class="premium-lock-badge" onclick="window.location.href='langganan.php'">
                    <i class="ph-fill ph-crown text-2xl mb-2"></i>
                    <span class="font-bold">Upgrade ke Premium untuk akses Wawasan Lanjutan</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Transaction List -->
        <div class="card animate-pop-in stagger-7 overflow-hidden flex flex-col mb-12">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700/60 flex justify-between items-center bg-slate-50/50 dark:bg-slate-800/50 backdrop-blur-md">
                <h3 class="text-sm font-bold text-slate-800 dark:text-white">Daftar Transaksi Lengkap</h3>
                <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Menampilkan <?php echo $isPremium ? 'Seluruh' : '10 Transaksi Terakhir (Free)'; ?></p>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="data-table w-full">
                    <thead class="bg-slate-100/50 dark:bg-slate-900/30 backdrop-blur-sm border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="text-left font-bold text-slate-500 dark:text-slate-400">ID</th>
                            <th class="text-left font-bold text-slate-500 dark:text-slate-400">Tanggal</th>
                            <th class="text-left font-bold text-slate-500 dark:text-slate-400">Tipe</th>
                            <th class="text-left font-bold text-slate-500 dark:text-slate-400">Sumber</th>
                            <th class="text-right font-bold text-slate-500 dark:text-slate-400">Nominal</th>
                            <th class="text-center font-bold text-slate-500 dark:text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody id="trx-body-full">
                        <?php if(empty($salesTransactions)): ?>
                            <tr><td colspan="6" class="text-center py-20 text-slate-400 font-medium">Tidak ada data penjualan ditemukan.</td></tr>
                        <?php else: ?>
                            <?php foreach($salesTransactions as $t): ?>
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/80 transition-colors border-b border-slate-100 dark:border-slate-800 last:border-0">
                                    <td class="font-mono text-[10px] text-slate-500 dark:text-slate-400 font-medium"><?php echo $t['external_id']; ?></td>
                                    <td class="text-xs text-slate-600 dark:text-slate-300 font-medium"><?php echo date('d M Y, H:i', strtotime($t['transaction_date'])); ?></td>
                                    <td class="font-bold text-slate-700 dark:text-slate-200 text-xs flex items-center gap-1.5">
                                        <i class="ph-bold ph-arrow-down-left text-emerald-500"></i> <?php echo $t['type']; ?>
                                    </td>
                                    <td><span class="badge bg-slate-100/80 dark:bg-slate-800/80 text-slate-600 dark:text-slate-300 border border-slate-200/50 dark:border-slate-700/50 shadow-sm"><?php echo $t['source']; ?></span></td>
                                    <td class="text-right font-extrabold text-emerald-600 dark:text-emerald-400">+ <?php echo formatRupiah($t['amount']); ?></td>
                                    <td class="text-center"><span class="badge badge-success shadow-sm shadow-emerald-500/20"><i class="ph-bold ph-check text-[10px]"></i> Success</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!$isPremium): ?>
                <div class="p-4 text-center bg-slate-50/50 dark:bg-slate-800/50 border-t border-white/50 dark:border-slate-700/50 backdrop-blur-sm">
                    <button class="btn btn-ghost btn-sm text-brand-600 dark:text-brand-400 font-bold" onclick="showToast('Hanya akun Premium yang dapat melihat sejarah transaksi lengkap', 'info')">
                        <i class="ph-bold ph-lock-key"></i> Tampilkan Lebih Banyak (Hanya Premium)
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="mt-8 mb-4 text-center text-[11px] text-slate-400 py-6 border-t border-slate-100 dark:border-slate-800">
            &copy; <?php echo date('Y'); ?> Ekosistem Ekonomi UMKM. Simulasi Sistem Informasi RPL 2.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isPremium = <?php echo $isPremium ? 'true' : 'false'; ?>;

        // --- TREND CHART (Tahap 4) ---
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';
        
        const ctxSales = document.getElementById('chart-sales-main').getContext('2d');
        const labels = <?php echo json_encode($chartLabels); ?>;
        const dataValues = <?php echo json_encode($chartValues); ?>;
        
        // Gradient for line chart
        let gradientLine = ctxSales.createLinearGradient(0, 0, 0, 400);
        gradientLine.addColorStop(0, 'rgba(16, 185, 129, 0.4)'); // emerald-500
        gradientLine.addColorStop(1, 'rgba(16, 185, 129, 0)');

        new Chart(ctxSales, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [{
                    label: 'Pendapatan',
                    data: dataValues.length ? dataValues : [0],
                    borderColor: '#10b981', // emerald-500
                    backgroundColor: gradientLine,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5, 5], color: 'rgba(148, 163, 184, 0.1)' },
                        border: { display: false }
                    },
                    x: { 
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });

        // Doughnut Source (Static for Demo)
        new Chart(document.getElementById('chart-sales-source'), {
            type: 'doughnut',
            data: {
                labels: ['POS', 'Marketplace', 'Lainnya'],
                datasets: [{
                    data: [35, 55, 10],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: { 
                cutout: '75%', 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 12,
                        cornerRadius: 8
                    }
                } 
            }
        });

        // Legend
        const legendColors = ['#10b981', '#3b82f6', '#f59e0b'];
        const legendLabels = ['POS (Kasir Toko)', 'Marketplace Online', 'Channel Lainnya'];
        document.getElementById('source-legend').innerHTML = legendLabels.map((l, i) => `
            <div class="flex items-center justify-between text-xs py-1">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background:${legendColors[i]}"></span>
                    <span class="text-slate-600 dark:text-slate-300 font-medium">${l}</span>
                </div>
                <span class="font-bold text-slate-800 dark:text-white">${[35, 55, 10][i]}%</span>
            </div>
        `).join('');
    });
</script>

<?php include 'includes/footer.php'; ?>
