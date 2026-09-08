// ========== UMKM INSIGHT — GLOBAL SCRIPTS ==========

// ----- FORMATTING UTILS -----
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(n);
const formatNumber = (n) => new Intl.NumberFormat('id-ID').format(n);

// ----- UI INTERACTION -----
function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('open');
    document.getElementById('sidebar-overlay')?.classList.toggle('open');
}

function showToast(msg, type = 'success') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    const icons = { success: 'ph-check-circle', warning: 'ph-warning', error: 'ph-x-circle', info: 'ph-info' };
    toast.innerHTML = `<i class="ph-fill ${icons[type] || icons.info}" style="font-size:1.25rem;flex-shrink:0;"></i><span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function toggleNotifPanel() {
    const existing = document.getElementById('notif-panel');
    if (existing) { existing.remove(); return; }
    
    // Panel Notifikasi (Statik untuk Demo / Bisa dihubungkan ke API PHP nanti)
    const panel = document.createElement('div');
    panel.id = 'notif-panel';
    panel.style.cssText = 'position:fixed;top:64px;right:16px;width:340px;max-height:420px;overflow-y:auto;background:white;border-radius:var(--radius-xl);box-shadow:var(--shadow-xl);border:1px solid var(--border);z-index:100;animation:fadeIn 0.2s ease-out;';
    panel.innerHTML = `
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <span style="font-weight:700;font-size:0.875rem;">Notifikasi</span>
            <button onclick="this.closest('#notif-panel').remove()" class="btn btn-ghost btn-sm" style="padding:4px;"><i class="ph ph-x"></i></button>
        </div>
        <div id="notif-list-content">
            <p style="padding:24px;text-align:center;color:var(--text-muted);font-size:0.8125rem;">Memuat notifikasi...</p>
        </div>
    `;
    document.body.appendChild(panel);
    
    // Auto-close on click outside
    document.addEventListener('click', function closePanel(e) {
        if (!panel.contains(e.target) && !e.target.closest('#notif-bell')) {
            panel.remove();
            document.removeEventListener('click', closePanel);
        }
    });
}

// ----- DATA SIMULATION (CHART HELPERS) -----
function generateSalesData(factor = 1.0) {
    const rand = (base) => Math.floor(base * factor * (0.8 + Math.random() * 0.4));
    return {
        kpi: { revenue: rand(45250000), transactions: rand(1248), fee: rand(1357500), expense: rand(28400000) },
        monthly: { labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'], data: [12,15,18,14,22,25,20,28,30,35,32,38].map(v => rand(v * 1000000)) },
        weekly: { labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'], masuk: [4200000,5100000,3800000,6200000,7500000,9100000,9350000].map(rand), keluar: [2100000,1800000,4500000,2000000,6000000,5000000,7000000].map(rand) }
    };
}

function generateCashflowData(factor = 1.0) {
    const rand = (base) => Math.floor(base * factor * (0.8 + Math.random() * 0.4));
    return {
        kpi: { netCash: rand(16850000), totalIn: rand(45250000), totalOut: rand(28400000) },
        daily: { labels: ['Sen','Sel','Rab','Kam','Jum','Sab','Min'], masuk: [4200000,5100000,3800000,6200000,7500000,9100000,9350000].map(rand), keluar: [2100000,1800000,4500000,2000000,6000000,5000000,7000000].map(rand) }
    };
}
