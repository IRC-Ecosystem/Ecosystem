<?php
// Simulator Endpoint untuk SmartBank
header('Content-Type: application/json');

require_once '../../config/db.php';

$smartbank_id = $_GET['smartbank_id'] ?? null;

if (!$smartbank_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing smartbank_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM smartbank_accounts WHERE smartbank_id = ?");
    $stmt->execute([$smartbank_id]);
    $account = $stmt->fetch();

    if (!$account) {
        echo json_encode(['status' => 'error', 'message' => 'Account not found']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, type, amount, description, transaction_date FROM smartbank_transactions WHERE smartbank_id = ? ORDER BY transaction_date DESC LIMIT 50");
    $stmt->execute([$smartbank_id]);
    $transactions = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'balance' => $account['balance'],
            'history' => $transactions
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
