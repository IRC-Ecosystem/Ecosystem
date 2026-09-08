<?php
require_once __DIR__ . '/../app/init.php';
$worker = new Logistics_model();
while (true) {
    try { $worker->dispatchPending(50); }
    catch (Throwable $error) { error_log(json_encode(['event'=>'marketplace_worker_failed','error'=>$error->getMessage()])); }
    sleep(10);
}
