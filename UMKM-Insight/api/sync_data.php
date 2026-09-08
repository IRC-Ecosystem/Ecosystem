<?php
error_reporting(E_ERROR);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

function jsonResult($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'status' => $success ? 'success' : 'error',
        'message' => $message,
        'data' => $data,
        'error' => $success ? null : ['code' => 'SYNC_ERROR'],
        'meta' => [
            'service' => 'umkm-insight',
            'timestamp' => gmdate('c')
        ]
    ]);
    exit;
}

function fetchJson($url, $apiKey = '') {
    $headers = "Accept: application/json\r\n";
    if ($apiKey !== '') {
        $headers .= "X-API-Key: {$apiKey}\r\n";
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headers,
            'timeout' => 8,
            'ignore_errors' => true
        ]
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        throw new Exception("Tidak bisa menghubungi {$url}");
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Response dari {$url} bukan JSON valid");
    }

    return $decoded;
}

function normalizeItems($response, $keys) {
    foreach ($keys as $key) {
        if (isset($response[$key]) && is_array($response[$key])) return $response[$key];
        if (isset($response['data'][$key]) && is_array($response['data'][$key])) return $response['data'][$key];
    }
    if (isset($response['data']) && is_array($response['data'])) return $response['data'];
    return is_array($response) ? $response : [];
}

function saveTransaction(PDO $pdo, $userId, array $item, $defaultSource) {
    $externalId = $item['external_id'] ?? $item['id'] ?? null;
    if (!$externalId) return false;

    $source = $item['source'] ?? $defaultSource;
    $type = $item['type'] ?? 'Income';
    $amount = (float) ($item['amount'] ?? 0);
    $status = $item['status'] ?? 'Success';
    $transactionDate = !empty($item['transaction_date']) ? date('Y-m-d H:i:s', strtotime($item['transaction_date'])) : date('Y-m-d H:i:s');
    $description = $item['description'] ?? $item['product_name'] ?? '';
    $payload = json_encode($item);

    $check = $pdo->prepare("SELECT id FROM transaction_cache WHERE user_id = ? AND external_id = ? AND source = ? LIMIT 1");
    $check->execute([$userId, $externalId, $source]);
    $existing = $check->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE transaction_cache
            SET type = ?, amount = ?, status = ?, transaction_date = ?, description = ?, source_payload = ?, synced_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$type, $amount, $status, $transactionDate, $description, $payload, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO transaction_cache
                (external_id, user_id, type, source, amount, status, transaction_date, description, source_payload)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$externalId, $userId, $type, $source, $amount, $status, $transactionDate, $description, $payload]);
    }

    return true;
}

if (!isLoggedIn() || $_SESSION['role'] !== 'client') {
    jsonResult(false, 'Unauthorized', null, 401);
}

$user = getCurrentUser($pdo);
$userId = (int) $user['id'];
$smartbankId = $user['smartbank_id'] ?? null;
$warungposId = $user['warungpos_id'] ?? null;

$smartbankUrl = getenv('SMARTBANK_TRANSACTIONS_URL') ?: '';
$warungposUrl = getenv('WARUNGPOS_TRANSACTIONS_URL') ?: '';
$pasarkitaUrl = getenv('PASARKITA_TRENDS_URL') ?: '';
$externalApiKey = getenv('EXTERNAL_API_KEY') ?: '';

if ($smartbankUrl === '' && $warungposUrl === '' && $pasarkitaUrl === '') {
    jsonResult(true, 'Belum ada endpoint pull eksternal yang dikonfigurasi. UMKM Insight siap menerima push API di POST /api/transactions.php dan POST /api/market_trends.php.', [
        'synced' => 0,
        'mode' => 'push-ready'
    ]);
}

try {
    $pdo->beginTransaction();
    $synced = 0;
    $sources = [];

    if ($smartbankUrl !== '' && $smartbankId) {
        $separator = str_contains($smartbankUrl, '?') ? '&' : '?';
        $response = fetchJson($smartbankUrl . $separator . 'smartbank_id=' . urlencode($smartbankId), $externalApiKey);
        $items = normalizeItems($response, ['transactions', 'items']);
        foreach ($items as $item) {
            if (saveTransaction($pdo, $userId, $item, 'SmartBank')) $synced++;
        }
        $sources[] = 'SmartBank';
    }

    if ($warungposUrl !== '' && $warungposId) {
        $separator = str_contains($warungposUrl, '?') ? '&' : '?';
        $response = fetchJson($warungposUrl . $separator . 'warungpos_id=' . urlencode($warungposId), $externalApiKey);
        $items = normalizeItems($response, ['transactions', 'sales', 'items']);
        foreach ($items as $item) {
            if (saveTransaction($pdo, $userId, $item, 'WarungPOS')) $synced++;
        }
        $sources[] = 'WarungPOS';
    }

    if ($pasarkitaUrl !== '') {
        $response = fetchJson($pasarkitaUrl, $externalApiKey);
        $items = normalizeItems($response, ['trends', 'items']);
        foreach ($items as $item) {
            if (empty($item['product_name'])) continue;
            $stmt = $pdo->prepare("
                INSERT INTO market_trends_cache
                    (product_name, category, total_sold_global, avg_price, trend_direction)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $item['product_name'],
                $item['category'] ?? 'Umum',
                (int) ($item['total_sold_global'] ?? $item['total_sold'] ?? 0),
                (float) ($item['avg_price'] ?? 0),
                $item['trend_direction'] ?? 'stable'
            ]);
            $synced++;
        }
        $sources[] = 'PasarKita';
    }

    $pdo->commit();

    jsonResult(true, 'Sinkronisasi eksternal selesai.', [
        'synced' => $synced,
        'sources' => $sources
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResult(false, 'Sync failed: ' . $e->getMessage(), null, 500);
}
?>
