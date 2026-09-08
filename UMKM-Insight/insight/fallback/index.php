<?php
require_once __DIR__ . '/../../api/_bootstrap.php';

apiResponse(true, 'Fallback UMKM Insight aktif.', [
    'service' => 'umkm-insight',
    'mode' => 'fallback'
]);
?>
