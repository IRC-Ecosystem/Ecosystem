// ========== UMKM INSIGHT — SHARED UTILITIES ==========

// ----- FORMATTING -----
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n);

// ----- DEFAULT USERS (SEED) -----
const DEFAULT_USERS = [
    { id: 'USR-001', username: 'budi', password: 'password', role: 'client', nama: 'Budi Santoso', email: 'budi@tokoberkah.com', bisnis: 'Toko Berkah Utama', kategori: 'Makanan & Minuman', alamat: 'Jl. Merdeka No. 10, Surabaya', telepon: '081234567890', deskripsi: 'Menjual aneka kopi dan kue tradisional', smartbankId: 'SB-UMKM-001', tier: 'free', tierExpiry: null, registeredAt: '2026-01-15' },
    { id: 'USR-002', username: 'sari', password: 'password', role: 'client', nama: 'Sari Dewi', email: 'sari@rajutanku.com', bisnis: 'Rajutan Sari', kategori: 'Fashion & Kerajinan', alamat: 'Jl. Pahlawan No. 5, Malang', telepon: '082345678901', deskripsi: 'Produk rajutan handmade premium', smartbankId: 'SB-UMKM-002', tier: 'premium', tierExpiry: '2026-05-05', registeredAt: '2025-11-20' },
    { id: 'USR-003', username: 'andi', password: 'password', role: 'client', nama: 'Andi Wijaya', email: 'andi@sambalroa.id', bisnis: 'Sambal Roa Manado', kategori: 'Makanan & Minuman', alamat: 'Jl. Sam Ratulangi No. 8, Manado', telepon: '083456789012', deskripsi: 'Sambal roa khas Manado', smartbankId: 'SB-UMKM-003', tier: 'premium', tierExpiry: '2026-06-15', registeredAt: '2025-10-05' },
    { id: 'USR-004', username: 'op_jaya', password: 'operator123', role: 'operator', nama: 'Jaya Operasional', email: 'jaya@umkminsight.id' },
    { id: 'USR-005', username: 'admin_super', password: 'admin123', role: 'admin', nama: 'Admin System', email: 'admin@umkminsight.id' },
];

// ----- COMPLAINTS & OFFERS SEED -----
const DEFAULT_COMPLAINTS = [
    { id: 'CMP-001', userId: 'USR-001', subject: 'Kendala Sinkronisasi', message: 'Data transaksi SmartBank saya tidak muncul sejak pagi ini.', status: 'open', date: '2026-04-28' },
];
const DEFAULT_OFFERS = [
    { id: 'OFF-001', title: 'Promo Ramadhan Premium', description: 'Diskon 50% untuk perpanjangan tier premium selama bulan Ramadhan.', target: 'free', price: 50000 },
];

// ----- STORAGE HELPERS -----
function initData() {
    const users = getUsers();
    // Jika user op_jaya belum ada, tambahkan/reset data demo
    if (!users.find(u => u.username === 'op_jaya')) {
        localStorage.setItem('umkm_users', JSON.stringify(DEFAULT_USERS));
    }
    if (!localStorage.getItem('umkm_notifications')) localStorage.setItem('umkm_notifications', JSON.stringify(DEFAULT_NOTIFICATIONS));
    if (!localStorage.getItem('umkm_complaints')) localStorage.setItem('umkm_complaints', JSON.stringify(DEFAULT_COMPLAINTS));
    if (!localStorage.getItem('umkm_offers')) localStorage.setItem('umkm_offers', JSON.stringify(DEFAULT_OFFERS));
}
function getUsers() { return JSON.parse(localStorage.getItem('umkm_users') || '[]'); }
function saveUsers(u) { localStorage.setItem('umkm_users', JSON.stringify(u)); }
function getNotifications() { return JSON.parse(localStorage.getItem('umkm_notifications') || '[]'); }
function saveNotifications(n) { localStorage.setItem('umkm_notifications', JSON.stringify(n)); }
function getComplaints() { return JSON.parse(localStorage.getItem('umkm_complaints') || '[]'); }
function saveComplaints(c) { localStorage.setItem('umkm_complaints', JSON.stringify(c)); }
function getOffers() { return JSON.parse(localStorage.getItem('umkm_offers') || '[]'); }
function saveOffers(o) { localStorage.setItem('umkm_offers', JSON.stringify(o)); }

// ----- AUTH -----
function login(username, password) {
    initData();
    const users = getUsers();
    const user = users.find(u => u.username === username && u.password === password);
    if (user) { localStorage.setItem('umkm_session', JSON.stringify({ userId: user.id, role: user.role })); return user; }
    return null;
}
function logout() { localStorage.removeItem('umkm_session'); window.location.href = 'login.html'; }
function getSession() { const s = localStorage.getItem('umkm_session'); return s ? JSON.parse(s) : null; }
function getCurrentUser() {
    const session = getSession();
    if (!session) return null;
    return getUsers().find(u => u.id === session.userId) || null;
}
function requireAuth(allowedRole) {
    initData();
    const session = getSession();
    if (!session) { window.location.href = 'login.html'; return null; }
    // Role logic
    const user = getCurrentUser();
    if (!user) { logout(); return null; }
    if (allowedRole && user.role !== allowedRole) {
        if (user.role === 'admin') window.location.href = 'admin.html';
        else if (user.role === 'operator') window.location.href = 'operator.html';
        else window.location.href = 'dashboard.html';
        return null;
    }
    return user;
}

// ----- SUBSCRIPTION -----
function isPremium(user) { return user && user.tier === 'premium'; }
function getDaysUntilExpiry(user) {
    if (!user || !user.tierExpiry) return Infinity;
    const now = new Date(); const exp = new Date(user.tierExpiry);
    return Math.ceil((exp - now) / (1000 * 60 * 60 * 24));
}
function checkSubscriptionBanner(user) {
    if (!user || user.role !== 'client') return;
    const days = getDaysUntilExpiry(user);
    if (isPremium(user) && days <= 7 && days > 0) {
        showBanner(`⚠️ Langganan Premium Anda akan berakhir dalam ${days} hari. Hubungi admin untuk perpanjang.`, 'warning');
    } else if (isPremium(user) && days <= 0) {
        const users = getUsers();
        const idx = users.findIndex(u => u.id === user.id);
        if (idx >= 0) { users[idx].tier = 'free'; users[idx].tierExpiry = null; saveUsers(users); }
        showBanner('Langganan Premium Anda telah berakhir. Anda kembali ke Free Tier.', 'warning');
    }
}
function renderPremiumLock(containerId, pageName) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.classList.add('premium-locked');
    const badge = document.createElement('div');
    badge.className = 'premium-lock-badge';
    badge.innerHTML = `<i class="ph-fill ph-crown"></i> Fitur Premium — Upgrade untuk akses ${pageName}`;
    badge.onclick = () => showToast('Hubungi Operator untuk upgrade ke Premium!', 'info');
    el.appendChild(badge);
}

// ----- NOTIFICATIONS UI -----
function getUserNotifications(userId) { return getNotifications().filter(n => n.targetUserId === userId); }
function getUnreadCount(userId) { return getUserNotifications(userId).filter(n => !n.read).length; }
function markNotificationsRead(userId) {
    const notifs = getNotifications();
    notifs.forEach(n => { if (n.targetUserId === userId) n.read = true; });
    saveNotifications(notifs);
}

// ----- BANNER & TOAST -----
function showBanner(msg, type = 'warning') {
    const existing = document.getElementById('notification-banner');
    if (existing) existing.remove();
    const banner = document.createElement('div');
    banner.id = 'notification-banner';
    banner.className = `notification-banner ${type === 'info' ? 'info-banner' : ''}`;
    banner.innerHTML = `<i class="ph-fill ph-warning-circle"></i> <span>${msg}</span> <span class="close-btn" onclick="this.parentElement.remove()"><i class="ph ph-x"></i></span>`;
    document.body.prepend(banner);
}
function showToast(msg, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) { container = document.createElement('div'); container.id = 'toast-container'; container.className = 'toast-container'; document.body.appendChild(container); }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const icons = { success: 'ph-check-circle', warning: 'ph-warning', error: 'ph-x-circle', info: 'ph-info' };
    toast.innerHTML = `<i class="ph-fill ${icons[type] || icons.info}" style="font-size:1.25rem;flex-shrink:0;"></i><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(20px)'; toast.style.transition = 'all 0.3s'; setTimeout(() => toast.remove(), 300); }, 4000);
}

// ----- SIDEBAR BUILDER -----
function buildSidebar(activePage) {
    const user = getCurrentUser();
    if (!user) return '';

    let navLinks = [];
    if (user.role === 'client') {
        navLinks = [
            { href: 'dashboard.html', icon: 'ph-squares-four', label: 'Dashboard', page: 'dashboard' },
            { href: 'laporan-penjualan.html', icon: 'ph-trend-up', label: 'Laporan Penjualan', page: 'laporan' },
            { href: 'arus-kas.html', icon: 'ph-money', label: 'Arus Kas', page: 'arus-kas' },
            { href: 'performa-produk.html', icon: 'ph-package', label: 'Performa Produk', page: 'performa' },
            { href: 'pengaduan.html', icon: 'ph-chat-circle-dots', label: 'Pengaduan', page: 'pengaduan' },
        ];
    } else if (user.role === 'operator') {
        navLinks = [
            { href: 'operator.html', icon: 'ph-lightning', label: 'Manajemen Operasional', page: 'operator' },
            { href: 'pengaduan-admin.html', icon: 'ph-chat-circle-text', label: 'Tiket Pengaduan', page: 'pengaduan-admin' },
        ];
    } else if (user.role === 'admin') {
        navLinks = [
            { href: 'admin.html', icon: 'ph-shield-check', label: 'Sistem & Audit', page: 'admin' },
        ];
    }

    const isFree = user.role === 'client' && user.tier === 'free';
    let navHtml = navLinks.map(l => {
        const isActive = activePage === l.page;
        const premTag = (isFree && ['laporan','arus-kas','performa'].includes(l.page)) ? '<span class="premium-tag">PRO</span>' : '';
        return `<li class="nav-item"><a href="${l.href}" class="${isActive ? 'active' : ''}""><i class="ph ${l.icon}"></i>${l.label}${premTag}</a></li>`;
    }).join('');

    const integrationHtml = user.role === 'client' ? `<div class="sidebar-section-title">Integrasi Sistem</div>
    <div style="padding:0 14px"><div style="background:var(--surface-alt);padding:12px;border-radius:var(--radius-lg);border:1px solid var(--border-light);display:flex;align-items:flex-start;gap:8px;">
        <i class="ph-fill ph-check-circle" style="color:var(--success);margin-top:2px;"></i>
        <div><p style="font-size:0.8125rem;font-weight:600;color:var(--text-primary);">SmartBank Connected</p>
        <p style="font-size:0.6875rem;color:var(--text-muted);margin-top:4px;">Read-only API<br>GET /analytics/*</p></div>
    </div></div>` : '';

    return `<aside class="app-sidebar" id="sidebar">
        <div class="sidebar-brand"><i class="ph-fill ph-chart-polar"></i><span>UMKM Insight</span></div>
        <nav class="sidebar-nav"><ul>${navHtml}</ul>${integrationHtml}</nav>
        <div class="sidebar-footer">
            <button onclick="logout()" class="btn btn-ghost btn-sm btn-full" style="justify-content:flex-start;"><i class="ph ph-sign-out"></i> Keluar</button>
        </div>
    </aside>
    <div class="sidebar-overlay" id="sidebar-overlay" onclick="toggleSidebar()"></div>`;
}

function buildTopbar(user) {
    const unread = user ? getUnreadCount(user.id) : 0;
    const initial = user ? user.nama.charAt(0).toUpperCase() : 'U';
    const notifBadge = unread > 0 ? `<span style="position:absolute;top:-2px;right:-2px;width:16px;height:16px;background:var(--error);color:white;font-size:0.625rem;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;">${unread}</span>` : '';
    return `<header class="app-topbar">
        <div style="display:flex;align-items:center;gap:12px;">
            <button onclick="toggleSidebar()" class="btn btn-ghost btn-sm" style="display:none;padding:6px;" id="mobile-menu-btn"><i class="ph ph-list" style="font-size:1.5rem;"></i></button>
            <div style="font-size:0.8125rem;color:var(--text-muted);display:flex;align-items:center;gap:6px;"><i class="ph ph-calendar-blank"></i><span id="topbar-date"></span></div>
        </div>
        <div style="display:flex;align-items:center;gap:16px;">
            <button style="position:relative;background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.25rem;" onclick="toggleNotifPanel()" id="notif-bell"><i class="ph ph-bell"></i>${notifBadge}</button>
            <div style="width:34px;height:34px;border-radius:50%;background:var(--brand-100);display:flex;align-items:center;justify-content:center;color:var(--brand-600);font-weight:700;font-size:0.875rem;border:2px solid var(--brand-200);">${initial}</div>
        </div>
    </header>`;
}

function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('open');
    document.getElementById('sidebar-overlay')?.classList.toggle('open');
}

function toggleNotifPanel() {
    const user = getCurrentUser();
    if (!user) return;
    const existing = document.getElementById('notif-panel');
    if (existing) { existing.remove(); return; }
    const notifs = getUserNotifications(user.id);
    const items = notifs.length ? notifs.map(n => `<div style="padding:12px;border-bottom:1px solid var(--border-light);${n.read ? 'opacity:0.6;' : ''}">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;"><span class="badge ${n.type === 'admin' ? 'badge-info' : 'badge-warning'}">${n.type === 'admin' ? 'Admin' : 'Sistem'}</span><span style="font-size:0.6875rem;color:var(--text-muted);">${n.date}</span></div>
        <p style="font-size:0.8125rem;font-weight:600;color:var(--text-primary);">${n.title}</p>
        <p style="font-size:0.75rem;color:var(--text-secondary);margin-top:2px;">${n.message}</p>
    </div>`).join('') : '<p style="padding:24px;text-align:center;color:var(--text-muted);font-size:0.8125rem;">Tidak ada notifikasi</p>';
    const panel = document.createElement('div');
    panel.id = 'notif-panel';
    panel.style.cssText = 'position:fixed;top:64px;right:16px;width:360px;max-height:420px;overflow-y:auto;background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-xl);border:1px solid var(--border);z-index:100;animation:fadeIn 0.2s ease-out;';
    panel.innerHTML = `<div style="padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
        <span style="font-weight:700;font-size:0.875rem;">Notifikasi</span>
        <button onclick="markAllRead()" class="btn btn-ghost btn-sm" style="font-size:0.75rem;">Tandai dibaca</button></div>${items}`;
    document.body.appendChild(panel);
    document.addEventListener('click', function closePanel(e) {
        if (!panel.contains(e.target) && e.target.id !== 'notif-bell' && !e.target.closest('#notif-bell')) { panel.remove(); document.removeEventListener('click', closePanel); }
    });
}
function markAllRead() {
    const user = getCurrentUser();
    if (user) { markNotificationsRead(user.id); document.getElementById('notif-panel')?.remove(); showToast('Semua notifikasi ditandai telah dibaca.', 'success'); setTimeout(() => location.reload(), 800); }
}

// ----- INIT PAGE -----
function initPage(activePage, requiredRole = 'client') {
    initData();
    const user = requireAuth(requiredRole);
    if (!user) return null;

    // Set date
    setTimeout(() => {
        const dateEl = document.getElementById('topbar-date');
        if (dateEl) dateEl.textContent = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    }, 0);

    // Responsive menu button
    setTimeout(() => {
        const btn = document.getElementById('mobile-menu-btn');
        if (btn && window.innerWidth <= 768) btn.style.display = 'flex';
        window.addEventListener('resize', () => { if (btn) btn.style.display = window.innerWidth <= 768 ? 'flex' : 'none'; });
    }, 0);

    // Subscription banner
    checkSubscriptionBanner(user);

    return user;
}

// ----- MOCK DATA GENERATORS -----
function generateSalesData(factor = 1.0) {
    const rand = (base) => Math.floor(base * factor * (0.8 + Math.random() * 0.4));
    return {
        kpi: { revenue: rand(45250000), transactions: rand(1248), fee: rand(1357500), expense: rand(28400000) },
        monthly: { labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], data: [12,15,18,14,22,25,20,28,30,35,32,38].map(v => rand(v * 1000000)) },
        weekly: { labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'], masuk: [4200000,5100000,3800000,6200000,7500000,9100000,9350000].map(rand), keluar: [2100000,1800000,4500000,2000000,6000000,5000000,7000000].map(rand) },
        sources: { labels: ['Marketplace','POS (Kasir)','B2B / Supplier'], data: [60,30,10] },
        transactions: Array.from({length: 20}, (_, i) => {
            const types = ['Penjualan','Penjualan','Penjualan','Potongan Fee','Pembelian Stok','Ongkos Kirim'];
            const srcs = ['Marketplace','POS','SmartBank','SupplierHub','LogistiKita'];
            const t = types[i % types.length]; const s = srcs[i % srcs.length];
            const amt = t === 'Penjualan' ? rand(150000 + Math.random()*300000) : -rand(5000 + Math.random()*50000);
            return { id: `TRX-${1000 + i}`, type: t, source: s, amount: amt, status: 'Success', date: `2026-04-${String(28 - i).padStart(2,'0')}` };
        })
    };
}
function generateCashflowData(factor = 1.0) {
    const rand = (base) => Math.floor(base * factor * (0.8 + Math.random() * 0.4));
    return {
        kpi: { netCash: rand(16850000), totalIn: rand(45250000), totalOut: rand(28400000) },
        daily: { labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'], masuk: [4200000,5100000,3800000,6200000,7500000,9100000,9350000].map(rand), keluar: [2100000,1800000,4500000,2000000,6000000,5000000,7000000].map(rand) },
        monthly: { labels: ['Jan','Feb','Mar','Apr','Mei','Jun'], masuk: [32,35,38,40,42,45].map(v => rand(v*1000000)), keluar: [20,22,25,24,26,28].map(v => rand(v*1000000)) },
        categories: { labels: ['Penjualan','Pembelian Stok','Ongkos Kirim','Fee Sistem','Pajak'], masuk: [rand(35000000),0,0,0,0], keluar: [0,rand(15000000),rand(5000000),rand(1500000),rand(900000)] },
        forecast: { labels: ['Minggu 1','Minggu 2','Minggu 3','Minggu 4'], projected: [rand(11000000),rand(12500000),rand(13000000),rand(14500000)] }
    };
}
function generateProductData(factor = 1.0) {
    const rand = (base) => Math.floor(base * factor * (0.8 + Math.random() * 0.4));
    const products = [
        { name: 'Kopi Susu Gula Aren', category: 'Minuman', sold: rand(1200), revenue: rand(18000000), rating: 4.8 },
        { name: 'Keripik Singkong Pedas', category: 'Makanan', sold: rand(850), revenue: rand(8500000), rating: 4.5 },
        { name: 'Baju Rajut Premium', category: 'Fashion', sold: rand(640), revenue: rand(32000000), rating: 4.9 },
        { name: 'Sambal Roa Original', category: 'Makanan', sold: rand(520), revenue: rand(10400000), rating: 4.7 },
        { name: 'Kue Kering Nastar', category: 'Makanan', sold: rand(410), revenue: rand(6150000), rating: 4.3 },
        { name: 'Tas Rajut Handmade', category: 'Fashion', sold: rand(380), revenue: rand(19000000), rating: 4.6 },
        { name: 'Kopi Robusta 250g', category: 'Minuman', sold: rand(320), revenue: rand(9600000), rating: 4.4 },
        { name: 'Selai Kacang Homemade', category: 'Makanan', sold: rand(280), revenue: rand(4200000), rating: 4.2 },
    ];
    return {
        products,
        categoryDist: { labels: ['Makanan','Minuman','Fashion'], data: [rand(2060), rand(1520), rand(1020)] },
        trend: { labels: ['Jan','Feb','Mar','Apr','Mei','Jun'], datasets: products.slice(0,3).map((p, i) => ({ label: p.name, data: Array.from({length:6}, () => rand(p.sold / 6)) })) }
    };
}
