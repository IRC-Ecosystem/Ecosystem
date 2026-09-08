<?php
/**
 * integrator_proxy.php
 * Proxy endpoint: UMKM Insight (PHP) → API Integrator (Python FastAPI)
 *
 * Actions:
 *   GET  ?action=health          → cek status koneksi ke API Integrator
 *   GET  ?action=routes          → ambil routing table dari API Integrator
 *   GET  ?action=request-stats   → ambil statistik request dari API Integrator
 *   POST ?action=send-transaksi  → kirim data transaksi ke API Integrator → SmartBank
 *   POST ?action=push-data       → push data insight ke API Integrator
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ──────────────────────────────────────────────────────────────
// Helper: ambil base URL API Integrator dari environment
// Di Docker: http://api-integrator:4001
// ──────────────────────────────────────────────────────────────
function getIntegratorBaseUrl(): string {
    return rtrim(getenv('API_INTEGRATOR_BASE_URL') ?: 'http://api-integrator:4001', '/');
}

/**
 * Kirim HTTP request ke API Integrator menggunakan stream_context (tanpa cURL)
 */
function callIntegrator(string $path, string $method = 'GET', array $body = [], array $extraHeaders = []): array {
    $baseUrl = getIntegratorBaseUrl();
    $url = $baseUrl . $path;
    $startTime = microtime(true);

    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    foreach ($extraHeaders as $k => $v) {
        $headers .= "{$k}: {$v}\r\n";
    }

    $opts = [
        'http' => [
            'method'        => strtoupper($method),
            'header'        => $headers,
            'timeout'       => 8,
            'ignore_errors' => true,
        ]
    ];

    if (!empty($body) && strtoupper($method) !== 'GET') {
        $opts['http']['content'] = json_encode($body);
    }

    $context = stream_context_create($opts);
    $raw = @file_get_contents($url, false, $context);
    $elapsed = round((microtime(true) - $startTime) * 1000);

    // Ambil status code dari header response
    $statusCode = 200;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $h) {
            if (preg_match('/HTTP\/[\d.]+ (\d+)/', $h, $m)) {
                $statusCode = (int) $m[1];
                break;
            }
        }
    }

    if ($raw === false) {
        return [
            'success'          => false,
            'status_code'      => 0,
            'response_time_ms' => $elapsed,
            'error'            => 'Tidak bisa terhubung ke API Integrator (' . $url . ')',
            'data'             => null,
        ];
    }

    $decoded = json_decode($raw, true);
    return [
        'success'          => $statusCode >= 200 && $statusCode < 300,
        'status_code'      => $statusCode,
        'response_time_ms' => $elapsed,
        'error'            => null,
        'data'             => $decoded,
    ];
}

// ──────────────────────────────────────────────────────────────
// Proteksi: hanya user yang login bisa akses
// ──────────────────────────────────────────────────────────────
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized — silakan login terlebih dahulu.']);
    exit;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'health');

// ──────────────────────────────────────────────────────────────
// ROUTING ACTIONS
// ──────────────────────────────────────────────────────────────
switch ($action) {

    // ── [GET] Status / Health API Integrator ─────────────────
    case 'health':
        $result = callIntegrator('/health', 'GET');
        echo json_encode([
            'success'          => $result['success'],
            'status_code'      => $result['status_code'],
            'response_time_ms' => $result['response_time_ms'],
            'integrator_url'   => getIntegratorBaseUrl(),
            'data'             => $result['data'],
            'error'            => $result['error'],
            'timestamp'        => date('c'),
        ]);
        break;

    // ── [GET] Daftar routing table dari API Integrator ───────
    case 'routes':
        $result = callIntegrator('/integrator/daftar_route', 'GET');
        echo json_encode([
            'success' => $result['success'],
            'data'    => $result['data'],
            'error'   => $result['error'],
        ]);
        break;

    // ── [GET] Statistik request dari API Integrator ──────────
    case 'request-stats':
        $result = callIntegrator('/monitor/request-stats', 'GET');
        echo json_encode([
            'success' => $result['success'],
            'data'    => $result['data'],
            'error'   => $result['error'],
        ]);
        break;

    // ── [GET] Status UMKM Insight endpoint di Integrator ─────
    case 'umkm-status':
        $result = callIntegrator('/integrator/umkm-insight/status', 'GET');
        echo json_encode([
            'success' => $result['success'],
            'data'    => $result['data'],
            'error'   => $result['error'],
        ]);
        break;

    // ── [POST] Kirim transaksi UMKM ke API Integrator ────────
    case 'send-transaksi':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method harus POST.']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        // Ambil data user yang sedang login
        $currentUser = getCurrentUser($pdo);
        $userId      = $currentUser['id'] ?? null;
        $amount      = isset($input['amount']) ? (float) $input['amount'] : 0.0;
        $token       = $input['token'] ?? '';
        $keterangan  = $input['keterangan'] ?? 'Transaksi dari UMKM Insight';

        if (!$userId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'User tidak valid.']);
            break;
        }
        if ($amount <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'error' => 'Jumlah transaksi harus lebih dari 0.']);
            break;
        }

        // Payload ke Python FastAPI endpoint UMKM Insight
        $payload = [
            'user_id' => (string) $userId,
            'amount'  => $amount,
            'token'   => $token,
            'source'  => 'umkm-insight-php',
            'metadata' => [
                'keterangan'    => $keterangan,
                'user_id'       => $userId,
                'smartbank_id'  => $currentUser['smartbank_id'] ?? null,
                'nama_bisnis'   => $currentUser['nama_bisnis'] ?? '',
                'timestamp'     => date('c'),
            ],
        ];

        $result = callIntegrator('/integrator/umkm-insight/push-transaksi', 'POST', $payload);

        http_response_code($result['success'] ? 200 : 502);
        echo json_encode([
            'success'          => $result['success'],
            'status_code'      => $result['status_code'],
            'response_time_ms' => $result['response_time_ms'],
            'data'             => $result['data'],
            'error'            => $result['error'],
        ]);
        break;

    // ── [POST] Push data insight ke API Integrator ───────────
    case 'push-data':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method harus POST.']);
            break;
        }

        $input       = json_decode(file_get_contents('php://input'), true) ?: [];
        $currentUser = getCurrentUser($pdo);
        $userId      = $currentUser['id'] ?? null;

        // Ambil ringkasan transaksi dari DB lokal untuk dikirim ke Integrator
        $summaryRows = [];
        if ($userId) {
            $stmt = $pdo->prepare(
                "SELECT type, source, SUM(amount) AS total, COUNT(*) AS count
                 FROM transaction_cache
                 WHERE user_id = ?
                 GROUP BY type, source"
            );
            $stmt->execute([$userId]);
            $summaryRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $payload = [
            'source'      => 'umkm-insight',
            'user_id'     => (string) ($userId ?? 'unknown'),
            'insight_data' => $summaryRows,
            'metadata'    => array_merge($input, [
                'pushed_at' => date('c'),
            ]),
        ];

        $result = callIntegrator('/integrator/umkm-insight/push-transaksi', 'POST', $payload);
        http_response_code($result['success'] ? 200 : 502);
        echo json_encode([
            'success' => $result['success'],
            'data'    => $result['data'],
            'error'   => $result['error'],
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => "Action '{$action}' tidak dikenal.",
            'available_actions' => ['health', 'routes', 'request-stats', 'umkm-status', 'send-transaksi', 'push-data'],
        ]);
}
?>
