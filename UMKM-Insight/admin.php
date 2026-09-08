<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('admin');

$adminDashboard = (new App\Controllers\AdminDashboardController($pdo))->viewData($_SESSION);
extract($adminDashboard);
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard — UMKM Insight</title>
  <style>
    :root {
      --bg: #f3f6fb;
      --panel: #ffffff;
      --panel-soft: #f8fafc;
      --line: #e2e8f0;
      --text: #0f172a;
      --muted: #64748b;
      --brand: #059669;
      --brand-soft: #d1fae5;
      --blue: #2563eb;
      --yellow: #d97706;
      --purple: #7c3aed;
      --red: #dc2626;
      --shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
      --sidebar: 248px;
    }
    [data-theme="dark"] {
      --bg: #0f172a;
      --panel: #172033;
      --panel-soft: #111827;
      --line: #29364f;
      --text: #f8fafc;
      --muted: #94a3b8;
      --brand-soft: #073b31;
      --shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
    }
    a { color: inherit; text-decoration: none; }
    button, input, select { font: inherit; }
    .app-shell { min-height: 100vh; display: flex; }
    .sidebar {
      position: fixed;
      inset: 0 auto 0 0;
      width: var(--sidebar);
      background: var(--panel);
      border-right: 1px solid var(--line);
      display: flex;
      flex-direction: column;
      z-index: 10;
    }
    .brand {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 22px 20px;
      border-bottom: 1px solid var(--line);
    }
    .brand-mark, .avatar, .mini-avatar {
      display: grid;
      place-items: center;
      color: #fff;
      font-weight: 800;
      background: linear-gradient(135deg, #059669, #0d9488);
    }
    .brand-mark { width: 38px; height: 38px; border-radius: 10px; }
    .brand-name { font-weight: 800; letter-spacing: -0.02em; }
    .brand-sub { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; }
    .nav { padding: 16px 12px; flex: 1; }
    .nav-label {
      padding: 10px 10px 6px;
      color: var(--muted);
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .nav button {
      width: 100%;
      border: 0;
      background: transparent;
      color: var(--muted);
      padding: 11px 12px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      font-weight: 650;
      text-align: left;
    }
    .nav button:hover { background: var(--panel-soft); color: var(--text); }
    .nav button.active { background: var(--brand-soft); color: var(--brand); }
    .sidebar-footer {
      padding: 14px;
      border-top: 1px solid var(--line);
    }
    .user-card {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px;
      border-radius: 8px;
      background: var(--panel-soft);
      margin-bottom: 10px;
    }
    .avatar { width: 36px; height: 36px; border-radius: 50%; }
    .user-name { font-size: 13px; font-weight: 750; }
    .user-role { font-size: 12px; color: var(--muted); }
    .logout {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--red);
      font-size: 13px;
      font-weight: 750;
      padding: 10px;
      border-radius: 8px;
    }
    .logout:hover { background: rgba(220, 38, 38, 0.08); }
    .main {
      margin-left: var(--sidebar);
      width: calc(100% - var(--sidebar));
      min-height: 100vh;
    }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 5;
      height: 64px;
      background: color-mix(in srgb, var(--panel) 90%, transparent);
      border-bottom: 1px solid var(--line);
      backdrop-filter: blur(12px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
    }
    .topbar-title { font-weight: 800; }
    .topbar-meta { display: flex; align-items: center; gap: 14px; color: var(--muted); font-size: 13px; }
    .theme-btn {
      border: 1px solid var(--line);
      background: var(--panel);
      color: var(--text);
      border-radius: 8px;
      width: 38px;
      height: 34px;
      cursor: pointer;
    }
    .content { padding: 26px; }
    .section { display: none; }
    .section.active { display: block; }
    .page-header { margin-bottom: 22px; }
    .page-header h1 { margin: 0 0 6px; font-size: 26px; letter-spacing: -0.03em; }
    .page-header p { margin: 0; color: var(--muted); font-size: 14px; }
    .stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 18px;
    }
    .stat-card, .panel {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 8px;
      box-shadow: var(--shadow);
    }
    .stat-card { padding: 18px; }
    .stat-label { color: var(--muted); font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
    .stat-value { margin-top: 9px; font-size: 30px; font-weight: 850; letter-spacing: -0.04em; }
    .stat-note { margin-top: 4px; color: var(--muted); font-size: 12px; }
    .panel-header {
      padding: 18px;
      border-bottom: 1px solid var(--line);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }
    .panel-title { font-weight: 800; }
    .panel-sub { color: var(--muted); font-size: 13px; margin-top: 2px; }
    .controls { display: flex; gap: 8px; flex-wrap: wrap; }
    .controls input, .controls select {
      border: 1px solid var(--line);
      background: var(--panel-soft);
      color: var(--text);
      border-radius: 8px;
      padding: 9px 11px;
      min-height: 38px;
    }
    .controls input { width: 260px; max-width: 70vw; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 13px 16px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: middle; }
    th {
      color: var(--muted);
      background: var(--panel-soft);
      font-size: 12px;
      font-weight: 850;
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }
    tr:last-child td { border-bottom: 0; }
    .user-cell { display: flex; align-items: center; gap: 11px; min-width: 220px; }
    .mini-avatar { width: 32px; height: 32px; border-radius: 50%; font-size: 12px; flex: 0 0 auto; }
    .user-primary { font-weight: 750; }
    .user-secondary { color: var(--muted); font-size: 12px; margin-top: 2px; }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border-radius: 999px;
      padding: 4px 9px;
      font-size: 12px;
      font-weight: 800;
      white-space: nowrap;
    }
    .badge-green { background: #dcfce7; color: #166534; }
    .badge-blue { background: #dbeafe; color: #1d4ed8; }
    .badge-yellow { background: #fef3c7; color: #92400e; }
    .badge-purple { background: #ede9fe; color: #5b21b6; }
    .badge-gray { background: var(--panel-soft); color: var(--muted); border: 1px solid var(--line); }
    .dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
    .empty { display: none; padding: 40px; text-align: center; color: var(--muted); }
    .config-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .config-item { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); }
    .config-item:last-child { border-bottom: 0; }
    .config-key { color: var(--muted); }
    .config-val { font-weight: 750; text-align: right; }
    .menu-btn { display: none; }
    @media (max-width: 900px) {
      :root { --sidebar: 0px; }
      .sidebar { transform: translateX(-100%); transition: transform .2s ease; }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; width: 100%; }
      .menu-btn { display: inline-grid; place-items: center; }
      .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .config-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 540px) {
      .content { padding: 18px; }
      .stats { grid-template-columns: 1fr; }
      .topbar { padding: 0 16px; }
    }
  </style>
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="admin.php">
      <div class="brand-mark">U</div>
      <div>
        <div class="brand-name">UMKM Insight</div>
        <div class="brand-sub">Admin Panel</div>
      </div>
    </a>
    <nav class="nav">
      <div class="nav-label">Menu Utama</div>
      <button class="active" data-section="users">👥 Manajemen User</button>
      <button data-section="audit">🧾 Audit Sistem</button>
      <button data-section="config">⚙ Konfigurasi</button>
    </nav>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="avatar"><?php echo htmlspecialchars($adminInitials); ?></div>
        <div>
          <div class="user-name"><?php echo htmlspecialchars($adminName); ?></div>
          <div class="user-role">Administrator</div>
        </div>
      </div>
      <a class="logout" href="logout.php">← Keluar</a>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:10px;">
        <button class="theme-btn menu-btn" id="menuBtn">☰</button>
        <div class="topbar-title">Admin Dashboard</div>
      </div>
      <div class="topbar-meta">
        <span id="topbarDate"></span>
        <button class="theme-btn" id="themeBtn" title="Ganti tema">🌙</button>
      </div>
    </header>

    <main class="content">
      <section class="section active" id="section-users">
        <div class="page-header">
          <h1>Manajemen Ekosistem UMKM</h1>
          <p>Kelola user, operator, tier, dan akun internal UMKM Insight.</p>
        </div>

        <div class="stats">
          <div class="stat-card"><div class="stat-label">Total Client</div><div class="stat-value"><?php echo (int) $stats['clients']; ?></div><div class="stat-note">Akun UMKM terdaftar</div></div>
          <div class="stat-card"><div class="stat-label">Operator</div><div class="stat-value"><?php echo (int) $stats['operators']; ?></div><div class="stat-note">Staff operasional</div></div>
          <div class="stat-card"><div class="stat-label">Premium</div><div class="stat-value"><?php echo (int) $stats['premium']; ?></div><div class="stat-note">Client premium</div></div>
          <div class="stat-card"><div class="stat-label">Service</div><div class="stat-value" style="color:var(--brand);">Online</div><div class="stat-note">Docker healthy</div></div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div>
              <div class="panel-title">Pengguna & Staff</div>
              <div class="panel-sub">Data langsung dari tabel users.</div>
            </div>
            <div class="controls">
              <input id="searchInput" type="search" placeholder="Cari user, email, bisnis..." />
              <select id="roleFilter">
                <option value="all">Semua Role</option>
                <option value="admin">Admin</option>
                <option value="operator">Operator</option>
                <option value="client">Client</option>
              </select>
            </div>
          </div>
          <div class="table-wrap">
            <table>
              <thead>
                <tr><th>User</th><th>Role</th><th>Email</th><th>Tier</th><th>Status</th></tr>
              </thead>
              <tbody id="usersTableBody"></tbody>
            </table>
            <div class="empty" id="emptyUsers">Tidak ada data yang cocok.</div>
          </div>
        </div>
      </section>

      <section class="section" id="section-audit">
        <div class="page-header">
          <h1>Audit Sistem</h1>
          <p>Status ringkas komponen integrasi.</p>
        </div>
        <div class="panel">
          <div class="panel-header"><div><div class="panel-title">Checklist Integrasi</div><div class="panel-sub">Untuk memastikan admin tidak blank dan service aktif.</div></div></div>
          <table>
            <tbody>
              <tr><td><span class="badge badge-green">OK</span></td><td>Database UMKM Insight</td><td>umkm-insight-mysql:3306</td></tr>
              <tr><td><span class="badge badge-green">OK</span></td><td>API UMKM Insight</td><td>/api/health.php</td></tr>
              <tr><td><span class="badge badge-blue">Docker</span></td><td>API Integrator</td><td>http://api-integrator:4001</td></tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="section" id="section-config">
        <div class="page-header">
          <h1>Konfigurasi Sistem</h1>
          <p>Ringkasan environment Docker yang dipakai UMKM Insight.</p>
        </div>
        <div class="config-grid">
          <div class="panel" style="padding:18px;">
            <div class="panel-title">Database</div>
            <div class="config-item"><span class="config-key">DB_HOST</span><span class="config-val">umkm-insight-mysql</span></div>
            <div class="config-item"><span class="config-key">DB_NAME</span><span class="config-val">umkm_insight</span></div>
          </div>
          <div class="panel" style="padding:18px;">
            <div class="panel-title">Endpoint</div>
            <div class="config-item"><span class="config-key">UI</span><span class="config-val">localhost:3006</span></div>
            <div class="config-item"><span class="config-key">API Integrator</span><span class="config-val">api-integrator:4001</span></div>
          </div>
        </div>
      </section>
    </main>
  </div>
</div>

<script>
  'use strict';
  const USERS = <?php echo json_encode($usersForUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  const html = document.documentElement;

  function $(id) { return document.getElementById(id); }

  function setDate() {
    const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const d = new Date();
    $('topbarDate').textContent = `${days[d.getDay()]}, ${String(d.getDate()).padStart(2,'0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  function badgeForRole(role) {
    return { admin: 'badge-purple', operator: 'badge-blue', client: 'badge-green' }[role] || 'badge-gray';
  }

  function badgeForTier(tier) {
    const normalized = String(tier || '').toLowerCase();
    if (normalized === 'premium') return 'badge-yellow';
    if (normalized === 'internal') return 'badge-purple';
    return 'badge-gray';
  }

  function renderUsers() {
    const body = $('usersTableBody');
    const empty = $('emptyUsers');
    const q = ($('searchInput').value || '').toLowerCase();
    const role = $('roleFilter').value;
    const rows = USERS.filter((user) => {
      const haystack = `${user.name} ${user.business} ${user.email} ${user.role}`.toLowerCase();
      return (role === 'all' || user.role === role) && (!q || haystack.includes(q));
    });

    body.innerHTML = rows.map((user) => `
      <tr>
        <td>
          <div class="user-cell">
            <div class="mini-avatar ${user.avatarClass || ''}">${user.initials || 'U'}</div>
            <div>
              <div class="user-primary">${user.name}</div>
              <div class="user-secondary">${user.business || '-'}</div>
            </div>
          </div>
        </td>
        <td><span class="badge ${badgeForRole(user.role)}">${String(user.role).toUpperCase()}</span></td>
        <td>${user.email || '-'}</td>
        <td><span class="badge ${badgeForTier(user.tier)}">${user.tier || '-'}</span></td>
        <td><span class="badge badge-green"><span class="dot"></span>${user.status || 'Aktif'}</span></td>
      </tr>
    `).join('');
    empty.style.display = rows.length ? 'none' : 'block';
  }

  function showSection(name) {
    document.querySelectorAll('.section').forEach((section) => section.classList.remove('active'));
    $(`section-${name}`).classList.add('active');
    document.querySelectorAll('.nav button').forEach((button) => button.classList.toggle('active', button.dataset.section === name));
  }

  function initTheme() {
    const theme = localStorage.getItem('umkm-theme') || 'light';
    html.setAttribute('data-theme', theme);
    $('themeBtn').textContent = theme === 'dark' ? '☀' : '🌙';
  }

  $('themeBtn').addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('umkm-theme', next);
    initTheme();
  });
  $('menuBtn')?.addEventListener('click', () => $('sidebar').classList.toggle('open'));
  $('searchInput').addEventListener('input', renderUsers);
  $('roleFilter').addEventListener('change', renderUsers);
  document.querySelectorAll('.nav button').forEach((button) => button.addEventListener('click', () => showSection(button.dataset.section)));

  initTheme();
  setDate();
  renderUsers();
</script>
</body>
</html>
