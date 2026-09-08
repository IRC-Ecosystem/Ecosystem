<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$productPerformanceData = (new App\Controllers\ClientAnalyticsController($pdo))->productPerformance($_SESSION);
extract($productPerformanceData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px; padding-left: 24px; padding-right: 24px;">
        <!-- Header -->
        <div class="flex justify-between items-start mb-6 flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 animate-pop-in drop-shadow-sm mb-1">
                    Performa Produk
                </h1>
                <p class="text-sm font-medium text-slate-500 dark:text-slate-400 flex items-center gap-2 animate-pop-in stagger-1">
                    Bandingkan penjualan lokal Anda (WarungPOS) dengan tren global PasarKita
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[10px] font-bold border border-blue-200 dark:border-blue-800/50">
                        <i class="ph-fill ph-chart-line-up"></i> Lokal vs Global
                    </span>
                </p>
            </div>
            <button onclick="location.reload()" class="btn bg-gradient-to-r from-blue-500 to-indigo-600 text-white hover:from-blue-600 hover:to-indigo-700 shadow-md hover:shadow-lg transition-all btn-sm animate-pop-in stagger-2"><i class="ph-bold ph-arrows-clockwise"></i> Refresh</button>
        </div>

        <!-- KPI Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6" id="kpi-product">
            <!-- PENJUALAN LOKAL -->
            <div class="card animate-pop-in stagger-3 p-6 bg-gradient-to-br from-blue-500 to-indigo-600 text-white border-0 shadow-lg shadow-blue-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-storefront text-2xl"></i></div>
                    <div>
                        <p class="text-[10px] font-bold text-white/80 uppercase tracking-wider mb-1">Penjualan Lokal (POS)</p>
                        <p class="text-3xl font-black text-white tracking-tight drop-shadow-md"><?php echo $totalLocalSold; ?> <span class="text-sm font-bold text-white/70">Unit</span></p>
                    </div>
                </div>
            </div>

            <!-- PENDAPATAN LOKAL -->
            <div class="card animate-pop-in stagger-4 p-6 bg-gradient-to-br from-emerald-400 to-teal-600 text-white border-0 shadow-lg shadow-emerald-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-currency-circle-dollar text-2xl"></i></div>
                    <div>
                        <p class="text-[10px] font-bold text-white/80 uppercase tracking-wider mb-1">Pendapatan Lokal</p>
                        <p class="text-2xl font-black text-white tracking-tight drop-shadow-md"><?php echo formatRupiah($totalLocalRevenue); ?></p>
                    </div>
                </div>
            </div>

            <!-- PRODUK TRENDING GLOBAL -->
            <div class="card animate-pop-in stagger-5 p-6 bg-gradient-to-br from-amber-400 to-orange-500 text-white border-0 shadow-lg shadow-amber-500/20 relative overflow-hidden group hover:-translate-y-2 transition-all duration-300">
                <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/10 rounded-full mix-blend-overlay group-hover:scale-150 transition-transform duration-500 ease-out"></div>
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-white/20 text-white shadow-sm backdrop-blur-sm"><i class="ph-fill ph-globe text-2xl"></i></div>
                    <div>
                        <p class="text-[10px] font-bold text-white/80 uppercase tracking-wider mb-1">Tren PasarKita (Global)</p>
                        <p class="text-3xl font-black text-white tracking-tight drop-shadow-md"><?php echo $totalGlobalItems; ?> <span class="text-sm font-bold text-white/70">Terjual</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Comparison Bar Chart -->
            <div class="card animate-pop-in stagger-5 p-6">
                <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400"><i class="ph-bold ph-chart-bar"></i></div>
                    Penjualan Lokal vs Tren Global
                </h3>
                <div style="height: 280px; position: relative;">
                    <canvas id="chart-comparison"></canvas>
                </div>
            </div>
            <!-- Category Pie -->
            <div class="card animate-pop-in stagger-6 p-6 flex flex-col">
                <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                    <div class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400"><i class="ph-bold ph-chart-pie"></i></div>
                    Distribusi Kategori Produk
                </h3>
                <div style="height: 220px; position: relative;" class="flex-1">
                    <canvas id="chart-product-category"></canvas>
                </div>
            </div>
        </div>

        <!-- Global Trends Table -->
        <div class="card animate-pop-in stagger-6 overflow-hidden flex flex-col mb-6">
            <div class="p-5 border-b border-slate-100 dark:border-slate-700/80 bg-slate-50/50 dark:bg-slate-800/50 backdrop-blur-md flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
                        <i class="ph-fill ph-globe text-amber-500"></i> Produk Trending di PasarKita
                    </h3>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium mt-1">Data pasar global — bukan milik UMKM Anda, melainkan tren seluruh marketplace</p>
                </div>
            </div>
            <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
                <?php foreach($globalTrends as $i => $g): ?>
                    <div class="flex items-center p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs <?php echo $i<3 ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/50 dark:text-amber-400 shadow-sm' : 'bg-slate-100 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300'; ?> mr-4"><?php echo $i+1; ?></div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200"><?php echo $g['product_name']; ?></p>
                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">Harga rata-rata: <?php echo formatRupiah($g['avg_price']); ?></p>
                        </div>
                        <div class="text-right flex items-center gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-200"><?php echo $g['total_sold_global']; ?> unit</p>
                                <p class="text-[10px] text-amber-600 dark:text-amber-400 font-bold flex items-center justify-end gap-1"><i class="ph-fill ph-trend-up"></i> Trending</p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($globalTrends)): ?>
                    <div class="p-8 text-center text-sm text-slate-400">
                        <i class="ph-bold ph-arrows-clockwise text-2xl mb-2 block"></i>
                        Belum ada data tren. Klik "Sinkronisasi Data" di Dashboard terlebih dahulu.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Premium Analysis Section: Smart Insights -->
        <div class="card animate-pop-in stagger-7 p-8 mb-12 relative overflow-hidden <?php echo !$isPremium ? 'premium-locked' : ''; ?>">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-purple-500 rounded-full mix-blend-multiply dark:mix-blend-screen opacity-5 dark:opacity-10 pointer-events-none"></div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10">
                <div class="lg:col-span-2 p-5 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/80 backdrop-blur-md">
                    <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-brand-100 dark:bg-brand-900/50 text-brand-600 dark:text-brand-400"><i class="ph-bold ph-chart-line-up"></i></div>
                        Komparasi Detail: Lokal vs Global
                        <span class="badge bg-brand-100 dark:bg-brand-900/50 text-brand-800 dark:text-brand-400 border-brand-300 dark:border-brand-700 ml-2 shadow-sm">PRO</span>
                    </h3>
                    <div style="height: 300px; position: relative;">
                        <canvas id="chart-detail-comparison"></canvas>
                    </div>
                </div>
                <div class="p-5 rounded-2xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/80 backdrop-blur-md">
                    <h3 class="font-bold text-slate-800 dark:text-white mb-6 flex items-center gap-2">
                        <div class="p-1.5 rounded-lg bg-purple-100 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400"><i class="ph-bold ph-lightning"></i></div>
                        Smart Insights
                        <span class="badge bg-purple-100 dark:bg-purple-900/50 text-purple-800 dark:text-purple-400 border-purple-300 dark:border-purple-700 ml-2 shadow-sm">PRO</span>
                    </h3>
                    <div class="space-y-4" id="product-insights">
                        <!-- Rendered via JS -->
                    </div>
                </div>
            </div>

            <?php if(!$isPremium): ?>
                <div class="premium-lock-badge" onclick="window.location.href='langganan.php'">
                    <i class="ph-fill ph-crown text-2xl mb-2"></i>
                    <span class="font-bold">Buka Wawasan Produk Lanjutan</span>
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
        const comparisonData = <?php echo json_encode($comparisonData); ?>;
        const globalTrends = <?php echo json_encode($globalTrends); ?>;
        const allProducts = <?php echo json_encode($allProducts); ?>;
        
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b';

        // --- Chart 1: Comparison Bar (Local vs Global) ---
        const compLabels = comparisonData.map(d => d.name.length > 18 ? d.name.substring(0,18)+'…' : d.name);
        const localSoldArr = comparisonData.map(d => d.local_sold);
        const globalSoldArr = comparisonData.map(d => d.global_sold);

        const ctxComp = document.getElementById('chart-comparison').getContext('2d');
        new Chart(ctxComp, {
            type: 'bar',
            data: {
                labels: compLabels.length > 0 ? compLabels : globalTrends.map(g => g.product_name),
                datasets: [
                    { 
                        label: 'Lokal (WarungPOS)', 
                        data: localSoldArr.length > 0 ? localSoldArr : globalTrends.map(() => 0), 
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderRadius: 6,
                        barThickness: 16
                    },
                    { 
                        label: 'Global (PasarKita)', 
                        data: comparisonData.length > 0 ? globalSoldArr : globalTrends.map(g => g.total_sold_global), 
                        backgroundColor: 'rgba(245, 158, 11, 0.8)',
                        borderRadius: 6,
                        barThickness: 16
                    }
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                plugins: { 
                    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 15 } },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                }, 
                scales: { 
                    x: { grid: { display: false }, border: { display: false } },
                    y: { grid: { borderDash: [5,5], color: 'rgba(148, 163, 184, 0.1)' }, border: { display: false } }
                }
            }
        });

        // --- Chart 2: Category Pie ---
        new Chart(document.getElementById('chart-product-category'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($catLabels); ?>,
                datasets: [{ 
                    data: <?php echo json_encode($catCounts); ?>, 
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#f43f5e'], 
                    borderWidth: 0, hoverOffset: 4
                }]
            },
            options: { 
                cutout: '75%', responsive: true, maintainAspectRatio: false, 
                plugins: { 
                    legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 8, padding: 15 } },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                }
            }
        });

        if (isPremium) {
            // --- Chart 3: Detail Comparison (Premium) ---
            const detailLabels = globalTrends.map(g => g.product_name.length > 15 ? g.product_name.substring(0,15)+'…' : g.product_name);
            const detailGlobal = globalTrends.map(g => parseInt(g.total_sold_global));
            const detailLocal = globalTrends.map(g => {
                const match = comparisonData.find(c => c.name && (
                    g.product_name.toLowerCase().includes(c.name.toLowerCase()) ||
                    c.name.toLowerCase().includes(g.product_name.toLowerCase())
                ));
                return match ? match.local_sold : 0;
            });

            new Chart(document.getElementById('chart-detail-comparison'), {
                type: 'bar',
                data: {
                    labels: detailLabels,
                    datasets: [
                        { label: 'Penjualan Anda', data: detailLocal, backgroundColor: 'rgba(16, 185, 129, 0.8)', borderRadius: 6, barThickness: 14 },
                        { label: 'Tren PasarKita', data: detailGlobal, backgroundColor: 'rgba(245, 158, 11, 0.6)', borderRadius: 6, barThickness: 14 }
                    ]
                },
                options: { 
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { 
                        legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, padding: 20 } },
                        tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8 }
                    },
                    scales: {
                        y: { grid: { display: false }, border: { display: false }, ticks: { font: { weight: 'bold' } } },
                        x: { grid: { borderDash: [5,5], color: 'rgba(148, 163, 184, 0.1)' }, border: { display: false } }
                    }
                }
            });

            // --- Smart Insights ---
            const insights = [];
            
            // --- GREEDY ALGORITHM: OPTIMALISASI BUNDLING CUCI GUDANG ---
            if (allProducts && allProducts.length >= 2) {
                // 1. Data Pipeline & Perhitungan Rasio
                const productsData = allProducts.map(p => ({
                    name: p.nama_produk,
                    stock: parseInt(p.stok) || 0,
                    sold: parseInt(p.total_sold) || 0,
                    // Hitung rasio kemandekan: stok tinggi dibagi penjualan rendah
                    // Jika penjualan 0, berikan bobot penalti sangat tinggi
                    ratio: (parseInt(p.total_sold) || 0) === 0 ? (parseInt(p.stok) || 0) * 1000 : (parseInt(p.stok) || 0) / parseInt(p.total_sold)
                }));

                // 2. Greedy Sort 1: Daftar A (Dead Stock / Kemandekan tertinggi)
                const deadStockList = [...productsData].sort((a, b) => b.ratio - a.ratio);
                
                // 3. Greedy Sort 2: Daftar B (Best Seller / Penjualan tertinggi)
                const bestSellerList = [...productsData].sort((a, b) => b.sold - a.sold);

                // 4. Greedy Select: Ambil ujung tombak masing-masing daftar
                let topDead = deadStockList[0];
                let topBest = bestSellerList[0];

                // 5. Cek Kesamaan (Cegah bundling produk yang sama)
                if (topDead.name === topBest.name && bestSellerList.length > 1) {
                    topBest = bestSellerList[1];
                }

                // 6. Buat Rekomendasi (Proses Bundling)
                if (topDead.stock > 0 && topDead.ratio > 0 && topBest.sold > 0) {
                    insights.push({
                        l: 'Rekomendasi Bundling (Greedy Analysis)',
                        v: `"${topDead.name}" menumpuk di gudang (Stok: ${topDead.stock}). Gabungkan dengan "${topBest.name}" (Terlaris: ${topBest.sold} terjual) sebagai paket diskon cuci gudang!`,
                        c: 'text-purple-600', bg: 'bg-purple-100 dark:bg-purple-900/50', i: 'ph-gift'
                    });
                }
            }

            // Find products where local matches global trend
            comparisonData.forEach(item => {
                if (item.has_match && item.global_sold > 0) {
                    const ratio = item.local_sold / item.global_sold;
                    if (ratio >= 0.5) {
                        insights.push({
                            l: 'Produk Anda Kompetitif!',
                            v: `"${item.name}" — Anda menjual ${item.local_sold} unit, sementara tren global ${item.global_sold} unit. Pertahankan!`,
                            c: 'text-emerald-600', bg: 'bg-emerald-100 dark:bg-emerald-900/50', i: 'ph-medal'
                        });
                    } else {
                        insights.push({
                            l: 'Peluang Pasar',
                            v: `"${item.name}" sedang tren di PasarKita (${item.global_sold} unit terjual). Anda baru menjual ${item.local_sold} unit. Tingkatkan stok & promosi!`,
                            c: 'text-blue-600', bg: 'bg-blue-100 dark:bg-blue-900/50', i: 'ph-trend-up'
                        });
                    }
                }
            });

            // Find global trends NOT matched by any local product
            globalTrends.forEach(g => {
                const hasLocal = comparisonData.some(c => c.has_match && 
                    (g.product_name.toLowerCase().includes(c.name.toLowerCase()) || 
                     c.name.toLowerCase().includes(g.product_name.toLowerCase())));
                if (!hasLocal) {
                    insights.push({
                        l: 'Produk Baru Potensial',
                        v: `"${g.product_name}" laku ${g.total_sold_global} unit di PasarKita tapi Anda belum menjualnya. Pertimbangkan untuk menambahkannya!`,
                        c: 'text-amber-600', bg: 'bg-amber-100 dark:bg-amber-900/50', i: 'ph-lightbulb'
                    });
                }
            });

            if (insights.length === 0) {
                insights.push({
                    l: 'Sinkronisasi Diperlukan',
                    v: 'Klik "Sinkronisasi Data" di Dashboard untuk menarik data tren terbaru dari SmartBank, WarungPOS, dan PasarKita.',
                    c: 'text-slate-600', bg: 'bg-slate-100 dark:bg-slate-800', i: 'ph-arrows-clockwise'
                });
            }

            document.getElementById('product-insights').innerHTML = insights.slice(0, 5).map(ins => `
                <div class="p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 shadow-sm flex items-start gap-4 hover:bg-white dark:hover:bg-slate-700 transition-colors">
                    <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center ${ins.bg} ${ins.c}">
                        <i class="ph-fill ${ins.i} text-xl"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${ins.l}</span>
                        <p class="text-xs font-medium text-slate-700 dark:text-slate-300 mt-1">${ins.v}</p>
                    </div>
                </div>
            `).join('');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
