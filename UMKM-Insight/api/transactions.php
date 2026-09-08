<?php
require_once __DIR__ . '/_bootstrap.php';

requireApiKeyIfConfigured();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;
    if (!$userId) {
        apiResponse(false, 'Query user_id wajib diisi.', null, 422, [
            'code' => 'VALIDATION_ERROR'
        ]);
    }

    $stmt = $pdo->prepare("
        SELECT id, external_id, product_id, type, source, amount, status, transaction_date, description, synced_at
        FROM transaction_cache
        WHERE user_id = ?
        ORDER BY transaction_date DESC
        LIMIT 100
    ");
    $stmt->execute([$userId]);

    apiResponse(true, 'Daftar transaksi berhasil diambil.', [
        'transactions' => $stmt->fetchAll()
    ]);
}

requireMethod('POST');
$payload = readJsonBody();

$externalId = trim($payload['external_id'] ?? '');
$source = trim($payload['source'] ?? '');
$amount = isset($payload['amount']) ? (float) $payload['amount'] : null;
$type = trim($payload['type'] ?? 'Income');

if ($externalId === '' || $source === '' || $amount === null) {
    apiResponse(false, 'Field external_id, source, dan amount wajib diisi.', null, 422, [
        'code' => 'VALIDATION_ERROR',
        'required' => ['external_id', 'source', 'amount']
    ]);
}

$userId = findUserId($pdo, $payload);

// Auto-create external user jika user_id tidak ditemukan di DB UMKM Insight.
// Ini memungkinkan transaksi dari API Integrator / SmartBank / app lain tetap masuk
// meskipun user belum terdaftar di UMKM Insight.
if (!$userId) {
    $sourceApp = trim($payload['source'] ?? $payload['source_app'] ?? 'external');
    $rawUserId = trim((string)($payload['user_id'] ?? ''));

    // Buat username unik berdasarkan source + user_id dari aplikasi pengirim
    $extUsername = 'ext_' . preg_replace('/[^a-zA-Z0-9_]/', '_', strtolower($sourceApp))
                 . ($rawUserId !== '' ? '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $rawUserId) : '');
    $extUsername = substr($extUsername, 0, 50); // max 50 karakter

    // Cek apakah guest user ini sudah ada
    $stmtCheck = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    $stmtCheck->execute([$extUsername]);
    $existingUser = $stmtCheck->fetch();

    if ($existingUser) {
        $userId = (int) $existingUser['id'];
    } else {
        // Buat guest user baru
        $stmtInsertUser = $pdo->prepare("
            INSERT INTO users (username, password, role, nama_lengkap, email, nama_bisnis, kategori, tier)
            VALUES (?, ?, 'client', ?, ?, ?, 'Eksternal', 'free')
        ");
        $guestName  = 'User Eksternal (' . $sourceApp . ')';
        $guestEmail = $extUsername . '@external.gateway';
        $guestBisnis = 'Terhubung via ' . $sourceApp;
        // Password placeholder (tidak bisa login langsung — hanya untuk data otomatis)
        $guestPwd = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);
        $stmtInsertUser->execute([$extUsername, $guestPwd, $guestName, $guestEmail, $guestBisnis]);
        $userId = (int) $pdo->lastInsertId();
    }
}

$status = trim($payload['status'] ?? 'Success');
$description = trim($payload['description'] ?? ($payload['product_name'] ?? ''));
$transactionDate = normalizeDateTime($payload['transaction_date'] ?? null);
$productId = !empty($payload['product_id']) ? (int) $payload['product_id'] : null;
$sourcePayload = json_encode($payload);

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare("SELECT id FROM transaction_cache WHERE user_id = ? AND external_id = ? AND source = ? LIMIT 1");
    $check->execute([$userId, $externalId, $source]);
    $existing = $check->fetch();

    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE transaction_cache
            SET product_id = ?, type = ?, amount = ?, status = ?, transaction_date = ?, description = ?, source_payload = ?, synced_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$productId, $type, $amount, $status, $transactionDate, $description, $sourcePayload, $existing['id']]);
        $recordId = (int) $existing['id'];
        $action = 'updated';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO transaction_cache
                (external_id, user_id, product_id, type, source, amount, status, transaction_date, description, source_payload)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$externalId, $userId, $productId, $type, $source, $amount, $status, $transactionDate, $description, $sourcePayload]);
        $recordId = (int) $pdo->lastInsertId();
        $action = 'created';
    }

    $pdo->commit();

    apiResponse(true, 'Transaksi berhasil diterima UMKM Insight.', [
        'id' => $recordId,
        'action' => $action,
        'user_id' => $userId
    ], $action === 'created' ? 201 : 200);
} catch (Exception $e) {
    $pdo->rollBack();
    apiResponse(false, 'Gagal menyimpan transaksi.', null, 500, [
        'code' => 'DATABASE_ERROR',
        'details' => $e->getMessage()
    ]);
}
?>
