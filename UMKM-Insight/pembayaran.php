<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$paymentData = (new App\Controllers\SubscriptionController($pdo))->checkout($_SESSION, $_POST);
extract($paymentData);

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<div class="main-content animated-bg">
    <div style="max-width:600px; margin:0 auto; padding-top:40px;">
        <div class="text-center mb-8 animate-fade-in stagger-1">
            <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white mb-2">Checkout Premium</h1>
            <p class="text-slate-500">Selesaikan pembayaran untuk menikmati fitur analitik tanpa batas.</p>
        </div>

        <?php if($message): ?>
            <div class="toast toast-success mb-6" style="position:static;">
                <i class="ph-fill ph-check-circle text-lg"></i>
                <div><?php echo $message; ?></div>
            </div>
            <div class="text-center mt-4">
                <a href="dashboard.php" class="btn btn-primary">Kembali ke Dashboard</a>
            </div>
        <?php else: ?>
        
            <?php if($error): ?>
                <div class="toast toast-error mb-6" style="position:static;">
                    <i class="ph-fill ph-warning-circle text-lg"></i>
                    <div><?php echo $error; ?></div>
                </div>
            <?php endif; ?>

            <div class="card bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 p-8 animate-fade-in stagger-2">
                <div class="flex justify-between items-center border-b border-slate-100 dark:border-slate-700 pb-4 mb-6">
                    <div>
                        <h3 class="font-bold text-lg">Paket Langganan</h3>
                        <p class="text-sm text-slate-500">Premium 30 Hari</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-black text-brand-600">Rp 99.000</p>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="text-sm font-bold mb-3 flex items-center gap-2">
                        <i class="ph-fill ph-bank text-blue-500"></i> Pembayaran via SmartBank Wallet (Otomatis &amp; Instant)
                    </h4>
                    
                    <div class="bg-blue-50 dark:bg-slate-800 border border-blue-100 dark:border-slate-700 rounded-xl p-5 mb-4">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-semibold text-slate-500">Status Wallet SmartBank:</span>
                            <?php if(!empty($user['smartbank_id'])): ?>
                                <span class="text-xs font-bold bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 px-2.5 py-1 rounded-full flex items-center gap-1">
                                    <i class="ph-fill ph-check-circle"></i> Terhubung: <?php echo htmlspecialchars($user['smartbank_id']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2.5 py-1 rounded-full">
                                    ○ Belum Terhubung
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                            Bayar langganan Rp 99.000 secara otomatis dari saldo SmartBank Wallet Anda menggunakan verifikasi kode OTP 6-digit.
                        </p>

                        <button type="button" onclick="openOtpModal()"
                                class="btn bg-blue-600 hover:bg-blue-700 text-white shadow-lg w-full py-3 font-bold flex items-center justify-center gap-2 text-sm rounded-xl"
                                data-tooltip="Lakukan verifikasi OTP 6-digit dari SmartBank Wallet untuk menyelesaikan pembayaran">
                            <i class="ph-fill ph-shield-check text-lg"></i> Bayar via SmartBank OTP (Instant Upgrade)
                        </button>
                    </div>
                </div>

                <div class="text-center mt-6 pt-4 border-t border-slate-100 dark:border-slate-700">
                    <p class="text-xs text-slate-500 mb-2">Atau ingin transfer manual?</p>
                    <a href="langganan.php" class="text-brand-600 text-sm font-bold hover:underline"
                       data-tooltip="Upload bukti transfer bank manual ke halaman langganan">Unggah Bukti Transfer Manual</a>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<!-- ================================================== -->
<!-- MODAL OVERLAY OTP SMARTBANK                        -->
<!-- ================================================== -->
<div id="otpModalOverlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,0.75); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
    <div style="background:#ffffff; width:90%; max-width:440px; border-radius:20px; padding:28px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.35); border:1px solid rgba(255,255,255,0.1); text-align:center;">
        
        <div style="width:60px; height:60px; background:rgba(37,99,235,0.1); color:#2563eb; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px; font-size:28px;">
            <i class="ph-fill ph-shield-check"></i>
        </div>

        <h3 style="font-size:20px; font-weight:800; color:#1e293b; margin-bottom:6px;">Verifikasi OTP SmartBank</h3>
        <p style="font-size:13px; color:#64748b; margin-bottom:20px; line-height:1.5;">
            Sistem telah mensimulasikan pengiriman kode 6-digit OTP ke <strong>Inbox Notifikasi SmartBank Wallet</strong> Anda.
        </p>

        <form action="pembayaran.php" method="POST">
            <div style="margin-bottom:16px; text-align:left;">
                <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Nomor HP / User ID SmartBank</label>
                <input type="text" name="smartbank_phone" value="<?php echo htmlspecialchars($user['smartbank_id'] ?? '081234567890'); ?>" required placeholder="Contoh: 081234567890" style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid #cbd5e1; font-size:14px; font-weight:600;">
            </div>

            <div style="margin-bottom:20px; text-align:left;">
                <label style="font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; display:block; margin-bottom:6px;">Kode OTP 6-Digit</label>
                <input type="text" name="otp_code" maxlength="6" pattern="\d{6}" required placeholder="Masukkan 6 angka, misal: 123456" style="width:100%; padding:12px 14px; border-radius:10px; border:2px solid #3b82f6; font-size:18px; font-weight:700; letter-spacing:4px; text-align:center; background:#f8fafc;">
                <small style="font-size:11px; color:#3b82f6; display:block; margin-top:6px;">💡 Kode OTP Uji Coba: <code>123456</code> atau <code>654321</code></small>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="button" onclick="closeOtpModal()" class="btn btn-outline" style="flex:1; padding:10px;">Batal</button>
                <button type="submit" name="pay_smartbank_otp" class="btn bg-blue-600 hover:bg-blue-700 text-white" style="flex:2; padding:10px; font-weight:700;">Konfirmasi &amp; Upgrade</button>
            </div>
        </form>
    </div>
</div>

<script>
function openOtpModal() {
    document.getElementById('otpModalOverlay').style.display = 'flex';
}
function closeOtpModal() {
    document.getElementById('otpModalOverlay').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
