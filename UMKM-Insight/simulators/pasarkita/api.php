<?php
// Simulator Endpoint untuk PasarKita (Marketplace) - DATA GLOBAL
// Endpoint ini mengembalikan tren produk yang laku di seluruh platform PasarKita.
// Tidak ada filter per user karena ini data publik marketplace.
header('Content-Type: application/json');

require_once '../../config/db.php';

try {
    // Agregasi: produk apa saja yang paling laku di PasarKita secara global
    $stmt = $pdo->prepare("
        SELECT 
            product_name, 
            COUNT(*) as total_sold,
            AVG(amount) as avg_price,
            SUM(amount) as total_revenue,
            MAX(transaction_date) as last_sold
        FROM external_sales 
        WHERE smartbank_id = 'GLOBAL' AND source = 'Marketplace' AND platform_name = 'PasarKita'
        GROUP BY product_name
        ORDER BY total_sold DESC
        LIMIT 10
    ");
    $stmt->execute();
    $trending = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'source_app' => 'PasarKita',
        'description' => 'Global trending products across PasarKita marketplace',
        'data' => $trending
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
