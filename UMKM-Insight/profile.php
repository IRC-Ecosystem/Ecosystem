<?php
require_once 'config/db.php';
require_once 'includes/auth.php';

requireRole('client');

$profileData = (new App\Controllers\ProfileController($pdo))->handle($_SESSION, $_POST, $_FILES);
extract($profileData);

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<div class="main-content">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:24px;">
        <div>
            <h1 class="text-2xl font-bold">Pengaturan Profil</h1>
            <p class="text-slate-500 text-sm">Kelola informasi bisnis dan integrasi API Anda.</p>
        </div>
    </div>

    <?php if($message): ?>
        <div class="toast toast-<?php echo $messageType; ?>" style="position:static; margin-bottom:20px; animation:fadeIn 0.3s;">
            <i class="ph-fill <?php echo $messageType === 'success' ? 'ph-check-circle' : 'ph-warning-circle'; ?> text-lg"></i>
            <div><?php echo $message; ?></div>
        </div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        
        <!-- Kolom Kiri: Profil Umum -->
        <div class="card p-6">
            <h2 class="text-lg font-bold mb-4 border-b border-slate-100 pb-2">Informasi Bisnis</h2>
            <form action="profile.php" method="POST" enctype="multipart/form-data">
                
                <div class="mb-5 flex flex-col items-center">
                    <div style="width:100px; height:100px; border-radius:50%; background:var(--brand-100); display:flex; align-items:center; justify-content:center; overflow:hidden; margin-bottom:10px; border:2px solid var(--brand-500);">
                        <?php if($user['foto_profil']): ?>
                            <img src="assets/uploads/profil/<?php echo htmlspecialchars($user['foto_profil']); ?>" alt="Foto" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <i class="ph ph-storefront text-4xl text-brand-600"></i>
                        <?php endif; ?>
                    </div>
                    <label class="btn btn-sm btn-outline cursor-pointer text-xs">
                        Ubah Logo/Foto
                        <input type="file" name="foto_profil" style="display:none;" accept="image/*">
                    </label>
                </div>

                <div class="mb-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Nama Pemilik / Lengkap</label>
                    <input type="text" class="form-input bg-slate-100 dark:bg-slate-700 dark:text-slate-200 cursor-not-allowed" value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" disabled>
                    <small class="text-[10px] text-slate-400">Nama lengkap tidak dapat diubah sendiri.</small>
                </div>
                
                <div class="mb-4">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Nama Bisnis</label>
                    <input type="text" name="nama_bisnis" class="form-input" value="<?php echo htmlspecialchars($user['nama_bisnis'] ?? ''); ?>" placeholder="Contoh: Toko Berkah" required>
                </div>

                <div class="mb-6">
                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Kategori Produk Utama</label>
                    <select name="kategori" class="form-select" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Makanan & Minuman" <?php echo ($user['kategori'] == 'Makanan & Minuman') ? 'selected' : ''; ?>>Makanan & Minuman</option>
                        <option value="Fashion & Kerajinan" <?php echo ($user['kategori'] == 'Fashion & Kerajinan') ? 'selected' : ''; ?>>Fashion & Kerajinan</option>
                        <option value="Jasa & Layanan" <?php echo ($user['kategori'] == 'Jasa & Layanan') ? 'selected' : ''; ?>>Jasa & Layanan</option>
                        <option value="Lainnya" <?php echo ($user['kategori'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                    </select>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary btn-full">
                    <i class="ph ph-floppy-disk"></i> Simpan Perubahan Profil
                </button>
            </form>
        </div>

        <!-- Kolom Kanan: Integrasi API -->
        <div class="flex flex-col gap-6">
            
            <!-- SmartBank -->
            <div class="card p-6 border-l-4 border-l-blue-500">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                        <i class="ph-fill ph-bank"></i>
                    </div>
                    <div>
                        <h2 class="text-md font-bold">Koneksi SmartBank</h2>
                        <div class="text-[11px] <?php echo $user['smartbank_id'] ? 'text-green-500' : 'text-slate-400'; ?>">
                            <?php echo $user['smartbank_id'] ? '● Terkoneksi' : '○ Belum Terkoneksi (Data 0)'; ?>
                        </div>
                    </div>
                </div>

                <?php if($user['smartbank_id']): ?>
                    <div class="bg-blue-50 dark:bg-slate-800 p-4 rounded-lg mb-4 text-sm flex justify-between items-center border border-blue-100 dark:border-slate-700">
                        <div>
                            <span class="text-slate-500 text-xs block">SmartBank ID Tersimpan</span>
                            <strong class="text-slate-800 dark:text-slate-200"><?php echo maskId($user['smartbank_id']); ?></strong>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="document.getElementById('edit-sb').classList.toggle('hidden')" class="btn btn-sm btn-outline"><i class="ph ph-pencil"></i> Ubah</button>
                            <form action="profile.php" method="POST" style="display:inline;">
                                <button type="submit" name="disconnect_smartbank" class="btn btn-sm btn-ghost text-rose-500 hover:bg-rose-50" onclick="return confirm('Yakin ingin memutus koneksi API? Data yang sudah tersinkron sebelumnya akan tetap ada sebagai histori, namun tidak akan bertambah.')"><i class="ph ph-link-break"></i> Putus</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" id="edit-sb" class="<?php echo $user['smartbank_id'] ? 'hidden' : ''; ?>">
                    <div class="mb-3">
                        <label class="text-[11px] font-bold text-slate-500">SMARTBANK ID</label>
                        <input type="text" name="smartbank_id" class="form-input" placeholder="Misal: SB-UMKM-001" value="<?php echo htmlspecialchars($user['smartbank_id'] ?? ''); ?>" required>
                    </div>
                    <p class="text-[10px] text-slate-400 mb-4">Data transaksi baru masuk melalui endpoint integrasi API, bukan dari PIN lokal.</p>
                    <button type="submit" name="connect_smartbank" class="btn bg-blue-600 text-white hover:bg-blue-700 btn-sm btn-full">Hubungkan SmartBank</button>
                </form>
            </div>

            <!-- WarungPos -->
            <div class="card p-6 border-l-4 border-l-orange-500">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center text-xl">
                        <i class="ph-fill ph-storefront"></i>
                    </div>
                    <div>
                        <h2 class="text-md font-bold">Koneksi WarungPos</h2>
                        <div class="text-[11px] <?php echo $user['warungpos_id'] ? 'text-green-500' : 'text-slate-400'; ?>">
                            <?php echo $user['warungpos_id'] ? '● Terkoneksi' : '○ Belum Terkoneksi (Data 0)'; ?>
                        </div>
                    </div>
                </div>

                <?php if($user['warungpos_id']): ?>
                    <div class="bg-orange-50 dark:bg-slate-800 p-4 rounded-lg mb-4 text-sm flex justify-between items-center border border-orange-100 dark:border-slate-700">
                        <div>
                            <span class="text-slate-500 text-xs block">WarungPos ID Tersimpan</span>
                            <strong class="text-slate-800 dark:text-slate-200"><?php echo maskId($user['warungpos_id']); ?></strong>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="document.getElementById('edit-wp').classList.toggle('hidden')" class="btn btn-sm btn-outline"><i class="ph ph-pencil"></i> Ubah</button>
                            <form action="profile.php" method="POST" style="display:inline;">
                                <button type="submit" name="disconnect_warungpos" class="btn btn-sm btn-ghost text-rose-500 hover:bg-rose-50" onclick="return confirm('Yakin ingin memutus koneksi API? Data yang sudah tersinkron sebelumnya akan tetap ada sebagai histori, namun tidak akan bertambah.')"><i class="ph ph-link-break"></i> Putus</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="profile.php" method="POST" id="edit-wp" class="<?php echo $user['warungpos_id'] ? 'hidden' : ''; ?>">
                    <div class="mb-3">
                        <label class="text-[11px] font-bold text-slate-500">WARUNGPOS ID</label>
                        <input type="text" name="warungpos_id" class="form-input" placeholder="Misal: WP-001" value="<?php echo htmlspecialchars($user['warungpos_id'] ?? ''); ?>" required>
                    </div>
                    <p class="text-[10px] text-slate-400 mb-4">Data penjualan baru masuk melalui endpoint integrasi API, bukan dari PIN lokal.</p>
                    <button type="submit" name="connect_warungpos" class="btn bg-orange-500 text-white hover:bg-orange-600 btn-sm btn-full">Hubungkan WarungPos</button>
                </form>
            </div>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
