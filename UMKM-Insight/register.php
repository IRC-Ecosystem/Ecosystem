<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$authController = new App\Controllers\AuthController($pdo);

if (isLoggedIn()) {
    redirectTo($authController->redirectPathForRole($_SESSION['role']));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $authController->register($_POST);
    $success = $result['success'] ? $result['message'] : '';
    $error = $result['success'] ? '' : $result['message'];
}

$pageTitle = "Daftar Akun";
include 'includes/header.php';
?>

<script>
    document.documentElement.classList.add('dark');
</script>

<style>
    body { min-height: 100vh; display: flex; align-items: center; justify-content: center; position: relative; overflow-x: hidden; padding: 2rem 1rem; box-sizing: border-box; }
</style>

<div class="animated-bg w-full h-full fixed top-0 left-0 -z-10"></div>

<div class="w-full max-w-[560px] px-6 relative z-10 animate-fade-in stagger-1 my-auto">
    <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 p-8 md:p-10 shadow-2xl relative overflow-hidden group">
        <div class="relative z-10">
            <div class="flex items-center gap-2 mb-6 justify-center animate-pop-in stagger-2">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center pulse-glow shadow-lg shadow-indigo-500/30">
                    <img src="assets/image/logo_desai.png" alt="Logo UMKM Insight" class="w-9 h-9 object-contain drop-shadow-md floating-element">
                </div>
                <span class="text-2xl font-extrabold tracking-tight text-slate-800 dark:text-white">UMKM Insight</span>
            </div>

            <div class="text-center mb-8 animate-pop-in stagger-3">
                <h1 class="text-2xl font-bold mb-2 text-slate-800 dark:text-white">Buat Akun UMKM</h1>
                <p class="text-slate-500 dark:text-slate-400 text-sm">Daftar untuk membaca data transaksi dan insight bisnis Anda.</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-rose-50/80 dark:bg-rose-900/50 backdrop-blur-md border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-300 p-3 rounded-xl text-sm mb-6 flex items-center gap-2 animate-pop-in">
                    <i class="ph-fill ph-warning-circle text-lg"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-emerald-50/80 dark:bg-emerald-900/50 backdrop-blur-md border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 p-4 rounded-xl text-sm mb-6 flex flex-col sm:flex-row items-center gap-3 animate-pop-in">
                    <div class="flex items-center gap-2">
                        <i class="ph-fill ph-check-circle text-xl"></i> <?php echo $success; ?>
                    </div>
                    <a href="login.php" class="btn bg-gradient-to-r from-emerald-500 to-teal-500 text-white btn-sm shadow-md sm:ml-auto whitespace-nowrap">Login Sekarang</a>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="flex flex-col gap-4 animate-pop-in stagger-4">
                <div class="form-group">
                    <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase tracking-wider mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-input bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-white/60 dark:border-slate-700/60 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-inner" placeholder="Nama pemilik usaha" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase tracking-wider mb-1">Email Aktif</label>
                        <input type="email" name="email" class="form-input bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-white/60 dark:border-slate-700/60 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-inner" placeholder="nama@email.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase tracking-wider mb-1">Nama Bisnis <span class="text-slate-400 font-normal lowercase">(opsional)</span></label>
                        <input type="text" name="bisnis" class="form-input bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-white/60 dark:border-slate-700/60 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-inner" placeholder="Nama usaha">
                    </div>
                </div>

                <div class="divider h-px bg-slate-200/50 dark:bg-slate-700/50 my-3"></div>

                <div class="form-group">
                    <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase tracking-wider mb-1">Username Baru</label>
                    <div class="relative">
                        <i class="ph ph-at absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="username" class="form-input pl-9 bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-white/60 dark:border-slate-700/60 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-inner" placeholder="pilih username unik" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" class="form-input bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-white/60 dark:border-slate-700/60 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-inner" placeholder="Minimal 8 karakter" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-[10px] uppercase tracking-wider mb-1">Konfirmasi</label>
                        <input type="password" name="confirm_password" class="form-input bg-white/50 dark:bg-slate-800/50 backdrop-blur-sm border-white/60 dark:border-slate-700/60 focus:bg-white dark:focus:bg-slate-800 transition-all shadow-inner" placeholder="Ulangi password" required>
                    </div>
                </div>

                <button type="submit" class="btn bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white shadow-lg shadow-indigo-500/30 btn-full py-3 mt-4 transform hover:-translate-y-1 transition-all">
                    <i class="ph-bold ph-user-plus"></i> Bergabung Sekarang
                </button>
            </form>

            <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-8 animate-pop-in stagger-5">
                Sudah punya akun? <a href="login.php" class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">Masuk di sini</a>
            </p>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
