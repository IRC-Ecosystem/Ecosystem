<?php
require_once __DIR__ . '/_bootstrap.php';

try {
    $pdo->query('SELECT 1');
    apiResponse(true, 'UMKM Insight API is healthy.', [
        'database' => 'connected'
    ]);
} catch (Exception $e) {
    apiResponse(false, 'Database tidak terhubung.', null, 500, [
        'code' => 'DATABASE_ERROR',
        'details' => $e->getMessage()
    ]);
}
?>
