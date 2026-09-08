<?php
require_once __DIR__ . '/_bootstrap.php';

requireApiKeyIfConfigured();
requireMethod('POST');

$payload = readJsonBody();
$items = $payload['items'] ?? [$payload];
if (!is_array($items) || count($items) === 0) {
    apiResponse(false, 'Field items wajib berupa array tren produk.', null, 422, [
        'code' => 'VALIDATION_ERROR'
    ]);
}

try {
    $pdo->beginTransaction();
    $saved = 0;

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
        $saved++;
    }

    $pdo->commit();
    apiResponse(true, 'Tren pasar berhasil diterima.', ['saved' => $saved], 201);
} catch (Exception $e) {
    $pdo->rollBack();
    apiResponse(false, 'Gagal menyimpan tren pasar.', null, 500, [
        'code' => 'DATABASE_ERROR',
        'details' => $e->getMessage()
    ]);
}
?>
