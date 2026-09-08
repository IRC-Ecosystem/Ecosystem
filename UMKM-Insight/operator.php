<?php
require_once 'config/db.php';
require_once 'includes/auth.php';
requireRole('operator');

$operatorDashboard = (new App\Controllers\OperatorDashboardController($pdo))->viewData($_SESSION);
extract($operatorDashboard);
?>
<!DOCTYPE html>
<html lang="id" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Operator Dashboard — UMKM Insight</title>
  <meta name="description" content="Dashboard Operator UMKM Insight. Kelola tier pengguna, pengajuan premium, tiket bantuan, dan penawaran promo." />
  <style>
    /* ─── CSS Variables ──────────────────────────────────────── */
    :root {
      --bg-page: #f0f4f8;
      --bg-sidebar: #ffffff;
      --bg-topbar: #ffffffcc;
      --bg-card: #ffffff;
      --bg-card-inner: #f8fafc;
      --bg-input: #f8fafc;
      --bg-hover: #f1f5f9;
      --bg-table-head: #f8fafc;
      --bg-table-row-hover: #f1f5f9;
      --bg-modal: rgba(15,23,42,0.55);
      --border: #e2e8f0;
      --border-soft: #edf2f7;
      --text-primary: #0f172a;
      --text-secondary: #475569;
      --text-muted: #94a3b8;
      --accent: #059669;
      --accent-hover: #047857;
      --accent-light: #d1fae5;
      --accent-text: #065f46;
      --blue: #3b82f6;
      --blue-light: #dbeafe;
      --blue-text: #1d4ed8;
      --yellow: #f59e0b;
      --yellow-light: #fef3c7;
      --yellow-text: #92400e;
      --red: #ef4444;
      --red-light: #fee2e2;
      --red-text: #991b1b;
      --purple: #8b5cf6;
      --purple-light: #ede9fe;
      --purple-text: #5b21b6;
      --orange: #f97316;
      --orange-light: #ffedd5;
      --orange-text: #9a3412;
      --shadow-card: 0 1px 4px rgba(0,0,0,0.06), 0 4px 20px rgba(0,0,0,0.05);
      --shadow-btn: 0 2px 8px rgba(5,150,105,0.22);
      --shadow-modal: 0 20px 60px rgba(0,0,0,0.18), 0 4px 16px rgba(0,0,0,0.1);
      --transition: all 0.22s cubic-bezier(0.4,0,0.2,1);
      --radius: 12px;
      --radius-sm: 8px;
      --sidebar-w: 240px;
      --topbar-h: 64px;
    }
    [data-theme="dark"] {
      --bg-page: #0d1525;
      --bg-sidebar: #111e35;
      --bg-topbar: #111e35cc;
      --bg-card: #1a2540;
      --bg-card-inner: #1e2d4a;
      --bg-input: #1e2d4a;
      --bg-hover: #1e2d4a;
      --bg-table-head: #1e2d4a;
      --bg-table-row-hover: #1e2d4a;
      --bg-modal: rgba(5,10,20,0.72);
      --border: #2d3f5e;
      --border-soft: #243450;
      --text-primary: #f0f6ff;
      --text-secondary: #94afd4;
      --text-muted: #5a7499;
      --accent: #10b981;
      --accent-hover: #059669;
      --accent-light: #022c22;
      --accent-text: #6ee7b7;
      --blue: #60a5fa;
      --blue-light: #0c1e3a;
      --blue-text: #93c5fd;
      --yellow: #fbbf24;
      --yellow-light: #1c1500;
      --yellow-text: #fcd34d;
      --red: #f87171;
      --red-light: #1c0a0a;
      --red-text: #fca5a5;
      --purple: #a78bfa;
      --purple-light: #1a1030;
      --purple-text: #c4b5fd;
      --orange: #fb923c;
      --orange-light: #1a0c00;
      --orange-text: #fdba74;
      --shadow-card: 0 1px 4px rgba(0,0,0,0.3), 0 4px 20px rgba(0,0,0,0.25);
      --shadow-btn: 0 2px 12px rgba(16,185,129,0.28);
      --shadow-modal: 0 20px 60px rgba(0,0,0,0.5), 0 4px 16px rgba(0,0,0,0.3);
    }

    /* ─── Reset & Base ──────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 15px; scroll-behavior: smooth; }
    body {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      min-height: 100vh;
      transition: background 0.3s ease, color 0.3s ease;
    }
    button { cursor: pointer; font-family: inherit; }
    input, select, textarea { font-family: inherit; }

    /* ─── Layout Shell ──────────────────────────────────────── */
    .app-shell { display: flex; min-height: 100vh; }

    /* ─── Sidebar ───────────────────────────────────────────── */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--bg-sidebar);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0;
      height: 100vh;
      z-index: 200;
      transition: transform 0.3s ease;
      overflow: hidden;
    }
    .sidebar-brand {
      display: flex; align-items: center; gap: 0.65rem;
      padding: 1.25rem 1.25rem 1rem;
      border-bottom: 1px solid var(--border-soft);
      text-decoration: none;
      flex-shrink: 0;
    }
    .brand-logo {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(59,130,246,0.3);
    }
    .brand-logo svg { width: 18px; height: 18px; fill: #fff; }
    .brand-info { line-height: 1.2; }
    .brand-name { font-size: 0.9rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.02em; }
    .brand-role { font-size: 0.67rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }

    .sidebar-nav { flex: 1; padding: 1rem 0.75rem; overflow-y: auto; }
    .nav-section-label {
      font-size: 0.65rem; font-weight: 600; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 0.08em;
      padding: 0.6rem 0.5rem 0.35rem; margin-top: 0.5rem;
    }
    .nav-item {
      display: flex; align-items: center; gap: 0.75rem;
      padding: 0.65rem 0.75rem;
      border-radius: var(--radius-sm);
      cursor: pointer;
      color: var(--text-secondary);
      font-size: 0.875rem; font-weight: 500;
      transition: var(--transition);
      border: none; background: none;
      width: 100%; text-align: left; text-decoration: none;
      margin-bottom: 2px; white-space: nowrap;
    }
    .nav-item:hover { background: var(--bg-hover); color: var(--text-primary); }
    .nav-item.active {
      background: var(--blue-light);
      color: var(--blue-text);
      font-weight: 600;
    }
    [data-theme="dark"] .nav-item.active { color: var(--blue); background: var(--blue-light); }
    .nav-item .nav-icon { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
    .nav-badge {
      margin-left: auto;
      background: var(--red-light); color: var(--red-text);
      font-size: 0.65rem; font-weight: 700;
      padding: 0.1rem 0.45rem; border-radius: 100px;
    }

    .sidebar-footer {
      padding: 0.75rem; border-top: 1px solid var(--border-soft); flex-shrink: 0;
    }
    .user-card {
      display: flex; align-items: center; gap: 0.65rem;
      padding: 0.65rem 0.75rem; border-radius: var(--radius-sm);
      margin-bottom: 0.5rem; background: var(--bg-card-inner);
    }
    .user-avatar {
      width: 34px; height: 34px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .user-info { line-height: 1.3; overflow: hidden; }
    .user-name { font-size: 0.8rem; font-weight: 600; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .user-role-tag { font-size: 0.68rem; color: var(--text-muted); }
    .btn-logout {
      display: flex; align-items: center; gap: 0.6rem;
      width: 100%;
      padding: 0.6rem 0.75rem;
      background: none; border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      color: var(--text-secondary);
      font-size: 0.825rem; font-weight: 500;
      text-decoration: none;
      transition: var(--transition);
    }
    .btn-logout:hover { border-color: var(--red); color: var(--red); background: var(--red-light); }

    /* ─── Main Area ─────────────────────────────────────────── */
    .main-area {
      margin-left: var(--sidebar-w);
      flex: 1; display: flex; flex-direction: column;
      min-height: 100vh; min-width: 0;
    }

    /* ─── Topbar ────────────────────────────────────────────── */
    .topbar {
      height: var(--topbar-h);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 1.75rem;
      background: var(--bg-topbar);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      position: sticky; top: 0; z-index: 100;
    }
    .topbar-left { display: flex; align-items: center; gap: 0.75rem; }
    .menu-toggle-btn {
      display: none; background: none;
      border: 1px solid var(--border); border-radius: var(--radius-sm);
      padding: 0.4rem 0.5rem; color: var(--text-secondary);
      font-size: 1.1rem; line-height: 1;
    }
    .topbar-date { font-size: 0.82rem; color: var(--text-muted); }
    .topbar-page-title { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); display: none; }
    .topbar-right { display: flex; align-items: center; gap: 0.75rem; }
    .notif-btn {
      position: relative; background: none;
      border: 1px solid var(--border); border-radius: var(--radius-sm);
      width: 36px; height: 36px;
      display: flex; align-items: center; justify-content: center;
      color: var(--text-secondary); font-size: 1rem; transition: var(--transition);
    }
    .notif-btn:hover { border-color: var(--blue); background: var(--blue-light); }
    .notif-dot {
      position: absolute; top: 6px; right: 7px;
      width: 7px; height: 7px; background: var(--red);
      border-radius: 50%; border: 1.5px solid var(--bg-card);
    }
    .theme-pill {
      display: flex; align-items: center; gap: 0.45rem;
      background: var(--bg-card-inner); border: 1px solid var(--border);
      border-radius: 100px; padding: 0.3rem 0.75rem 0.3rem 0.5rem;
      cursor: pointer; transition: var(--transition);
      font-size: 0.78rem; color: var(--text-secondary); font-weight: 500;
    }
    .theme-pill:hover { border-color: var(--blue); }
    .toggle-track {
      width: 30px; height: 17px; background: var(--border);
      border-radius: 100px; position: relative; transition: var(--transition);
    }
    .toggle-track.active { background: var(--blue); }
    .toggle-thumb {
      position: absolute; top: 2.5px; left: 2.5px;
      width: 12px; height: 12px; background: #fff;
      border-radius: 50%; transition: var(--transition);
      box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .toggle-track.active .toggle-thumb { transform: translateX(13px); }
    .topbar-avatar {
      width: 34px; height: 34px;
      background: linear-gradient(135deg, #3b82f6, #6366f1);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 0.8rem; font-weight: 700; color: #fff;
      border: 2px solid var(--blue-light);
    }

    /* ─── Page Content ──────────────────────────────────────── */
    .page-content { flex: 1; padding: 2rem 1.75rem; }
    .section-panel { display: none; }
    .section-panel.active { display: block; animation: fadeIn 0.28s ease; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    .page-header { margin-bottom: 1.75rem; }
    .page-header h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); letter-spacing: -0.03em; margin-bottom: 0.3rem; }
    .page-header p { font-size: 0.875rem; color: var(--text-secondary); }

    /* ─── Card ──────────────────────────────────────────────── */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-card);
      overflow: hidden;
      margin-bottom: 1.25rem;
    }
    .card-header-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border-soft);
      flex-wrap: wrap; gap: 0.75rem;
    }
    .card-title-row h3 { font-size: 0.975rem; font-weight: 600; color: var(--text-primary); }
    .card-title-row p { font-size: 0.78rem; color: var(--text-muted); margin-top: 0.1rem; }
    .card-body { padding: 1.25rem 1.5rem; }

    /* ─── Buttons ───────────────────────────────────────────── */
    .btn {
      display: inline-flex; align-items: center; gap: 0.4rem;
      padding: 0.48rem 1rem; border-radius: var(--radius-sm);
      font-size: 0.835rem; font-weight: 600; border: none;
      transition: var(--transition); cursor: pointer; white-space: nowrap;
    }
    .btn-primary { background: var(--accent); color: #fff; box-shadow: var(--shadow-btn); }
    .btn-primary:hover { background: var(--accent-hover); transform: translateY(-1px); }
    .btn-blue { background: var(--blue); color: #fff; box-shadow: 0 2px 8px rgba(59,130,246,0.25); }
    .btn-blue:hover { background: #2563eb; transform: translateY(-1px); }
    .btn-outline { background: none; border: 1px solid var(--border); color: var(--text-secondary); }
    .btn-outline:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-light); }
    .btn-sm { padding: 0.3rem 0.65rem; font-size: 0.775rem; }
    .btn-danger { background: var(--red-light); color: var(--red-text); border: 1px solid transparent; }
    .btn-danger:hover { background: var(--red); color: #fff; }
    .btn-warn { background: var(--yellow-light); color: var(--yellow-text); border: 1px solid transparent; }
    .btn-warn:hover { background: var(--yellow); color: #fff; }
    .btn-info { background: var(--blue-light); color: var(--blue-text); border: 1px solid transparent; }
    .btn-info:hover { background: var(--blue); color: #fff; }
    .btn-success { background: var(--accent-light); color: var(--accent-text); border: 1px solid transparent; }
    .btn-success:hover { background: var(--accent); color: #fff; }
    [data-theme="dark"] .btn-success { color: var(--accent); }

    /* ─── Toolbar ───────────────────────────────────────────── */
    .toolbar { display: flex; align-items: center; gap: 0.65rem; flex-wrap: wrap; }
    .search-box {
      display: flex; align-items: center; gap: 0.5rem;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: var(--radius-sm); padding: 0.45rem 0.85rem;
      transition: var(--transition);
    }
    .search-box:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
    .search-box input { border: none; background: none; outline: none; color: var(--text-primary); font-size: 0.85rem; width: 200px; }
    .search-box input::placeholder { color: var(--text-muted); }
    .search-icon { color: var(--text-muted); font-size: 0.9rem; }
    .filter-select {
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: var(--radius-sm); padding: 0.48rem 0.85rem;
      color: var(--text-primary); font-size: 0.85rem;
      outline: none; cursor: pointer; transition: var(--transition);
    }
    .filter-select:focus { border-color: var(--blue); }

    /* ─── Table ─────────────────────────────────────────────── */
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.855rem; }
    thead tr { background: var(--bg-table-head); }
    th {
      padding: 0.75rem 1rem; text-align: left;
      font-size: 0.73rem; font-weight: 600; color: var(--text-muted);
      text-transform: uppercase; letter-spacing: 0.05em;
      white-space: nowrap; border-bottom: 1px solid var(--border);
    }
    td {
      padding: 0.85rem 1rem; border-bottom: 1px solid var(--border-soft);
      color: var(--text-primary); vertical-align: middle;
    }
    tr:last-child td { border-bottom: none; }
    tbody tr { transition: var(--transition); }
    tbody tr:hover { background: var(--bg-table-row-hover); }

    /* ─── User Cell ─────────────────────────────────────────── */
    .user-cell { display: flex; align-items: center; gap: 0.65rem; }
    .mini-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.72rem; font-weight: 700; color: #fff; flex-shrink: 0;
    }
    .a-green  { background: linear-gradient(135deg,#059669,#0d9488); }
    .a-blue   { background: linear-gradient(135deg,#3b82f6,#6366f1); }
    .a-orange { background: linear-gradient(135deg,#f59e0b,#ef4444); }
    .a-purple { background: linear-gradient(135deg,#8b5cf6,#a855f7); }
    .a-teal   { background: linear-gradient(135deg,#14b8a6,#0ea5e9); }
    .a-rose   { background: linear-gradient(135deg,#f43f5e,#ec4899); }
    .user-cell-info { line-height: 1.3; }
    .user-cell-name { font-weight: 600; font-size: 0.85rem; }
    .user-cell-sub  { font-size: 0.75rem; color: var(--text-muted); }

    /* ─── Badge ─────────────────────────────────────────────── */
    .badge {
      display: inline-flex; align-items: center; gap: 0.25rem;
      padding: 0.2rem 0.6rem; border-radius: 100px;
      font-size: 0.72rem; font-weight: 600; white-space: nowrap;
    }
    .badge-green  { background: var(--accent-light);  color: var(--accent-text); }
    .badge-blue   { background: var(--blue-light);    color: var(--blue-text); }
    .badge-yellow { background: var(--yellow-light);  color: var(--yellow-text); }
    .badge-red    { background: var(--red-light);     color: var(--red-text); }
    .badge-purple { background: var(--purple-light);  color: var(--purple-text); }
    .badge-orange { background: var(--orange-light);  color: var(--orange-text); }
    .badge-gray   { background: var(--bg-hover);      color: var(--text-secondary); border: 1px solid var(--border); }
    [data-theme="dark"] .badge-green  { color: var(--accent); }
    [data-theme="dark"] .badge-blue   { color: var(--blue); }
    [data-theme="dark"] .badge-yellow { color: var(--yellow); }
    [data-theme="dark"] .badge-red    { color: var(--red); }
    [data-theme="dark"] .badge-purple { color: var(--purple); }
    [data-theme="dark"] .badge-orange { color: var(--orange); }

    .status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .dot-green  { background: #10b981; }
    .dot-yellow { background: #f59e0b; }
    .dot-red    { background: #ef4444; }
    .dot-blue   { background: #3b82f6; }
    .dot-gray   { background: #94a3b8; }

    /* ─── Action Cell ───────────────────────────────────────── */
    .action-cell { display: flex; gap: 0.35rem; flex-wrap: wrap; }

    /* ─── Ops Grid (two columns) ────────────────────────────── */
    .ops-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
      margin-bottom: 1.25rem;
    }
    .ops-grid .card { margin-bottom: 0; }

    /* ─── Promo Cards ───────────────────────────────────────── */
    .promo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
      gap: 0.85rem;
      padding: 1.25rem 1.5rem;
    }
    .promo-card {
      background: var(--bg-card-inner);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 1rem;
      transition: var(--transition);
      position: relative;
    }
    .promo-card:hover { border-color: var(--blue); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(59,130,246,0.1); }
    .promo-card-title { font-size: 0.875rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.35rem; }
    .promo-card-desc  { font-size: 0.77rem; color: var(--text-muted); margin-bottom: 0.65rem; line-height: 1.5; }
    .promo-card-footer { display: flex; align-items: center; justify-content: space-between; }
    .promo-price { font-size: 0.875rem; font-weight: 700; color: var(--accent); }
    .promo-remove {
      position: absolute; top: 0.6rem; right: 0.7rem;
      background: none; border: none;
      color: var(--text-muted); font-size: 0.85rem;
      cursor: pointer; transition: var(--transition);
      padding: 0.15rem 0.3rem; border-radius: 4px;
    }
    .promo-remove:hover { color: var(--red); background: var(--red-light); }

    /* ─── Tips Card ─────────────────────────────────────────── */
    .tips-card {
      background: linear-gradient(135deg, #1d4ed8 0%, #4f46e5 100%);
      border: none;
      border-radius: var(--radius);
      padding: 1.5rem 1.75rem;
      margin-bottom: 1.25rem;
      display: flex; align-items: flex-start; gap: 1rem;
      box-shadow: 0 4px 20px rgba(59,130,246,0.25);
    }
    [data-theme="dark"] .tips-card { background: linear-gradient(135deg, #1e3a8a 0%, #312e81 100%); }
    .tips-icon { font-size: 1.75rem; flex-shrink: 0; margin-top: 0.1rem; }
    .tips-body { }
    .tips-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255,255,255,0.6); margin-bottom: 0.35rem; }
    .tips-text { font-size: 0.875rem; color: rgba(255,255,255,0.92); line-height: 1.65; font-weight: 400; }

    /* ─── Ticket Priority ───────────────────────────────────── */
    .prio-high   { color: var(--red);    font-weight: 700; font-size: 0.78rem; }
    .prio-medium { color: var(--yellow); font-weight: 700; font-size: 0.78rem; }
    .prio-low    { color: var(--accent); font-weight: 700; font-size: 0.78rem; }

    /* ─── Modal ─────────────────────────────────────────────── */
    .modal-overlay {
      display: none;
      position: fixed; inset: 0;
      background: var(--bg-modal);
      backdrop-filter: blur(6px);
      z-index: 500;
      align-items: center; justify-content: center;
      padding: 1rem;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow-modal);
      width: 100%; max-width: 500px;
      max-height: 90vh; overflow-y: auto;
      animation: modalIn 0.3s cubic-bezier(0.34,1.2,0.64,1);
    }
    .modal-overlay:not(.open) .modal-box { animation: modalOut 0.22s ease forwards; }
    @keyframes modalIn { from { opacity:0; transform:scale(0.93) translateY(16px); } to { opacity:1; transform:scale(1) translateY(0); } }
    @keyframes modalOut { from { opacity:1; transform:scale(1); } to { opacity:0; transform:scale(0.95); } }
    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid var(--border);
    }
    .modal-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); }
    .modal-close {
      background: none; border: 1px solid var(--border);
      border-radius: var(--radius-sm); width: 30px; height: 30px;
      display: flex; align-items: center; justify-content: center;
      color: var(--text-muted); font-size: 1rem; cursor: pointer;
      transition: var(--transition);
    }
    .modal-close:hover { border-color: var(--red); color: var(--red); background: var(--red-light); }
    .modal-body { padding: 1.5rem; }

    .form-group { margin-bottom: 1.1rem; }
    .form-label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.4rem; }
    .form-input, .form-select, .form-textarea {
      width: 100%; padding: 0.6rem 0.85rem;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: var(--radius-sm); color: var(--text-primary);
      font-size: 0.875rem; outline: none; transition: var(--transition);
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
      border-color: var(--blue); box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
    }
    .form-input.error, .form-select.error, .form-textarea.error { border-color: var(--red); }
    .form-textarea { resize: vertical; min-height: 80px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0.85rem; }
    .form-error { font-size: 0.75rem; color: var(--red); margin-top: 0.25rem; display: none; }
    .form-error.show { display: block; }

    .modal-footer {
      display: flex; justify-content: flex-end; gap: 0.65rem;
      padding: 1rem 1.5rem;
      border-top: 1px solid var(--border);
    }

    /* ─── Toast ─────────────────────────────────────────────── */
    #toast-container {
      position: fixed; bottom: 1.5rem; right: 1.5rem;
      z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem;
    }
    .toast {
      display: flex; align-items: center; gap: 0.65rem;
      padding: 0.75rem 1.1rem;
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      font-size: 0.85rem; color: var(--text-primary);
      min-width: 240px; max-width: 320px;
      animation: toastIn 0.3s ease;
    }
    .toast.removing { animation: toastOut 0.25s ease forwards; }
    @keyframes toastIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
    @keyframes toastOut { from { opacity:1; transform:translateX(0); } to { opacity:0; transform:translateX(20px); } }
    .toast-icon { font-size: 1rem; flex-shrink: 0; }
    .toast-text { flex: 1; font-weight: 500; }
    .toast-close { background: none; border: none; color: var(--text-muted); font-size: 1rem; padding: 0; cursor: pointer; }

    /* ─── Sidebar Overlay ───────────────────────────────────── */
    .sidebar-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.4); z-index: 190;
    }

    /* ─── Empty State ───────────────────────────────────────── */
    .empty-state {
      text-align: center; padding: 3rem 1rem; color: var(--text-muted);
    }
    .empty-state-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
    .empty-state-title { font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); margin-bottom: 0.3rem; }
    .empty-state-desc { font-size: 0.8rem; }

    /* ─── Responsive ────────────────────────────────────────── */
    @media (max-width: 1024px) {
      .ops-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 768px) {
      :root { --sidebar-w: 0px; }
      .sidebar { width: 240px; transform: translateX(-100%); }
      .sidebar.open { transform: translateX(0); }
      .sidebar-overlay { display: block; opacity: 0; pointer-events: none; transition: opacity 0.3s; }
      .sidebar-overlay.open { opacity: 1; pointer-events: all; }
      .main-area { margin-left: 0; }
      .menu-toggle-btn { display: flex; }
      .topbar-page-title { display: block; }
      .page-content { padding: 1.25rem 1rem; }
      .search-box input { width: 130px; }
      .card-body { padding: 1rem; }
      .card-header-row { padding: 0.9rem 1rem; }
    }
    @media (max-width: 480px) {
      .topbar { padding: 0 1rem; }
      td, th { padding: 0.7rem 0.75rem; }
      .promo-grid { grid-template-columns: 1fr; }
      .form-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

<!-- Toast Container -->
<div id="toast-container" aria-live="polite" role="status"></div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- ── MODAL: BUAT PROMO ───────────────────────────────────── -->
<div class="modal-overlay" id="promoModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title" id="modalTitle">✨ Buat Penawaran Baru</span>
      <button class="modal-close" id="modalClose" aria-label="Tutup modal">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label" for="promoTitle">Judul Promo <span style="color:var(--red)">*</span></label>
        <input class="form-input" id="promoTitle" type="text" placeholder="Contoh: Voucher Ramadan Ceria" />
        <div class="form-error" id="err-promoTitle">Judul promo wajib diisi</div>
      </div>
      <div class="form-group">
        <label class="form-label" for="promoDesc">Deskripsi Singkat <span style="color:var(--red)">*</span></label>
        <textarea class="form-textarea" id="promoDesc" placeholder="Jelaskan keuntungan penawaran ini…"></textarea>
        <div class="form-error" id="err-promoDesc">Deskripsi wajib diisi</div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="promoPrice">Harga (Rp) <span style="color:var(--red)">*</span></label>
          <input class="form-input" id="promoPrice" type="number" min="0" placeholder="299000" />
          <div class="form-error" id="err-promoPrice">Harga wajib diisi</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="promoTarget">Target Tier <span style="color:var(--red)">*</span></label>
          <select class="form-select" id="promoTarget">
            <option value="">-- Pilih Target --</option>
            <option value="ALL">Semua Pengguna</option>
            <option value="FREE">FREE</option>
            <option value="PREMIUM">PREMIUM</option>
          </select>
          <div class="form-error" id="err-promoTarget">Target wajib dipilih</div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="promoStart">Tanggal Mulai <span style="color:var(--red)">*</span></label>
          <input class="form-input" id="promoStart" type="date" />
          <div class="form-error" id="err-promoStart">Tanggal mulai wajib diisi</div>
        </div>
        <div class="form-group">
          <label class="form-label" for="promoEnd">Tanggal Berakhir <span style="color:var(--red)">*</span></label>
          <input class="form-input" id="promoEnd" type="date" />
          <div class="form-error" id="err-promoEnd">Tanggal berakhir wajib diisi</div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" id="modalCancel">Batal</button>
      <button class="btn btn-blue" id="modalSubmit">🚀 Publikasikan Promo</button>
    </div>
  </div>
</div>

<!-- App Shell -->
<div class="app-shell">

  <!-- ══ SIDEBAR ════════════════════════════════════════════ -->
  <aside class="sidebar" id="sidebar" aria-label="Navigasi Operator">
    <a href="index.html" class="sidebar-brand" aria-label="UMKM Insight Home">
      <div class="brand-logo" aria-hidden="true">
        <svg viewBox="0 0 24 24"><path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm3 11a4 4 0 1 1 0 8 4 4 0 0 1 0-8z"/></svg>
      </div>
      <div class="brand-info">
        <div class="brand-name">UMKM Insight</div>
        <div class="brand-role">Operator Panel</div>
      </div>
    </a>

    <nav class="sidebar-nav" aria-label="Menu Operator">
      <div class="nav-section-label">Menu Utama</div>
      <button class="nav-item active" data-section="ops-mgmt" id="nav-ops-mgmt" aria-current="page">
        <span class="nav-icon" aria-hidden="true">🎛️</span>
        Manajemen Operasional
      </button>
      <button class="nav-item" data-section="tickets" id="nav-tickets">
        <span class="nav-icon" aria-hidden="true">🎫</span>
        Tiket Pengaduan
        <span class="nav-badge" id="openTicketsBadge">0</span>
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar" aria-hidden="true"><?php echo htmlspecialchars($operatorInitials); ?></div>
        <div class="user-info">
          <div class="user-name"><?php echo htmlspecialchars($operatorName); ?></div>
          <div class="user-role-tag">Operator</div>
        </div>
      </div>
      <a href="logout.php" class="btn-logout">
        <span aria-hidden="true">←</span>
        Keluar
      </a>
    </div>
  </aside>

  <!-- ══ MAIN AREA ══════════════════════════════════════════ -->
  <div class="main-area">

    <!-- Topbar -->
    <header class="topbar" role="banner">
      <div class="topbar-left">
        <button class="menu-toggle-btn" id="menuToggle" aria-label="Buka menu sidebar" aria-expanded="false">☰</button>
        <div>
          <div class="topbar-date" id="topbarDate">Senin, 01 Juni 2026</div>
          <div class="topbar-page-title" id="topbarPageTitle">Manajemen Operasional</div>
        </div>
      </div>
      <div class="topbar-right">
        <button class="notif-btn" id="notifBtn" aria-label="Notifikasi tiket terbuka">
          🔔
          <span class="notif-dot" aria-hidden="true"></span>
        </button>
        <button class="theme-pill" id="themeToggle" aria-label="Toggle dark mode">
          <span id="toggleIcon">🌙</span>
          <div class="toggle-track" id="toggleTrack">
            <div class="toggle-thumb"></div>
          </div>
        </button>
        <div class="topbar-avatar" title="<?php echo htmlspecialchars($operatorName); ?> - Operator" aria-label="Avatar <?php echo htmlspecialchars($operatorName); ?>"><?php echo htmlspecialchars($operatorInitials); ?></div>
      </div>
    </header>

    <!-- ═══ PAGE CONTENT ════════════════════════════════════ -->
    <main class="page-content" id="pageContent">

      <!-- ── SECTION 1: MANAJEMEN OPERASIONAL ──────────────── -->
      <section class="section-panel active" id="section-ops-mgmt" aria-labelledby="heading-ops">
        <div class="page-header">
          <h2 id="heading-ops">Pusat Kendali Operasional</h2>
          <p>Kelola tier pengguna, penawaran promo, dan pengaduan layanan.</p>
        </div>

        <!-- Tips Card -->
        <div class="tips-card" role="note" aria-label="Tips operator">
          <div class="tips-icon" aria-hidden="true">💡</div>
          <div class="tips-body">
            <div class="tips-label">Tips Operator</div>
            <div class="tips-text">Sebelum melakukan approve PRO, pastikan bukti pembayaran atau validasi transaksi sudah sesuai dengan ID pengguna yang bersangkutan.</div>
          </div>
        </div>

        <!-- Top Row: Pengajuan + Pengaduan -->
        <div class="ops-grid">

          <!-- Pengajuan Tier Premium -->
          <div class="card">
            <div class="card-header-row">
              <div class="card-title-row">
                <h3>🏆 Pengajuan Tier Premium</h3>
                <p id="pengajuanCount">3 pengajuan menunggu tindakan</p>
              </div>
            </div>
            <div class="table-wrap">
              <table id="pengajuanTable" aria-label="Tabel pengajuan tier premium">
                <thead>
                  <tr>
                    <th scope="col">UMKM / Bisnis</th>
                    <th scope="col">Waktu Pengajuan</th>
                    <th scope="col">Nilai</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody id="pengajuanBody"></tbody>
              </table>
            </div>
          </div>

          <!-- Pengaduan Terkini -->
          <div class="card">
            <div class="card-header-row">
              <div class="card-title-row">
                <h3>📨 Pengaduan Terkini</h3>
                <p>Tiket masuk yang memerlukan respons</p>
              </div>
              <button class="btn btn-sm btn-outline" onclick="activateSection('tickets')">Lihat Semua Tiket</button>
            </div>
            <div class="table-wrap">
              <table aria-label="Ringkasan pengaduan terkini">
                <thead>
                  <tr>
                    <th scope="col">Subjek</th>
                    <th scope="col">Pengguna</th>
                    <th scope="col">Status</th>
                    <th scope="col">Aksi</th>
                  </tr>
                </thead>
                <tbody id="recentComplaintsBody"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Manajemen Tier Pengguna -->
        <div class="card">
          <div class="card-header-row">
            <div class="card-title-row">
              <h3>👥 Manajemen Tier Pengguna</h3>
              <p>Kelola akses tier seluruh pengguna aktif</p>
            </div>
          </div>
          <div class="table-wrap">
            <table id="tierTable" aria-label="Tabel manajemen tier pengguna">
              <thead>
                <tr>
                  <th scope="col">Pengguna</th>
                  <th scope="col">Bisnis</th>
                  <th scope="col">Tier Saat Ini</th>
                  <th scope="col">Aktivitas Terakhir</th>
                  <th scope="col">Aksi</th>
                </tr>
              </thead>
              <tbody id="tierBody"></tbody>
            </table>
          </div>
        </div>

        <!-- Kelola Penawaran & Promo -->
        <div class="card">
          <div class="card-header-row">
            <div class="card-title-row">
              <h3>🎁 Kelola Penawaran &amp; Promo</h3>
              <p>Buat dan kelola penawaran untuk pengguna platform</p>
            </div>
            <button class="btn btn-blue" id="openPromoModal" aria-haspopup="dialog">
              ＋ Buat Promo
            </button>
          </div>
          <div class="promo-grid" id="promoGrid" aria-label="Daftar penawaran aktif"></div>
        </div>

      </section>

      <!-- ── SECTION 2: TIKET PENGADUAN ─────────────────────── -->
      <section class="section-panel" id="section-tickets" aria-labelledby="heading-tickets">
        <div class="page-header">
          <h2 id="heading-tickets">Manajemen Tiket Bantuan</h2>
          <p>Tanggapi dan selesaikan keluhan pengguna untuk menjaga kualitas layanan.</p>
        </div>

        <div class="card">
          <div class="card-header-row">
            <div class="card-title-row">
              <h3>Daftar Tiket Pengaduan</h3>
              <p id="ticketSummary">0 tiket total - 0 terbuka</p>
            </div>
            <div class="toolbar">
              <div class="search-box" role="search">
                <span class="search-icon" aria-hidden="true">🔍</span>
                <input type="text" id="ticketSearch" placeholder="Cari tiket, UMKM, atau subjek…" aria-label="Cari tiket" />
              </div>
              <select class="filter-select" id="ticketStatusFilter" aria-label="Filter status tiket">
                <option value="">Semua Status</option>
                <option value="open">Terbuka</option>
                <option value="resolved">Selesai</option>
              </select>
            </div>
          </div>
          <div class="table-wrap">
            <table id="ticketTable" aria-label="Tabel tiket bantuan">
              <thead>
                <tr>
                  <th scope="col">ID</th>
                  <th scope="col">UMKM / Pengguna</th>
                  <th scope="col">Subjek &amp; Keluhan</th>
                  <th scope="col">Waktu Masuk</th>
                  <th scope="col">Status</th>
                  <th scope="col">Prioritas</th>
                  <th scope="col">Aksi</th>
                </tr>
              </thead>
              <tbody id="ticketBody"></tbody>
            </table>
            <div id="ticketEmptyState" class="empty-state" style="display:none;" aria-live="polite">
              <div class="empty-state-icon" aria-hidden="true">🎫</div>
              <div class="empty-state-title">Tidak ada tiket ditemukan</div>
              <div class="empty-state-desc">Coba ubah filter atau kata kunci pencarian</div>
            </div>
          </div>
        </div>
      </section>

    </main>
  </div><!-- /main-area -->
</div><!-- /app-shell -->

<script>
  'use strict';

  // ─── Backend Data ──────────────────────────────────────────────
  const PENGAJUAN = <?php echo json_encode($pengajuanForUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  const COMPLAINTS = <?php echo json_encode($complaintsForUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  const TIER_USERS = <?php echo json_encode($tierUsersForUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  let PROMOS = <?php echo json_encode($promosForUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  let TICKETS = <?php echo json_encode($ticketsForUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

  // ─── Theme ──────────────────────────────────────────────────
  const html = document.documentElement;

  function applyTheme(theme) {
    html.setAttribute('data-theme', theme);
    const isDark = theme === 'dark';
    document.getElementById('toggleTrack').classList.toggle('active', isDark);
    document.getElementById('toggleIcon').textContent = isDark ? '☀️' : '🌙';
  }

  function initTheme() {
    applyTheme(localStorage.getItem('umkm-theme') || 'light');
  }

  document.getElementById('themeToggle').addEventListener('click', () => {
    const next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    localStorage.setItem('umkm-theme', next);
    applyTheme(next);
  });

  // ─── Toast ──────────────────────────────────────────────────
  function showToast(message, icon = '✅') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
      <span class="toast-icon" aria-hidden="true">${icon}</span>
      <span class="toast-text">${message}</span>
      <button class="toast-close" aria-label="Tutup notifikasi">✕</button>
    `;
    toast.querySelector('.toast-close').addEventListener('click', () => removeToast(toast));
    container.appendChild(toast);
    setTimeout(() => removeToast(toast), 3500);
  }

  function removeToast(toast) {
    if (!toast.parentElement) return;
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 260);
  }

  // ─── Sidebar Navigation ─────────────────────────────────────
  const sections = ['ops-mgmt', 'tickets'];
  const sectionTitles = {
    'ops-mgmt': 'Manajemen Operasional',
    'tickets':  'Tiket Pengaduan',
  };

  function activateSection(id) {
    sections.forEach(s => {
      document.getElementById(`section-${s}`).classList.toggle('active', s === id);
      const btn = document.getElementById(`nav-${s}`);
      btn.classList.toggle('active', s === id);
      btn.setAttribute('aria-current', s === id ? 'page' : 'false');
    });
    document.getElementById('topbarPageTitle').textContent = sectionTitles[id] || '';
    closeSidebar();
  }

  sections.forEach(s => {
    document.getElementById(`nav-${s}`).addEventListener('click', () => activateSection(s));
  });

  // ─── Mobile Sidebar ─────────────────────────────────────────
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');
  const menuToggle = document.getElementById('menuToggle');

  function openSidebar() {
    sidebar.classList.add('open');
    sidebarOverlay.classList.add('open');
    menuToggle.setAttribute('aria-expanded', 'true');
  }
  function closeSidebar() {
    sidebar.classList.remove('open');
    sidebarOverlay.classList.remove('open');
    menuToggle.setAttribute('aria-expanded', 'false');
  }
  menuToggle.addEventListener('click', () => sidebar.classList.contains('open') ? closeSidebar() : openSidebar());
  sidebarOverlay.addEventListener('click', closeSidebar);

  // ─── Render Helpers ─────────────────────────────────────────
  function tierBadge(tier) {
    const map = { 'FREE':'badge-gray', 'PREMIUM':'badge-yellow', 'ALL':'badge-blue' };
    return `<span class="badge ${map[tier] || 'badge-gray'}">${tier}</span>`;
  }

  function statusBadge(status) {
    const upper = status.toUpperCase();
    if (upper === 'OPEN')     return `<span class="badge badge-red"><span class="status-dot dot-red"></span> Open</span>`;
    if (upper === 'RESOLVED') return `<span class="badge badge-green"><span class="status-dot dot-green"></span> Resolved</span>`;
    if (upper === 'PENDING')  return `<span class="badge badge-yellow"><span class="status-dot dot-yellow"></span> Pending</span>`;
    if (upper === 'REVIEW')   return `<span class="badge badge-blue"><span class="status-dot dot-blue"></span> Review</span>`;
    if (upper === 'MENUNGGU') return `<span class="badge badge-orange"><span class="status-dot dot-yellow"></span> Menunggu</span>`;
    return `<span class="badge badge-gray">${status}</span>`;
  }

  function priorityTag(p) {
    if (p === 'high')   return `<span class="prio-high">▲ High</span>`;
    if (p === 'medium') return `<span class="prio-medium">● Medium</span>`;
    return `<span class="prio-low">▼ Low</span>`;
  }

  // ─── Render Pengajuan ────────────────────────────────────────
  function renderPengajuan() {
    const tbody = document.getElementById('pengajuanBody');
    if (PENGAJUAN.length === 0) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Tidak ada pengajuan</td></tr>'; return; }
    tbody.innerHTML = PENGAJUAN.map(p => `
      <tr>
        <td>
          <div class="user-cell">
            <div class="mini-avatar ${p.avatarClass}" aria-hidden="true">${p.initials}</div>
            <span style="font-weight:600;font-size:0.855rem;">${p.business}</span>
          </div>
        </td>
        <td style="font-size:0.8rem;color:var(--text-muted);">${p.time}</td>
        <td style="font-weight:600;color:var(--accent);font-size:0.85rem;">${p.value}</td>
        <td>${statusBadge(p.status)}</td>
        <td>
          <div class="action-cell">
            <button class="btn btn-sm btn-success" onclick="approvePengajuan(${p.id})">✓ Approve</button>
            <button class="btn btn-sm btn-danger"  onclick="rejectPengajuan(${p.id})">✕ Reject</button>
          </div>
        </td>
      </tr>
    `).join('');
  }

  function approvePengajuan(id) {
    const item = PENGAJUAN.find(p => p.id === id);
    if (!item) return;
    showToast('Approval tier perlu dilakukan lewat halaman langganan admin yang sudah terhubung database.', 'ℹ️');
  }
  function rejectPengajuan(id) {
    const item = PENGAJUAN.find(p => p.id === id);
    if (!item) return;
    showToast('Reject tier perlu endpoint backend agar perubahan tersimpan.', 'ℹ️');
  }

  // ─── Render Recent Complaints ────────────────────────────────
  function renderComplaints() {
    const tbody = document.getElementById('recentComplaintsBody');
    if (COMPLAINTS.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Belum ada pengaduan terbaru</td></tr>';
      return;
    }
    tbody.innerHTML = COMPLAINTS.map(c => `
      <tr>
        <td style="font-size:0.84rem;font-weight:500;">${c.subject}</td>
        <td style="font-size:0.82rem;color:var(--text-secondary);">${c.user}</td>
        <td>${statusBadge(c.status)}</td>
        <td>
          <button class="btn btn-sm btn-info" onclick="showToast('Balasan tiket perlu endpoint backend agar tersimpan','ℹ️')">Detail</button>
        </td>
      </tr>
    `).join('');
  }

  // ─── Render Tier Users ────────────────────────────────────────
  function renderTierUsers() {
    const tbody = document.getElementById('tierBody');
    if (TIER_USERS.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:1.5rem;">Belum ada client UMKM</td></tr>';
      return;
    }
    tbody.innerHTML = TIER_USERS.map(u => `
      <tr>
        <td>
          <div class="user-cell">
            <div class="mini-avatar ${u.avatarClass}" aria-hidden="true">${u.initials}</div>
            <span style="font-weight:600;font-size:0.855rem;">${u.name}</span>
          </div>
        </td>
        <td style="font-size:0.82rem;color:var(--text-secondary);">${u.business}</td>
        <td>${tierBadge(u.tier)}</td>
        <td style="font-size:0.8rem;color:var(--text-muted);">${u.lastActivity}</td>
        <td>
          <button class="btn btn-sm btn-info"
            onclick="tierAction(${u.id})">
            ${u.actionLabel}
          </button>
        </td>
      </tr>
    `).join('');
  }

  function tierAction(id) {
    const user = TIER_USERS.find(u => u.id === id);
    if (!user) return;
    showToast(`Status ${user.name} dibaca dari database. Perubahan tier dilakukan lewat alur langganan.`, 'ℹ️');
  }

  // ─── Render Promos ───────────────────────────────────────────
  function renderPromos() {
    const grid = document.getElementById('promoGrid');
    if (PROMOS.length === 0) {
      grid.innerHTML = '<div style="padding:1.5rem;color:var(--text-muted);font-size:0.85rem;grid-column:1/-1;text-align:center;">Belum ada promo. Klik "Buat Promo" untuk menambahkan.</div>';
      return;
    }
    grid.innerHTML = PROMOS.map(p => `
      <div class="promo-card">
        <button class="promo-remove" onclick="removePromo(${p.id})" aria-label="Hapus promo ${p.title}">✕</button>
        <div class="promo-card-title">${p.title}</div>
        <div class="promo-card-desc">${p.desc}</div>
        <div class="promo-card-footer">
          <span class="promo-price">${p.price}</span>
          ${tierBadge(p.target)}
        </div>
      </div>
    `).join('');
  }

  function removePromo(id) {
    const idx = PROMOS.findIndex(p => p.id === id);
    if (idx === -1) return;
    showToast('Hapus promo perlu endpoint backend agar perubahan tersimpan.', 'ℹ️');
  }

  // ─── Promo Modal ─────────────────────────────────────────────
  const promoModal = document.getElementById('promoModal');

  function openModal() {
    promoModal.classList.add('open');
    document.getElementById('promoTitle').focus();
    // set default dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('promoStart').value = today;
    const end = new Date(); end.setMonth(end.getMonth() + 1);
    document.getElementById('promoEnd').value = end.toISOString().split('T')[0];
  }

  function closeModal() {
    promoModal.classList.remove('open');
    clearModalErrors();
    document.getElementById('promoTitle').value = '';
    document.getElementById('promoDesc').value = '';
    document.getElementById('promoPrice').value = '';
    document.getElementById('promoTarget').value = '';
    document.getElementById('promoStart').value = '';
    document.getElementById('promoEnd').value = '';
  }

  function clearModalErrors() {
    ['promoTitle','promoDesc','promoPrice','promoTarget','promoStart','promoEnd'].forEach(id => {
      document.getElementById(id).classList.remove('error');
      document.getElementById(`err-${id}`).classList.remove('show');
    });
  }

  function validateModal() {
    clearModalErrors();
    const fields = [
      { id:'promoTitle',  val: document.getElementById('promoTitle').value.trim() },
      { id:'promoDesc',   val: document.getElementById('promoDesc').value.trim() },
      { id:'promoPrice',  val: document.getElementById('promoPrice').value.trim() },
      { id:'promoTarget', val: document.getElementById('promoTarget').value },
      { id:'promoStart',  val: document.getElementById('promoStart').value },
      { id:'promoEnd',    val: document.getElementById('promoEnd').value },
    ];
    let valid = true;
    fields.forEach(f => {
      if (!f.val) {
        document.getElementById(f.id).classList.add('error');
        document.getElementById(`err-${f.id}`).classList.add('show');
        valid = false;
      }
    });
    return valid;
  }

  document.getElementById('openPromoModal').addEventListener('click', openModal);
  document.getElementById('modalClose').addEventListener('click', closeModal);
  document.getElementById('modalCancel').addEventListener('click', closeModal);

  promoModal.addEventListener('click', e => {
    if (e.target === promoModal) closeModal();
  });

  document.getElementById('modalSubmit').addEventListener('click', () => {
    if (!validateModal()) return;
    closeModal();
    showToast('Form promo belum terhubung ke endpoint penyimpanan database.', 'ℹ️');
  });

  // ─── Render Tickets ──────────────────────────────────────────
  function renderTickets(list) {
    const tbody = document.getElementById('ticketBody');
    const empty = document.getElementById('ticketEmptyState');
    const openCount = TICKETS.filter(t => t.status === 'open').length;
    document.getElementById('ticketSummary').textContent = `${TICKETS.length} tiket total · ${openCount} terbuka`;
    document.getElementById('openTicketsBadge').textContent = openCount;

    if (list.length === 0) {
      tbody.innerHTML = '';
      empty.style.display = '';
      return;
    }
    empty.style.display = 'none';
    tbody.innerHTML = list.map(t => {
      const isResolved = t.status === 'resolved';
      return `
        <tr id="ticket-row-${t.id}">
          <td style="font-family:'Courier New',monospace;font-size:0.8rem;font-weight:600;color:var(--text-muted);">#${t.id}</td>
          <td>
            <div class="user-cell-info">
              <div class="user-cell-name">${t.umkm}</div>
              <div class="user-cell-sub">${t.user}</div>
            </div>
          </td>
          <td>
            <div style="font-size:0.84rem;font-weight:600;color:var(--text-primary);">${t.subject}</div>
            <div style="font-size:0.75rem;color:var(--text-muted);">${t.detail}</div>
          </td>
          <td style="font-size:0.79rem;color:var(--text-muted);white-space:nowrap;">${t.time}</td>
          <td>${statusBadge(t.status)}</td>
          <td>${priorityTag(t.priority)}</td>
          <td>
            ${isResolved
              ? `<span class="badge badge-green" style="font-size:0.75rem;">✓ Resolved</span>`
              : `<button class="btn btn-sm btn-success" onclick="resolveTicket('${t.id}')">✓ Selesaikan</button>`
            }
          </td>
        </tr>
      `;
    }).join('');
  }

  function resolveTicket(id) {
    const ticket = TICKETS.find(t => t.id === id);
    if (!ticket || ticket.status === 'resolved') return;
    showToast('Penyelesaian tiket perlu endpoint backend agar status tersimpan.', 'ℹ️');
  }

  function filterTickets() {
    const q      = document.getElementById('ticketSearch').value.toLowerCase().trim();
    const status = document.getElementById('ticketStatusFilter').value;
    const filtered = TICKETS.filter(t => {
      const matchQ      = !q      || t.id.toLowerCase().includes(q) || t.umkm.toLowerCase().includes(q) || t.user.toLowerCase().includes(q) || t.subject.toLowerCase().includes(q);
      const matchStatus = !status || t.status === status;
      return matchQ && matchStatus;
    });
    renderTickets(filtered);
  }

  document.getElementById('ticketSearch').addEventListener('input', filterTickets);
  document.getElementById('ticketStatusFilter').addEventListener('change', filterTickets);
  document.getElementById('notifBtn').addEventListener('click', () => {
    const openCount = TICKETS.filter(t => t.status === 'open').length;
    showToast(`${openCount} tiket masih terbuka dan menunggu respons`, '🔔');
  });

  // ─── Date ───────────────────────────────────────────────────
  function setDate() {
    const days   = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const d = new Date();
    document.getElementById('topbarDate').textContent = `${days[d.getDay()]}, ${String(d.getDate()).padStart(2,'0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
  }

  // ─── Init ────────────────────────────────────────────────────
  function init() {
    initTheme();
    setDate();
    renderPengajuan();
    renderComplaints();
    renderTierUsers();
    renderPromos();
    renderTickets(TICKETS);
  }

  init();
</script>
</body>
</html>
