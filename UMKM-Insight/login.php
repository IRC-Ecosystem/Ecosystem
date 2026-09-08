<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

$authController = new App\Controllers\AuthController($pdo);

// Jika sudah login, lempar ke dashboard sesuai role
if (isLoggedIn()) {
    redirectTo($authController->redirectPathForRole($_SESSION['role']));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = $authController->login($_POST, $_SESSION);
}

$pageTitle = "Masuk";
include 'includes/header.php';
?>

<script>
    // Force dark mode specifically for this login page as requested by user
    document.documentElement.classList.add('dark');
</script>

<style>
    body { height: 100vh; display: flex; overflow: hidden; margin: 0; }

    /* Removed Theme Switcher in Login to force Dark Mode */
    /* Left Panel */
    .auth-left-panel {
        flex: 1.2;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 60px 48px;
        background: linear-gradient(135deg, #0f172a 0%, #0d2a28 40%, #0d4a44 70%, #14b8a6 100%);
    }
    .auth-left-panel::before {
        content: '';
        position: absolute; inset: 0;
        background: linear-gradient(135deg, #0f172a 0%, #134e4a 50%, #0d9488 100%);
        z-index: 0;
    }

    /* Animated orbs in left panel */
    .orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(70px);
        animation: floatBlob 12s ease-in-out infinite alternate;
    }
    .orb-1 { width: 350px; height: 350px; background: rgba(20,184,166,0.3); top: -80px; left: -80px; animation-delay: 0s; }
    .orb-2 { width: 280px; height: 280px; background: rgba(99,102,241,0.25); bottom: -60px; right: -60px; animation-delay: -6s; }
    .orb-3 { width: 180px; height: 180px; background: rgba(245,158,11,0.15); top: 50%; left: 50%; transform: translate(-50%, -50%); animation-delay: -3s; }

    /* Floating grid lines */
    .grid-lines {
        position: absolute; inset: 0; z-index: 1;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 60px 60px;
    }

    /* Feature pills */
    .feature-pill {
        display: flex; align-items: center; gap: 14px;
        background: rgba(255,255,255,0.07);
        border: 1px solid rgba(255,255,255,0.12);
        backdrop-filter: blur(10px);
        padding: 14px 18px;
        border-radius: 16px;
        transition: all 0.3s ease;
    }
    .feature-pill:hover { background: rgba(255,255,255,0.12); transform: translateX(4px); border-color: rgba(255,255,255,0.2); }
    .feature-pill-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; flex-shrink: 0; }

    /* Stat ticker */
    .stat-ticker {
        display: flex; gap: 24px;
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 16px 24px;
    }
    .stat-item { display: flex; flex-direction: column; align-items: center; gap: 2px; }

    /* Right Panel */
    .auth-right-panel {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 32px;
        overflow-y: auto;
        background: var(--surface);
        position: relative;
    }
    .auth-right-panel::before {
        content: '';
        position: absolute; inset: 0;
        background:
            radial-gradient(ellipse 60% 50% at 80% 20%, rgba(20,184,166,0.08) 0%, transparent 70%),
            radial-gradient(ellipse 50% 60% at 20% 80%, rgba(99,102,241,0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    /* ── Floating particles on right panel ── */
    .particle {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
        animation: particleDrift var(--dur, 8s) ease-in-out infinite alternate;
        opacity: 0;
        animation-fill-mode: both;
    }
    @keyframes particleDrift {
        0%   { transform: translate(0, 0) scale(1); opacity: 0; }
        20%  { opacity: var(--op, 0.6); }
        80%  { opacity: var(--op, 0.6); }
        100% { transform: translate(var(--dx, 30px), var(--dy, -40px)) scale(1.3); opacity: 0; }
    }

    /* ── Animated ring behind the form card ── */
    .ring {
        position: absolute;
        border-radius: 50%;
        border: 1px solid;
        animation: ringPulse var(--dur, 4s) ease-in-out infinite;
        pointer-events: none;
    }
    @keyframes ringPulse {
        0%, 100% { transform: translate(-50%, -50%) scale(1);   opacity: 0.4; }
        50%       { transform: translate(-50%, -50%) scale(1.12); opacity: 0.08; }
    }

    /* ── Input shimmer / glow on focus ── */
    .auth-input {
        transition: border-color 0.3s, box-shadow 0.3s, background 0.3s;
    }
    .auth-input:focus {
        border-color: #14b8a6 !important;
        box-shadow: 0 0 0 3px rgba(20,184,166,0.15), 0 0 16px rgba(20,184,166,0.1);
        background: white !important;
    }
    .dark .auth-input:focus {
        background: #1e293b !important;
        box-shadow: 0 0 0 3px rgba(20,184,166,0.2), 0 0 20px rgba(20,184,166,0.1);
    }

    /* ── Submit button pulse ── */
    .btn-login {
        position: relative;
        overflow: hidden;
    }
    .btn-login::after {
        content: '';
        position: absolute;
        inset: -2px;
        border-radius: inherit;
        background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.15) 50%, transparent 100%);
        transform: translateX(-100%);
        animation: btnShimmer 3s ease-in-out infinite;
    }
    @keyframes btnShimmer {
        0%   { transform: translateX(-100%); }
        50%  { transform: translateX(100%); }
        100% { transform: translateX(100%); }
    }

    /* ── Typing cursor on heading ── */
    .typing-cursor::after {
        content: '|';
        display: inline-block;
        margin-left: 2px;
        animation: blink 1s step-end infinite;
        color: #14b8a6;
    }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

    /* ── Form card slide-up entry ── */
    .form-card {
        animation: slideUpFade 0.7s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    @keyframes slideUpFade {
        from { opacity: 0; transform: translateY(32px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 900px) { .auth-left-panel { display: none; } }
</style>

<!-- ============ LEFT PANEL ============ -->
<div class="auth-left-panel">
    <!-- Background elements -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-lines"></div>

    <!-- Content -->
    <div class="relative z-10 max-w-md w-full text-white animate-fade-in">
        <!-- Logo -->
        <div class="flex items-center gap-3 mb-12 animate-pop-in stagger-1">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-brand-400 to-teal-600 flex items-center justify-center shadow-lg pulse-glow">
                <img src="assets/image/logo_desai.png" alt="Logo UMKM Insight" class="w-8 h-8 object-contain drop-shadow-md">
            </div>
            <span class="text-xl font-extrabold tracking-tight">UMKM Insight</span>
        </div>

        <!-- Headline -->
        <div class="mb-10 animate-pop-in stagger-2">
            <h2 class="text-4xl font-black leading-tight tracking-tight mb-4">
                Analitik Bisnis<br>
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-brand-300 to-teal-400">Berbasis Data</span>
            </h2>
            <p class="text-slate-300 text-sm leading-relaxed">
                Pantau kinerja UMKM Anda secara real-time. Dashboard interaktif terhubung langsung ke transaksi SmartBank Anda.
            </p>
        </div>

        <!-- Feature pills -->
        <div class="flex flex-col gap-3 mb-10 animate-pop-in stagger-3">
            <div class="feature-pill">
                <div class="feature-pill-icon bg-emerald-500/20 text-emerald-400">
                    <i class="ph-fill ph-trend-up"></i>
                </div>
                <div>
                    <p class="text-sm font-bold">Laporan Penjualan Real-time</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tren harian hingga bulanan secara otomatis</p>
                </div>
            </div>
            <div class="feature-pill">
                <div class="feature-pill-icon bg-blue-500/20 text-blue-400">
                    <i class="ph-fill ph-money"></i>
                </div>
                <div>
                    <p class="text-sm font-bold">Analisis Arus Kas Mendalam</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Pantau pemasukan, pengeluaran & proyeksi</p>
                </div>
            </div>
            <div class="feature-pill">
                <div class="feature-pill-icon bg-amber-500/20 text-amber-400">
                    <i class="ph-fill ph-crown"></i>
                </div>
                <div>
                    <p class="text-sm font-bold">Wawasan Premium untuk Scale-up</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Optimalkan strategi bisnis dengan data nyata</p>
                </div>
            </div>
        </div>

        <!-- Stats ticker -->
        <div class="stat-ticker animate-pop-in stagger-4">
            <div class="stat-item">
                <span class="text-2xl font-black text-brand-300">500+</span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest">UMKM Aktif</span>
            </div>
            <div class="w-px bg-white/10"></div>
            <div class="stat-item">
                <span class="text-2xl font-black text-indigo-300">99.9%</span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest">Uptime</span>
            </div>
            <div class="w-px bg-white/10"></div>
            <div class="stat-item">
                <span class="text-2xl font-black text-amber-300">4.9 ★</span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest">Rating</span>
            </div>
        </div>
    </div>
</div>

<!-- ============ RIGHT PANEL ============ -->
<div class="auth-right-panel">

    <!-- Background Animations Container (Prevents Scrolling) -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0">
        <!-- Floating particles -->
        <div class="particle" style="--dur:9s;--dx:40px;--dy:-60px;--op:0.5; width:8px;height:8px;background:#14b8a6; top:20%;left:15%;animation-delay:0s;"></div>
        <div class="particle" style="--dur:7s;--dx:-30px;--dy:-50px;--op:0.4; width:5px;height:5px;background:#6366f1; top:60%;left:80%;animation-delay:-2s;"></div>
        <div class="particle" style="--dur:11s;--dx:50px;--dy:-30px;--op:0.5; width:6px;height:6px;background:#f59e0b; top:75%;left:10%;animation-delay:-4s;"></div>
        <div class="particle" style="--dur:8s;--dx:-40px;--dy:-70px;--op:0.35; width:4px;height:4px;background:#14b8a6; top:30%;left:75%;animation-delay:-1s;"></div>
        <div class="particle" style="--dur:13s;--dx:20px;--dy:-40px;--op:0.45; width:7px;height:7px;background:#a78bfa; top:85%;left:55%;animation-delay:-6s;"></div>
        <div class="particle" style="--dur:10s;--dx:-50px;--dy:-20px;--op:0.3; width:5px;height:5px;background:#34d399; top:10%;left:45%;animation-delay:-3s;"></div>
        <div class="particle" style="--dur:6s;--dx:30px;--dy:-55px;--op:0.5; width:6px;height:6px;background:#f472b6; top:50%;left:30%;animation-delay:-5s;"></div>
        <div class="particle" style="--dur:12s;--dx:-20px;--dy:-45px;--op:0.4; width:4px;height:4px;background:#38bdf8; top:90%;left:85%;animation-delay:-7s;"></div>

        <!-- Pulsing rings centered behind the form -->
        <div class="ring" style="--dur:5s; width:380px;height:380px;border-color:rgba(20,184,166,0.15); top:50%;left:50%;"></div>
        <div class="ring" style="--dur:7s; width:520px;height:520px;border-color:rgba(99,102,241,0.1);  top:50%;left:50%;animation-delay:-2s;"></div>
        <div class="ring" style="--dur:9s; width:660px;height:660px;border-color:rgba(245,158,11,0.07); top:50%;left:50%;animation-delay:-4s;"></div>
    </div>

    <!-- Form card -->
    <div class="w-full max-w-[400px] relative z-10 form-card">

        <div class="mb-8">
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white mb-2 typing-cursor">Selamat Datang Kembali</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm">Masukkan kredensial Anda untuk mengakses dasbor.</p>
        </div>

        <!-- Animated trust badges -->
        <div class="flex gap-2 mb-6">
            <div class="flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700/50 text-emerald-700 dark:text-emerald-400 text-[10px] font-bold px-3 py-1.5 rounded-full animate-pop-in stagger-1">
                <i class="ph-fill ph-shield-check text-sm"></i> SSL Aman
            </div>
            <div class="flex items-center gap-1.5 bg-brand-50 dark:bg-teal-900/30 border border-brand-200 dark:border-teal-700/50 text-brand-700 dark:text-brand-400 text-[10px] font-bold px-3 py-1.5 rounded-full animate-pop-in stagger-2">
                <i class="ph-fill ph-lock text-sm"></i> Enkripsi BCrypt
            </div>
            <div class="flex items-center gap-1.5 bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700/50 text-indigo-700 dark:text-indigo-400 text-[10px] font-bold px-3 py-1.5 rounded-full animate-pop-in stagger-3">
                <i class="ph-fill ph-cloud-check text-sm"></i> Real-time
            </div>
        </div>

        <?php if($error): ?>
            <div class="bg-rose-50 dark:bg-rose-900/50 border border-rose-200 dark:border-rose-800 text-rose-600 dark:text-rose-300 p-3 rounded-xl text-sm mb-6 flex items-center gap-2 animate-pop-in">
                <i class="ph-fill ph-warning-circle text-lg"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="flex flex-col gap-5">
            <div class="form-group animate-pop-in stagger-3">
                <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-xs uppercase tracking-wider">Username</label>
                <div class="relative group">
                    <i class="ph ph-user absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors duration-200 z-10"></i>
                    <input type="text" name="username" class="form-input auth-input pl-9 dark:bg-slate-800/60" placeholder="Masukkan username" required>
                </div>
            </div>
            <div class="form-group animate-pop-in stagger-4">
                <div class="flex justify-between items-center mb-1">
                    <label class="form-label text-slate-700 dark:text-slate-300 font-bold text-xs uppercase tracking-wider mb-0">Password</label>
                    <a href="#" class="text-[10px] text-brand-600 dark:text-brand-400 font-bold hover:underline">Lupa Password?</a>
                </div>
                <div class="relative group">
                    <i class="ph ph-lock absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-brand-500 transition-colors duration-200 z-10"></i>
                    <input type="password" name="password" id="pw-input" class="form-input auth-input pl-9 pr-10 dark:bg-slate-800/60" placeholder="••••••••" required>
                    <button type="button" onclick="togglePw()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-brand-500 dark:hover:text-brand-400 transition-colors z-10">
                        <i class="ph ph-eye" id="pw-eye"></i>
                    </button>
                </div>
            </div>

            <div class="animate-pop-in stagger-5">
                <button type="submit" id="login-btn" class="btn-login btn bg-gradient-to-r from-brand-600 to-teal-500 hover:from-brand-700 hover:to-teal-600 text-white shadow-lg shadow-brand-500/25 btn-full py-3 mt-1 transform hover:-translate-y-1 hover:shadow-xl hover:shadow-brand-500/30 transition-all font-bold rounded-xl">
                    <i class="ph-bold ph-sign-in" id="btn-icon"></i>
                    <span id="btn-text">Masuk ke Dasbor</span>
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-slate-500 dark:text-slate-400 mt-8 animate-pop-in stagger-5">
            Belum punya akun? <a href="register.php" class="text-brand-600 dark:text-brand-400 font-bold hover:underline">Daftar Gratis →</a>
        </p>

        <div class="mt-6 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700/50 text-[10px] text-slate-400 leading-relaxed text-center animate-pop-in stagger-6">
            <strong class="text-slate-500 dark:text-slate-300">DEMO ACCOUNTS:</strong><br>
            Client: <code>budi</code> / <code>password</code> &nbsp;|&nbsp;
            client (premium): <code>sari</code> / <code>password</code> &nbsp;|&nbsp;
            operator: <code>op_jaya</code> / <code>password</code> &nbsp;|&nbsp;
            Admin: <code>admin_super</code> / <code>password</code> &nbsp;|&nbsp;
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePw() {
    const inp = document.getElementById('pw-input');
    const eye = document.getElementById('pw-eye');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    eye.className = inp.type === 'password' ? 'ph ph-eye' : 'ph ph-eye-slash';
}

// Animate button on submit
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('login-btn');
    const icon = document.getElementById('btn-icon');
    const text = document.getElementById('btn-text');
    btn.disabled = true;
    btn.style.opacity = '0.8';
    icon.className = 'ph ph-circle-notch animate-spin';
    text.textContent = 'Memverifikasi...';
});

// Remove typing cursor after 3 seconds
setTimeout(() => {
    const h1 = document.querySelector('.typing-cursor');
    if (h1) h1.classList.remove('typing-cursor');
}, 3500);
</script>

<?php include 'includes/footer.php'; ?>

