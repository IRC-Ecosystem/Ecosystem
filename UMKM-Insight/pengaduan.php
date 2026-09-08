<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$complaintData = (new App\Controllers\ComplaintController($pdo))->client($_SESSION, $_POST);
extract($complaintData);

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="main-content animated-bg">
    <?php include 'includes/topbar.php'; ?>

    <div class="animate-fade-in stagger-1" style="padding-top: 24px; padding-left: 24px; padding-right: 24px;">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-brand-400 via-teal-500 to-emerald-600 animate-pop-in drop-shadow-sm mb-1">
                Pusat Bantuan & Pengaduan
            </h1>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 animate-pop-in stagger-1">
                Ada kendala? Tim operator kami siap membantu Anda.
            </p>
        </div>

        <?php if($success): ?>
            <div class="bg-emerald-50/80 dark:bg-emerald-900/50 backdrop-blur-md border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 p-4 rounded-xl text-sm mb-6 flex items-center gap-3 animate-pop-in stagger-2 shadow-sm">
                <i class="ph-fill ph-check-circle text-xl"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="bg-rose-50/80 dark:bg-rose-900/50 backdrop-blur-md border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-300 p-4 rounded-xl text-sm mb-6 flex items-center gap-3 animate-pop-in stagger-2 shadow-sm">
                <i class="ph-fill ph-warning-circle text-xl"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Form -->
            <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 animate-pop-in stagger-3 p-8 relative overflow-hidden group">
                <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-brand-500 rounded-full mix-blend-multiply dark:mix-blend-screen opacity-5 dark:opacity-10 group-hover:scale-150 transition-transform duration-700 pointer-events-none"></div>
                <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-slate-800 dark:text-white relative z-10">
                    <div class="p-1.5 rounded-lg bg-brand-100 dark:bg-brand-900/50 text-brand-600"><i class="ph-bold ph-note-pencil"></i></div>
                    Buat Laporan Baru
                </h2>
                <form action="pengaduan.php" method="POST" class="flex flex-col gap-5 relative z-10">
                    <input type="hidden" name="action" value="submit_complaint">
                    <div class="form-group mb-0">
                        <label class="form-label text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Subjek Pengaduan</label>
                        <input type="text" name="subject" class="form-input bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm border-white/80 dark:border-slate-700/80 shadow-inner focus:bg-white dark:focus:bg-slate-800 transition-colors" placeholder="e.g. Masalah Data Dashboard" required>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Detail Keluhan</label>
                        <textarea name="message" class="form-input bg-white/60 dark:bg-slate-900/60 backdrop-blur-sm border-white/80 dark:border-slate-700/80 shadow-inner focus:bg-white dark:focus:bg-slate-800 transition-colors" rows="6" placeholder="Ceritakan detail masalah yang Anda hadapi..." required></textarea>
                    </div>
                    <button type="submit" class="btn bg-gradient-to-r from-brand-500 to-teal-500 hover:from-brand-600 hover:to-teal-600 text-white shadow-md hover:shadow-lg hover:-translate-y-1 transition-all btn-full py-3.5 rounded-xl font-bold mt-2">
                        <i class="ph-bold ph-paper-plane-tilt"></i> Kirim Pengaduan
                    </button>
                </form>
            </div>

            <!-- History -->
            <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 animate-pop-in stagger-4 p-8 flex flex-col relative overflow-hidden group">
                <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-slate-500 rounded-full mix-blend-multiply dark:mix-blend-screen opacity-5 group-hover:scale-150 transition-transform duration-700 pointer-events-none"></div>
                <h2 class="text-xl font-extrabold mb-6 flex items-center gap-2 text-slate-800 dark:text-white relative z-10">
                    <div class="p-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400"><i class="ph-bold ph-clock-counter-clockwise"></i></div>
                    Riwayat Pengaduan
                </h2>
                <div class="flex flex-col gap-4 flex-1 max-h-[450px] overflow-y-auto pr-2 custom-scrollbar relative z-10">
                    <?php if(empty($complaints)): ?>
                        <div class="text-center py-16 text-slate-400 dark:text-slate-500 h-full flex flex-col items-center justify-center">
                            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                                <i class="ph-fill ph-chats text-3xl"></i>
                            </div>
                            <p class="text-sm font-medium">Belum ada riwayat pengaduan.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($complaints as $c): ?>
                            <div class="p-5 rounded-2xl border <?php echo $c['status'] === 'open' ? 'border-amber-200/50 bg-amber-50/50 dark:border-amber-700/30 dark:bg-amber-900/20' : 'border-emerald-200/50 bg-emerald-50/50 dark:border-emerald-700/30 dark:bg-emerald-900/20'; ?> backdrop-blur-sm shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-bold text-sm text-slate-800 dark:text-slate-200"><?php echo $c['subject']; ?></h3>
                                    <span class="badge <?php echo $c['status'] === 'open' ? 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700' : 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700'; ?>">
                                        <?php echo strtoupper($c['status']); ?>
                                    </span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 mb-4 leading-relaxed"><?php echo nl2br($c['message']); ?></p>
                                <div class="flex justify-between items-center text-[10px] text-slate-500 dark:text-slate-500 font-medium pt-3 border-t <?php echo $c['status'] === 'open' ? 'border-amber-200/30 dark:border-amber-700/30' : 'border-emerald-200/30 dark:border-emerald-700/30'; ?>">
                                    <span class="flex items-center gap-1.5"><i class="ph-bold ph-calendar"></i> <?php echo date('d M Y, H:i', strtotime($c['created_at'])); ?></span>
                                    <?php if($c['status'] === 'resolved'): ?>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1.5"><i class="ph-bold ph-check-circle"></i> Selesai</span>
                                    <?php else: ?>
                                        <span class="text-amber-600 dark:text-amber-400 flex items-center gap-1.5"><i class="ph-bold ph-hourglass"></i> Sedang ditinjau</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <footer class="mt-8 mb-4 text-center text-[11px] text-slate-400 py-6 border-t border-slate-100 dark:border-slate-800">
            &copy; <?php echo date('Y'); ?> Ekosistem Ekonomi UMKM. Simulasi Sistem Informasi RPL 2.
        </footer>
    </div>
</div>

<style>
/* Custom scrollbar for history panel */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.3); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.5); }
</style>

<?php include 'includes/footer.php'; ?>
