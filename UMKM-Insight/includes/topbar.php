<?php
/**
 * Topbar Component
 * Menampilkan info user dan tombol menu mobile
 */
$user_name = $_SESSION['nama_lengkap'] ?? 'User';
$initial = strtoupper(substr($user_name, 0, 1));
?>

<header class="app-topbar">
    <div style="display:flex;align-items:center;gap:12px;">
        <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm" style="display:none;padding:6px;" id="mobile-menu-btn">
            <i class="ph ph-list" style="font-size:1.5rem;"></i>
        </button>
        <div style="font-size:0.8125rem;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
            <i class="ph ph-calendar-blank"></i>
            <span><?php echo date('l, d F Y'); ?></span>
        </div>
    </div>
    
    <div style="display:flex;align-items:center;gap:16px;">
        <!-- Theme Toggle -->
        <button style="position:relative;background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.25rem;" onclick="toggleTheme()" id="theme-toggle" title="Toggle Dark/Light Mode">
            <i class="ph ph-moon dark:hidden"></i>
            <i class="ph ph-sun hidden dark:block text-amber-400"></i>
        </button>

        <!-- Notification Bell -->
        <button style="position:relative;background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.25rem;" onclick="toggleNotifPanel()" id="notif-bell">
            <i class="ph ph-bell"></i>
            <!-- Badge bisa ditambahkan secara dinamis via JS/PHP -->
        </button>
        
        <!-- User Avatar (Clickable to Profile) -->
        <a href="profile.php" style="text-decoration:none;" title="Profil UMKM">
            <div style="width:34px;height:34px;border-radius:50%;background:var(--brand-100);display:flex;align-items:center;justify-content:center;color:var(--brand-600);font-weight:700;font-size:0.875rem;border:2px solid var(--brand-200);cursor:pointer;transition:transform 0.2s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                <?php echo $initial; ?>
            </div>
        </a>
    </div>
</header>
