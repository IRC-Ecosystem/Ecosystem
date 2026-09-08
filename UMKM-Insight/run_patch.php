<?php
/**
 * Run Database Patch
 * Helper script to automatically apply the SQL patch for audit_logs
 */
require_once 'config/db.php';

try {
    $sql = file_get_contents('dokumentasi/database_patch.sql');
    $pdo->exec($sql);
    echo "SUCCESS: Database patch applied successfully!\n";
    // Self destruct to keep directory clean
    unlink(__FILE__);
} catch (Exception $e) {
    echo "ERROR: Failed to apply database patch: " . $e->getMessage() . "\n";
}
?>
