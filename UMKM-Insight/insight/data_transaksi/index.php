<?php
require_once __DIR__ . '/../../api/_bootstrap.php';

requireApiKeyIfConfigured();

$payload = $_SERVER['REQUEST_METHOD'] === 'POST' ? readJsonBody() : $_GET;
$userId = findUserId($pdo, $payload);

if (!$userId) {
    apiResponse(false, 'User tidak ditemukan untuk permintaan insight.', null, 404, [
        'code' => 'USER_NOT_FOUND'
    ]);
}

$stmt = $pdo->prepare("
    SELECT id, external_id, type, source, amount, status, transaction_date, description
    FROM transaction_cache
    WHERE user_id = ?
    ORDER BY transaction_date DESC
    LIMIT 100
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

$summaryStmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_transactions,
        COALESCE(SUM(CASE WHEN type = 'Income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'Expense' THEN amount ELSE 0 END), 0) AS total_expense
    FROM transaction_cache
    WHERE user_id = ?
");
$summaryStmt->execute([$userId]);
$summary = $summaryStmt->fetch();

apiResponse(true, 'Data transaksi UMKM berhasil dikirim.', [
    'user_id' => $userId,
    'summary' => $summary,
    'transactions' => $transactions
]);
?>
