<?php
/**
 * api-integrator.php
 * Halaman "Integrasi Gateway" — UI PHP UMKM Insight
 * Menampilkan koneksi dan interaksi dengan API Integrator (Python FastAPI)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$pageTitle  = 'Integrasi Gateway';
$activePage = 'api-integrator';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
include __DIR__ . '/includes/topbar.php';
?>

<main class="main-content">
  <!-- ──────────────────────────────────────────────────────── -->
  <!-- PAGE HEADER                                             -->
  <!-- ──────────────────────────────────────────────────────── -->
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
        <i class="ph ph-plugs-connected text-brand-500 text-3xl"></i>
        Integrasi Gateway
      </h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Hubungkan UMKM Insight dengan API Integrator (Python FastAPI) untuk routing transaksi melalui Gateway
      </p>
    </div>
    <button onclick="refreshAll()" id="btnRefreshAll"
      class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium
             bg-brand-500 text-white hover:bg-brand-600 transition-all duration-200 shadow-sm"
      title="Cek status kesehatan server &amp; memuat ulang routing table gateway">
      <i class="ph ph-arrows-clockwise"></i> Refresh Status
      <span class="w-4 h-4 rounded-full bg-white/20 text-white text-[10px] font-bold flex items-center justify-center ml-1">i</span>
    </button>
  </div>

  <!-- ──────────────────────────────────────────────────────── -->
  <!-- ROW 1: STATUS CARDS                                     -->
  <!-- ──────────────────────────────────────────────────────── -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <!-- Kartu Status Koneksi -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Status Koneksi</span>
        <span id="statusBadge" class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full
              bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
          <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
          Cek...
        </span>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center">
          <i class="ph ph-link text-brand-500 text-xl"></i>
        </div>
        <div>
          <div class="text-lg font-bold text-gray-900 dark:text-white" id="statusLabel">—</div>
          <div class="text-xs text-gray-500 dark:text-gray-400" id="statusUrl">—</div>
        </div>
      </div>
    </div>

    <!-- Kartu Response Time -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Response Time</span>
        <i class="ph ph-timer text-brand-500"></i>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex items-center justify-center">
          <i class="ph ph-gauge text-blue-500 text-xl"></i>
        </div>
        <div>
          <div class="text-lg font-bold text-gray-900 dark:text-white" id="responseTime">—</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Latency ke Integrator</div>
        </div>
      </div>
    </div>

    <!-- Kartu Jumlah Route -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5 shadow-sm flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">App Terdaftar</span>
        <i class="ph ph-graph text-purple-500"></i>
      </div>
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center">
          <i class="ph ph-circles-three-plus text-purple-500 text-xl"></i>
        </div>
        <div>
          <div class="text-lg font-bold text-gray-900 dark:text-white" id="totalApps">—</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Apps di Routing Table</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ──────────────────────────────────────────────────────── -->
  <!-- ROW 2: FORM KIRIM TRANSAKSI + ROUTING TABLE             -->
  <!-- ──────────────────────────────────────────────────────── -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    <!-- Panel Kirim Transaksi via Gateway -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
        <i class="ph ph-paper-plane-tilt text-brand-500 text-lg"></i>
        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Kirim Transaksi via Gateway</h2>
      </div>
      <div class="p-5 space-y-4">
        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5" for="txAmount">
            Jumlah Transaksi (Rp)
          </label>
          <input type="number" id="txAmount" min="1" placeholder="Contoh: 500000"
            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                   bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                   focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500
                   placeholder:text-gray-400 transition"
            oninput="updateFeePreview()">
        </div>

        <div id="feePreview" style="display:none"
          class="p-3 rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20
                 flex items-center justify-between text-sm">
          <span class="text-brand-700 dark:text-brand-300 font-medium">Fee Gateway (0.5%)</span>
          <span class="font-bold text-brand-600 dark:text-brand-400" id="feeValue">Rp 0</span>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5" for="txToken">
            Token JWT <span class="text-gray-400 font-normal">(dari API Integrator /api/login)</span>
          </label>
          <input type="text" id="txToken" placeholder="eyJhbGciOiJIUzI1NiIs..."
            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                   bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm font-mono
                   focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500
                   placeholder:text-gray-400 transition">
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5" for="txKeterangan">
            Keterangan
          </label>
          <input type="text" id="txKeterangan" value="Transaksi dari UMKM Insight" placeholder="Keterangan transaksi"
            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700
                   bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white text-sm
                   focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500
                   placeholder:text-gray-400 transition">
        </div>

        <button onclick="kirimTransaksi()" id="btnKirim"
          class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold
                 bg-brand-500 text-white hover:bg-brand-600 active:scale-95 transition-all duration-150 shadow-sm"
          title="Meneruskan request transaksi keuangan ini ke API Gateway (Python FastAPI) lalu diteruskan ke SmartBank">
          <i class="ph ph-rocket-launch"></i>
          Kirim via API Gateway
          <span class="w-4 h-4 rounded-full bg-white/20 text-white text-[10px] font-bold flex items-center justify-center ml-1">i</span>
        </button>
      </div>

      <!-- Response Panel -->
      <div id="txResponseWrap" style="display:none" class="border-t border-gray-100 dark:border-gray-800">
        <div class="px-5 py-3 flex items-center justify-between bg-gray-50 dark:bg-gray-800/50">
          <div class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <i class="ph ph-code-block"></i> Response JSON
          </div>
          <span id="txRespStatus" class="text-xs font-bold px-2 py-0.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">—</span>
        </div>
        <div class="px-5 py-3">
          <pre id="txResponseBody"
            class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 rounded-xl p-3
                   overflow-x-auto max-h-48 whitespace-pre-wrap break-all font-mono"></pre>
        </div>
      </div>
    </div>

    <!-- Panel Routing Table -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i class="ph ph-flow-arrow text-purple-500 text-lg"></i>
          <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Routing Table API Integrator</h2>
        </div>
        <button onclick="loadRoutes()" class="text-xs text-brand-500 hover:text-brand-600 font-medium flex items-center gap-1">
          <i class="ph ph-arrows-clockwise"></i> Muat
        </button>
      </div>
      <div id="routesContainer" class="p-5">
        <div class="text-center py-8 text-gray-400 dark:text-gray-600 text-sm">
          <i class="ph ph-arrows-clockwise text-2xl mb-2 block animate-spin-once"></i>
          Klik "Muat" atau "Refresh Status" untuk memuat routing table
        </div>
      </div>
    </div>
  </div>

  <!-- ──────────────────────────────────────────────────────── -->
  <!-- ROW 3: ALUR INTEGRASI + PUSH DATA                       -->
  <!-- ──────────────────────────────────────────────────────── -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

    <!-- Alur Integrasi PHP → Python -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5">
      <div class="flex items-center gap-2 mb-4">
        <i class="ph ph-arrows-left-right text-brand-500 text-lg"></i>
        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Alur Integrasi PHP ↔ Python FastAPI</h2>
      </div>
      <div class="space-y-3">
        <!-- Step 1 -->
        <div class="flex gap-3">
          <div class="flex-shrink-0 w-7 h-7 rounded-full bg-brand-500 text-white text-xs font-bold flex items-center justify-center shadow">1</div>
          <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">UMKM Insight (PHP) kirim request</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-brand-600 dark:text-brand-400 font-mono">POST /api/integrator_proxy.php?action=send-transaksi</code>
            </p>
          </div>
        </div>
        <!-- Arrow -->
        <div class="ml-3.5 w-px h-5 bg-gray-200 dark:bg-gray-700"></div>
        <!-- Step 2 -->
        <div class="flex gap-3">
          <div class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-500 text-white text-xs font-bold flex items-center justify-center shadow">2</div>
          <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">PHP meneruskan ke Python FastAPI</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
              <code class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-blue-600 dark:text-blue-400 font-mono">POST http://api-integrator:4001/integrator/umkm-insight/push-transaksi</code>
            </p>
          </div>
        </div>
        <!-- Arrow -->
        <div class="ml-3.5 w-px h-5 bg-gray-200 dark:bg-gray-700"></div>
        <!-- Step 3 -->
        <div class="flex gap-3">
          <div class="flex-shrink-0 w-7 h-7 rounded-full bg-purple-500 text-white text-xs font-bold flex items-center justify-center shadow">3</div>
          <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Python FastAPI validasi & routing</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">JWT check → rate limit → hitung fee → forward ke SmartBank</p>
          </div>
        </div>
        <!-- Arrow -->
        <div class="ml-3.5 w-px h-5 bg-gray-200 dark:bg-gray-700"></div>
        <!-- Step 4 -->
        <div class="flex gap-3">
          <div class="flex-shrink-0 w-7 h-7 rounded-full bg-emerald-500 text-white text-xs font-bold flex items-center justify-center shadow">4</div>
          <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">Response dikembalikan ke PHP</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">JSON response + fee_diambil + respons_dari_smartbank</p>
          </div>
        </div>
      </div>

      <!-- Info URL -->
      <div class="mt-4 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">URL Koneksi Internal Docker</p>
        <code class="text-xs font-mono text-brand-600 dark:text-brand-400">http://api-integrator:4001</code>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Dikonfigurasi via env <code>API_INTEGRATOR_BASE_URL</code></p>
      </div>
    </div>

    <!-- Panel Push Data Insight -->
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-2">
        <i class="ph ph-upload-simple text-emerald-500 text-lg"></i>
        <h2 class="font-semibold text-gray-900 dark:text-white text-sm">Push Data Insight ke Gateway</h2>
      </div>
      <div class="p-5 space-y-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Kirim ringkasan data transaksi UMKM Insight ke API Integrator sebagai sumber data insight bagi ecosystem Gateway.
        </p>

        <div class="p-3 rounded-xl border border-dashed border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
          <p class="text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Data yang akan dikirim:</p>
          <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-1 list-disc ml-4">
            <li>Ringkasan transaksi per tipe &amp; sumber (dari DB lokal)</li>
            <li>Metadata user UMKM Insight</li>
            <li>Timestamp push</li>
          </ul>
        </div>

        <button onclick="pushDataInsight()" id="btnPushData"
          class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold
                 bg-emerald-500 text-white hover:bg-emerald-600 active:scale-95 transition-all duration-150 shadow-sm"
          title="Mengirimkan ringkasan data transaksi lokal UMKM Insight ke database API Integrator">
          <i class="ph ph-upload-simple"></i>
          Push Data Insight
          <span class="w-4 h-4 rounded-full bg-white/20 text-white text-[10px] font-bold flex items-center justify-center ml-1">i</span>
        </button>

        <div id="pushResponseWrap" style="display:none">
          <div class="px-0 py-2 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Response</span>
            <span id="pushRespStatus" class="text-xs font-bold px-2 py-0.5 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300">—</span>
          </div>
          <pre id="pushResponseBody"
            class="text-xs text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-800 rounded-xl p-3
                   overflow-x-auto max-h-40 whitespace-pre-wrap break-all font-mono"></pre>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- ────────────────────────────────────────────────────────── -->
<!-- JAVASCRIPT                                                -->
<!-- ────────────────────────────────────────────────────────── -->
<script>
const PROXY_URL = 'api/integrator_proxy.php';

// Helpers
function fmt(n) {
  return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}
function setLoading(btnId, loading) {
  const btn = document.getElementById(btnId);
  if (!btn) return;
  btn.disabled = loading;
  btn.style.opacity = loading ? '0.6' : '1';
}

// ── Fee preview ───────────────────────────────────────────────
function updateFeePreview() {
  const amount = parseFloat(document.getElementById('txAmount').value) || 0;
  const wrap = document.getElementById('feePreview');
  const feeEl = document.getElementById('feeValue');
  if (amount > 0) {
    const fee = amount * 0.005;
    feeEl.textContent = fmt(fee) + ' (net: ' + fmt(amount - fee) + ')';
    wrap.style.display = 'flex';
  } else {
    wrap.style.display = 'none';
  }
}

// ── Status: cek health API Integrator ────────────────────────
async function checkHealth() {
  const badge = document.getElementById('statusBadge');
  const label = document.getElementById('statusLabel');
  const urlEl = document.getElementById('statusUrl');
  const rtEl  = document.getElementById('responseTime');

  badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-yellow-400 inline-block animate-pulse"></span> Mengecek...`;

  try {
    const res = await fetch(PROXY_URL + '?action=health');
    const data = await res.json();

    if (data.success) {
      badge.className = badge.className.replace(/bg-\S+\s*text-\S+/, '')
        + ' bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400';
      badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 inline-block"></span> Online`;
      label.textContent = 'API Integrator Online';
    } else {
      badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span> Offline`;
      label.textContent = 'Tidak Terhubung';
    }
    urlEl.textContent = data.integrator_url || 'http://api-integrator:4001';
    rtEl.textContent  = (data.response_time_ms || '—') + ' ms';
  } catch (e) {
    badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-red-400 inline-block"></span> Error`;
    label.textContent = 'Gagal cek status';
    rtEl.textContent  = '—';
  }
}

// ── Load routing table ────────────────────────────────────────
async function loadRoutes() {
  const container = document.getElementById('routesContainer');
  container.innerHTML = `<div class="text-center py-6 text-gray-400 text-sm animate-pulse">Memuat routing table...</div>`;

  try {
    const res  = await fetch(PROXY_URL + '?action=routes');
    const json = await res.json();

    if (!json.success || !json.data?.data?.daftar_app) {
      container.innerHTML = `<div class="text-center py-6 text-red-400 text-sm"><i class="ph ph-warning-circle mr-1"></i>${json.error || 'Gagal memuat routing table'}</div>`;
      document.getElementById('totalApps').textContent = '—';
      return;
    }

    const apps = json.data.data.daftar_app;
    document.getElementById('totalApps').textContent = apps.length;

    container.innerHTML = `
      <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
        ${apps.map(app => `
          <div class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center flex-shrink-0">
              <i class="ph ph-circles-three-plus text-purple-500 text-sm"></i>
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-xs font-bold text-gray-900 dark:text-white">${app.app_id}</div>
              <div class="text-xs text-gray-400 truncate">${app.peran || ''}</div>
            </div>
            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">${app.kelompok}</span>
          </div>
        `).join('')}
      </div>`;
  } catch (e) {
    container.innerHTML = `<div class="text-center py-6 text-red-400 text-sm"><i class="ph ph-x-circle mr-1"></i>Gagal memuat: ${e.message}</div>`;
  }
}

// ── Kirim Transaksi via Gateway ───────────────────────────────
async function kirimTransaksi() {
  const amount    = parseFloat(document.getElementById('txAmount').value) || 0;
  const token     = document.getElementById('txToken').value.trim();
  const keterangan = document.getElementById('txKeterangan').value.trim();

  if (amount <= 0) { alert('Masukkan jumlah transaksi yang valid (> 0).'); return; }
  if (!token)       { alert('Token JWT diperlukan. Dapatkan dari /api/login di API Integrator.'); return; }

  setLoading('btnKirim', true);

  try {
    const res  = await fetch(PROXY_URL + '?action=send-transaksi', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ amount, token, keterangan }),
    });
    const data = await res.json();

    const wrap   = document.getElementById('txResponseWrap');
    const body   = document.getElementById('txResponseBody');
    const status = document.getElementById('txRespStatus');

    wrap.style.display = 'block';
    body.textContent   = JSON.stringify(data, null, 2);
    status.textContent = data.success ? `✓ ${data.status_code || 200}` : `✗ ${data.status_code || 'Error'}`;
    status.className   = status.className.replace(/bg-\S+\s*text-\S+/, '')
      + (data.success ? ' bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                      : ' bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400');
  } catch (e) {
    document.getElementById('txResponseWrap').style.display = 'block';
    document.getElementById('txResponseBody').textContent = 'Error: ' + e.message;
  } finally {
    setLoading('btnKirim', false);
  }
}

// ── Push Data Insight ─────────────────────────────────────────
async function pushDataInsight() {
  setLoading('btnPushData', true);

  try {
    const res  = await fetch(PROXY_URL + '?action=push-data', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ source: 'umkm-insight-legacy-php' }),
    });
    const data = await res.json();

    const wrap   = document.getElementById('pushResponseWrap');
    const body   = document.getElementById('pushResponseBody');
    const status = document.getElementById('pushRespStatus');

    wrap.style.display = 'block';
    body.textContent   = JSON.stringify(data, null, 2);
    status.textContent = data.success ? '✓ Sukses' : '✗ Gagal';
    status.className   = status.className.replace(/bg-\S+\s*text-\S+/, '')
      + (data.success ? ' bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
                      : ' bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400');
  } catch (e) {
    document.getElementById('pushResponseWrap').style.display = 'block';
    document.getElementById('pushResponseBody').textContent = 'Error: ' + e.message;
  } finally {
    setLoading('btnPushData', false);
  }
}

// ── Refresh all ───────────────────────────────────────────────
async function refreshAll() {
  setLoading('btnRefreshAll', true);
  await Promise.all([checkHealth(), loadRoutes()]);
  setLoading('btnRefreshAll', false);
}

// Auto-load on page ready
document.addEventListener('DOMContentLoaded', () => refreshAll());
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
