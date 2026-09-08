<?php
// Simulator Endpoint untuk WarungPOS
header('Content-Type: application/json');

require_once '../../config/db.php';

$warungpos_id = $_GET['warungpos_id'] ?? null;

if (!$warungpos_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing warungpos_id']);
    exit;
}

try {
    // Ambil data dari tabel simulasi external_sales khusus untuk POS
    $stmt = $pdo->prepare("SELECT id, product_name, amount, transaction_date FROM external_sales WHERE warungpos_id = ? AND source = 'POS' ORDER BY transaction_date DESC");
    $stmt->execute([$warungpos_id]);
    $sales = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'source_app' => 'WarungPOS',
        'data' => $sales
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
