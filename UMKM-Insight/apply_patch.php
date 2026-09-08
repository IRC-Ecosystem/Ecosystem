<?php
/**
 * Run Database Patch 2 & Additional Columns
 */
require_once 'config/db.php';

try {
    // 1. Run database_patch_2.sql
    $sql = file_get_contents('dokumentasi/database_patch_2.sql');
    if ($sql) {
        $pdo->exec($sql);
        echo "SUCCESS: database_patch_2.sql applied.\n";
    }

    // 2. Add description column to transaction_cache
    // check if column exists first
    $stmt = $pdo->prepare("SHOW COLUMNS FROM transaction_cache LIKE 'description'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE transaction_cache ADD COLUMN description VARCHAR(255) DEFAULT NULL");
        echo "SUCCESS: Added description to transaction_cache.\n";
    } else {
        echo "INFO: description already exists in transaction_cache.\n";
    }

    // 3. Add warungpos_id and foto_profil to users
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'warungpos_id'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN warungpos_id VARCHAR(50) DEFAULT NULL, ADD COLUMN foto_profil VARCHAR(255) DEFAULT NULL");
        echo "SUCCESS: Added warungpos_id and foto_profil to users.\n";
    } else {
        echo "INFO: warungpos_id already exists in users.\n";
    }

    // Add smartbank_id if not exists (it should exist in database.sql but just in case)
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'smartbank_id'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN smartbank_id VARCHAR(50) DEFAULT NULL");
    }

    echo "DONE: All patches applied successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: Failed to apply patch: " . $e->getMessage() . "\n";
}
?>
