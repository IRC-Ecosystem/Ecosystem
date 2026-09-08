<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$cashflowData = (new App\Controllers\ClientAnalyticsController($pdo))->cashflow($_SESSION);
extract($cashflowData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px; padding-left: 24px; padding-right: 24px;">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-400 via-indigo-500 to-purple-600 animate-pop-in drop-shadow-sm mb-1">
                    Arus Kas (Cashflow)
                </h1>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 flex items-center gap-2 animate-pop-in stagger-1">
                    Aliran kas masuk & keluar bisnis Anda 
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 text-[10px] font-bold border border-indigo-200 dark:border-indigo-800/50">
                        <i class="ph-fill ph-bank"></i> SmartBank Integrated
                    </span>
                </p>
            </div>
            <button onclick="location.reload()" class="btn bg-gradient-to-r from-blue-500 to-indigo-600 text-white hover:from-blue-600 hover:to-indigo-700 shadow-md hover:shadow-lg transition-all btn-sm animate-pop-in stagger-2"><i class="ph-bold ph-arrows-clockwise"></i> Refresh</button>
        </div>

        <!-- KPI Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6" id="kpi-cashflow">
            <?php 
            $netPos = $netCash >= 0; 
            ?>
            <!-- KAS BERSIH -->
            <div class="card animate-pop-in stagger-3 p-6 bg-gradient-to-br <?php echo $netPos ? 'from-emerald-400 to-teal-600 shadow-emerald-500/20' : 'from-rose-400 to-pink-600 shadow-rose-500/20'; ?> text-white border-0 shadow-lg relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Kas Bersih</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-scales text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($netCash); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-white/80 relative z-10 flex items-center gap-1"><i class="ph-bold <?php echo $netPos ? 'ph-trend-up' : 'ph-trend-down'; ?>"></i> Status: <?php echo $netPos ? 'Surplus' : 'Defisit'; ?></div>
            </div>

            <!-- TOTAL MASUK -->
            <div class="card animate-pop-in stagger-4 p-6 bg-gradient-to-br from-blue-400 to-indigo-600 text-white border-0 shadow-lg shadow-blue-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Total Masuk</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-arrow-circle-down text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($totalIn); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-blue-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-plus-circle"></i> Kumulatif Pemasukan</div>
            </div>

            <!-- TOTAL KELUAR -->
            <div class="card animate-pop-in stagger-5 p-6 bg-gradient-to-br from-amber-400 to-orange-500 text-white border-0 shadow-lg shadow-amber-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex justify-between items-center mb-4 relative z-10">
                    <span class="text-xs font-bold text-white/80 uppercase tracking-widest">Total Keluar</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-arrow-circle-up text-xl"></i></div>
                </div>
                <div class="text-3xl font-black text-white tracking-tight relative z-10"><?php echo formatRupiah($totalOut); ?></div>
                <div class="text-[11px] mt-2 font-semibold text-amber-50 relative z-10 flex items-center gap-1"><i class="ph-bold ph-minus-circle"></i> Kumulatif Pengeluaran</div>
            </div>
        </div>

        <!-- Daily Trend (Free) -->
        <div class="card animate-pop-in stagger-5 p-6 mb-6">
            <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                <div class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 text-emerald-600 dark:text-emerald-400"><i class="ph-bold ph-chart-line"></i></div>
                Tren Arus Kas Harian (7 Hari Terakhir)
            </h3>
            <div style="height: 320px; position: relative;">
                <canvas id="chart-cashflow-daily"></canvas>
            </div>
        </div>

        <!-- Premium Analysis Section -->
        <div class="card animate-pop-in stagger-6 p-8 mb-6 relative overflow-hidden <?php echo !$isPremium ? 'premium-locked' : ''; ?>">
            <!-- Background Glow -->
            <div class="absolute -left-20 -top-20 w-80 h-80 bg-indigo-500 rounded-full mix-blend-multiply dark:mix-blend-screen opacity-5 dark:opacity-10 pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 relative z-10 mb-6">
                <div class="p-5 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/80 backdrop-blur-md hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400"><i class="ph-bold ph-chart-bar"></i></div>
                        Perbandingan Kas Bulanan 
                        <span class="badge bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-400 border-indigo-300 dark:border-indigo-700 ml-2 shadow-sm">PRO</span>
                    </h3>
                    <div style="height: 250px; position: relative;">
                        <canvas id="chart-cashflow-monthly"></canvas>
                    </div>
                </div>
                <div class="p-5 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/80 backdrop-blur-md hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shadow-sm">
                    <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400"><i class="ph-bold ph-crystal-ball"></i></div>
                        Proyeksi Saldo Masa Depan 
                        <span class="badge bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-400 border-purple-300 dark:border-purple-700 ml-2 shadow-sm">PRO</span>
                    </h3>
                    <div style="height: 250px; position: relative;">
                        <canvas id="chart-cashflow-forecast"></canvas>
                    </div>
                </div>
            </div>

            <div class="p-6 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/80 backdrop-blur-md relative z-10 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors shadow-sm">
                <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400"><i class="ph-bold ph-stack"></i></div>
                    Analisis Kategori Pengeluaran 
                    <span class="badge bg-amber-100 dark:bg-amber-900/50 text-amber-800 dark:text-amber-400 border-amber-300 dark:border-amber-700 ml-2 shadow-sm">PRO</span>
                </h3>
                <div class="space-y-5" id="cashflow-categories">
                    <!-- Progress bars for categories -->
                </div>
            </div>

            <?php if(!$isPremium): ?>
                <div class="premium-lock-badge" onclick="window.location.href='langganan.php'">
                    <i class="ph-fill ph-crown text-2xl mb-2"></i>
                    <span class="font-bold">Upgrade ke Premium untuk akses Analisis Lanjutan</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if($isPremium): ?>
            <div class="flex justify-end mb-12 animate-pop-in stagger-7">
                <button class="btn bg-white/60 dark:bg-slate-800/60 hover:bg-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-white/60 dark:border-slate-600 shadow-sm transition-all"><i class="ph-bold ph-file-pdf"></i> Download Laporan Cashflow (PDF)</button>
            </div>
        <?php endif; ?>
        
        <footer class="mt-8 mb-4 text-center text-[11px] text-slate-400 py-6 border-t border-slate-100 dark:border-slate-800">
            &copy; <?php echo date('Y'); ?> Ekosistem Ekonomi UMKM. Simulasi Sistem Informasi RPL 2.
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const isPremium = <?php echo $isPremium ? 'true' : 'false'; ?>;
        
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';

        // Daily Chart (Tahap 4)
        const labels = <?php echo json_encode($chartLabels); ?>;
        const income = <?php echo json_encode($incomeValues); ?>;
        const expense = <?php echo json_encode($expenseValues); ?>;

        const ctxDaily = document.getElementById('chart-cashflow-daily').getContext('2d');
        let gradIncome = ctxDaily.createLinearGradient(0, 0, 0, 400);
        gradIncome.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
        gradIncome.addColorStop(1, 'rgba(16, 185, 129, 0)');

        let gradExpense = ctxDaily.createLinearGradient(0, 0, 0, 400);
        gradExpense.addColorStop(0, 'rgba(244, 63, 94, 0.15)');
        gradExpense.addColorStop(1, 'rgba(244, 63, 94, 0)');

        new Chart(ctxDaily, {
            type: 'line',
            data: {
                labels: labels.length ? labels : ['No Data'],
                datasets: [
                    { 
                        label: 'Masuk', 
                        data: income.length ? income : [0], 
                        borderColor: '#10b981', 
                        backgroundColor: gradIncome, 
                        fill: true, 
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    { 
                        label: 'Keluar', 
                        data: expense.length ? expense : [0], 
                        borderColor: '#f43f5e', 
                        backgroundColor: gradExpense, 
                        fill: true, 
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#f43f5e',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { usePointStyle: true, boxWidth: 8, padding: 20 } 
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#fff',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        cornerRadius: 8,
                        usePointStyle: true
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

        if (isPremium) {
            // Monthly
            new Chart(document.getElementById('chart-cashflow-monthly'), {
                type: 'bar',
                data: {
                    labels: ['Jan','Feb','Mar','Apr','Mei','Jun'],
                    datasets: [
                        { label: 'Masuk', data: [32e6,35e6,38e6,40e6,42e6,45e6], backgroundColor: '#10b981', borderRadius: 4 },
                        { label: 'Keluar', data: [20e6,22e6,25e6,24e6,26e6,28e6], backgroundColor: '#f43f5e', borderRadius: 4 }
                    ]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    plugins: { 
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } },
                        tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                    },
                    scales: {
                        y: { grid: { borderDash: [5, 5], color: 'rgba(148, 163, 184, 0.1)' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });

            // Forecast
            new Chart(document.getElementById('chart-cashflow-forecast'), {
                type: 'line',
                data: {
                    labels: ['Minggu 1','Minggu 2','Minggu 3','Minggu 4'],
                    datasets: [{ label: 'Proyeksi Saldo', data: [11e6,12.5e6,13e6,14.5e6], borderColor: '#8b5cf6', backgroundColor: '#8b5cf6', borderDash: [5,5], tension: 0.3, fill: false, pointRadius: 5 }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                    },
                    scales: {
                        y: { grid: { borderDash: [5, 5], color: 'rgba(148, 163, 184, 0.1)' }, border: { display: false } },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });

            // Categories
            const formatRupiah = (number) => {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
            };

            const cats = [
                {l:'Penjualan Produk', v:35000000, p:100, c:'#10b981'},
                {l:'Pembelian Stok', v:15000000, p:42, c:'#f43f5e'},
                {l:'Biaya Operasional', v:5000000, p:14, c:'#f59e0b'},
                {l:'Fee Marketplace', v:1500000, p:4, c:'#3b82f6'},
                {l:'Pajak Sistem', v:900000, p:2, c:'#6366f1'}
            ];
            document.getElementById('cashflow-categories').innerHTML = cats.map(ct => `
                <div>
                    <div class="flex justify-between text-xs mb-2">
                        <span class="font-bold text-slate-700 dark:text-slate-200 flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full shadow-sm" style="background:${ct.c}"></span> ${ct.l}</span>
                        <span class="text-slate-500 dark:text-slate-400 font-medium">${formatRupiah(ct.v)} (${ct.p}%)</span>
                    </div>
                    <div class="w-full bg-slate-200/50 dark:bg-slate-700/50 h-2 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full rounded-full" style="width:${ct.p}%; background:${ct.c}"></div>
                    </div>
                </div>
            `).join('');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
