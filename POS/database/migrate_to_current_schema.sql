CREATE DATABASE IF NOT EXISTS `warungpos`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `warungpos`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) DEFAULT NULL,
  `nama` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `password` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('manager', 'operator', 'kasir', 'konsumen') NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @users_add_nama = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'nama'
    ),
    'SELECT 1',
    'ALTER TABLE `users` ADD COLUMN `nama` VARCHAR(100) NULL AFTER `name`'
  )
);
PREPARE stmt FROM @users_add_nama;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `api_integrations` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `provider` ENUM('smartbank', 'api-gateway', 'umkm-insight') NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `method` ENUM('GET', 'POST', 'PUT', 'DELETE') NOT NULL DEFAULT 'GET',
  `base_url` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `headers_json` JSON NULL,
  `query_json` JSON NULL,
  `body_json` JSON NULL,
  `expected_status` INT NOT NULL DEFAULT 200,
  `description` TEXT NULL,
  `status` ENUM('untested', 'ok', 'failed') NOT NULL DEFAULT 'untested',
  `last_checked_at` DATETIME NULL,
  `last_response_code` INT NULL,
  `last_response_body` MEDIUMTEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_integrations_provider` (`provider`),
  KEY `idx_api_integrations_status` (`status`),
  KEY `idx_api_integrations_active` (`provider`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @users_add_phone = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = 'phone'
    ),
    'SELECT 1',
    'ALTER TABLE `users` ADD COLUMN `phone` VARCHAR(30) NULL AFTER `email`'
  )
);
PREPARE stmt FROM @users_add_phone;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `users`
SET `nama` = COALESCE(`nama`, `name`)
WHERE `nama` IS NULL OR `nama` = '';

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nama_produk` VARCHAR(150) NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `kategori` VARCHAR(100) NOT NULL,
  `gambar` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @products_add_on_hand = (
  SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'on_hand'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `on_hand` INT NOT NULL DEFAULT 0 AFTER `stock`')
);
PREPARE stmt FROM @products_add_on_hand; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @products_add_reserved = (
  SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'reserved'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `reserved` INT NOT NULL DEFAULT 0 AFTER `on_hand`')
);
PREPARE stmt FROM @products_add_reserved; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @products_add_available = (
  SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'available'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `available` INT NOT NULL DEFAULT 0 AFTER `reserved`')
);
PREPARE stmt FROM @products_add_available; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @products_add_inventory_initialized = (
  SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'inventory_initialized'), 'SELECT 1', 'ALTER TABLE `products` ADD COLUMN `inventory_initialized` TINYINT(1) NOT NULL DEFAULT 0 AFTER `available`')
);
PREPARE stmt FROM @products_add_inventory_initialized; EXECUTE stmt; DEALLOCATE PREPARE stmt;
UPDATE `products`
SET `on_hand` = `stock`, `reserved` = 0, `available` = `stock`, `inventory_initialized` = 1
WHERE `inventory_initialized` = 0;

CREATE TABLE IF NOT EXISTS `inventory_reservations` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `transaction_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL,
  `status` ENUM('active', 'released', 'consumed', 'expired') NOT NULL DEFAULT 'active',
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `released_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_reservation_item` (`transaction_id`, `product_id`),
  KEY `idx_inventory_reservations_expiry` (`status`, `expires_at`),
  CONSTRAINT `fk_inventory_reservations_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `product_id` INT NOT NULL,
  `transaction_id` INT NULL,
  `movement_type` ENUM('reserved', 'released', 'consumed', 'expired', 'adjustment') NOT NULL,
  `on_hand_delta` INT NOT NULL DEFAULT 0,
  `reserved_delta` INT NOT NULL DEFAULT 0,
  `note` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_movements_product` (`product_id`, `created_at`),
  KEY `idx_inventory_movements_transaction` (`transaction_id`),
  CONSTRAINT `fk_inventory_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `invoice` VARCHAR(50) NOT NULL,
  `user_id` INT NULL,
  `cashier_id` INT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'pending_payment', 'approved', 'paid', 'rejected', 'voided', 'refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `stock_deducted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice` (`invoice`),
  KEY `fk_transactions_user` (`user_id`),
  KEY `fk_transactions_cashier` (`cashier_id`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_transactions_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `transactions`
  MODIFY COLUMN `user_id` INT NULL;

ALTER TABLE `transactions`
  MODIFY COLUMN `status` ENUM('pending', 'pending_payment', 'approved', 'paid', 'rejected', 'voided', 'refunded') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL,
  `user_role` VARCHAR(50) NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(100) NOT NULL,
  `entity_id` VARCHAR(100) NULL,
  `before_data` JSON NULL,
  `after_data` JSON NULL,
  `reason` TEXT NULL,
  `ip_address` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_action` (`action`, `created_at`),
  KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_logs_user` (`user_id`, `created_at`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @transactions_add_stock_deducted = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'transactions'
        AND COLUMN_NAME = 'stock_deducted'
    ),
    'SELECT 1',
    'ALTER TABLE `transactions` ADD COLUMN `stock_deducted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_method`'
  )
);
PREPARE stmt FROM @transactions_add_stock_deducted;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `transaction_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` INT NOT NULL DEFAULT 1,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `fk_transaction_items_transaction` (`transaction_id`),
  KEY `fk_transaction_items_product` (`product_id`),
  CONSTRAINT `fk_transaction_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `transaction_id` INT NOT NULL,
  `provider` ENUM('local', 'smartbank') NOT NULL DEFAULT 'local',
  `method` VARCHAR(50) NOT NULL,
  `status` ENUM('processing', 'pending', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_request_id` VARCHAR(100) DEFAULT NULL,
  `provider_reference` VARCHAR(150) DEFAULT NULL,
  `idempotency_key` VARCHAR(191) DEFAULT NULL,
  `request_fingerprint` CHAR(64) DEFAULT NULL,
  `provider_reference_key` VARCHAR(191) DEFAULT NULL,
  `response_code` INT DEFAULT NULL,
  `response_body` MEDIUMTEXT NULL,
  `cashier_id` INT NULL,
  `paid_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_idempotency` (`idempotency_key`),
  UNIQUE KEY `uq_payments_provider_reference` (`provider`, `provider_reference_key`),
  KEY `idx_payments_transaction` (`transaction_id`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_method` (`method`),
  KEY `idx_payments_cashier` (`cashier_id`),
  CONSTRAINT `fk_payments_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @payments_add_idempotency_key = (
  SELECT IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'idempotency_key'), 'SELECT 1', 'ALTER TABLE `payments` ADD COLUMN `idempotency_key` VARCHAR(191) NULL AFTER `provider_reference`')
);
PREPARE stmt FROM @payments_add_idempotency_key;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @payments_add_request_fingerprint = (
  SELECT IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'request_fingerprint'), 'SELECT 1', 'ALTER TABLE `payments` ADD COLUMN `request_fingerprint` CHAR(64) NULL AFTER `idempotency_key`')
);
PREPARE stmt FROM @payments_add_request_fingerprint;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @payments_add_provider_reference_key = (
  SELECT IF(EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'provider_reference_key'), 'SELECT 1', 'ALTER TABLE `payments` ADD COLUMN `provider_reference_key` VARCHAR(191) NULL AFTER `request_fingerprint`')
);
PREPARE stmt FROM @payments_add_provider_reference_key;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @payments_add_unique_idempotency = (
  SELECT IF(EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'uq_payments_idempotency'), 'SELECT 1', 'ALTER TABLE `payments` ADD UNIQUE KEY `uq_payments_idempotency` (`idempotency_key`)')
);
PREPARE stmt FROM @payments_add_unique_idempotency;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @payments_add_unique_provider_reference = (
  SELECT IF(EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'uq_payments_provider_reference'), 'SELECT 1', 'ALTER TABLE `payments` ADD UNIQUE KEY `uq_payments_provider_reference` (`provider`, `provider_reference_key`)')
);
PREPARE stmt FROM @payments_add_unique_provider_reference;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `payments`
  MODIFY COLUMN `status` ENUM('processing', 'pending', 'success', 'failed', 'refunded') NOT NULL DEFAULT 'pending';

CREATE TABLE IF NOT EXISTS `outbox_events` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `aggregate_type` VARCHAR(50) NOT NULL,
  `aggregate_id` BIGINT NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` JSON NOT NULL,
  `idempotency_key` VARCHAR(191) NOT NULL,
  `status` ENUM('pending', 'processing', 'published', 'dead_letter') NOT NULL DEFAULT 'pending',
  `attempts` INT NOT NULL DEFAULT 0,
  `max_attempts` INT NOT NULL DEFAULT 8,
  `next_attempt_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `locked_at` DATETIME NULL,
  `last_error` TEXT NULL,
  `published_at` DATETIME NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_outbox_idempotency` (`idempotency_key`),
  KEY `idx_outbox_claim` (`status`, `next_attempt_at`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `refunds` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `payment_id` INT NOT NULL,
  `idempotency_key` VARCHAR(191) NOT NULL,
  `reason_code` VARCHAR(128) NOT NULL,
  `status` ENUM('processing', 'success', 'failed') NOT NULL DEFAULT 'processing',
  `provider_reference` VARCHAR(191) NULL,
  `response_body` MEDIUMTEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_refunds_idempotency` (`idempotency_key`),
  UNIQUE KEY `uq_refunds_provider_reference` (`provider_reference`),
  CONSTRAINT `fk_refunds_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_reconciliation` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `payment_id` INT NULL,
  `invoice` VARCHAR(50) NOT NULL,
  `provider_reference` VARCHAR(191) NULL,
  `provider_status` VARCHAR(50) NULL,
  `local_status` VARCHAR(50) NULL,
  `status` ENUM('matched', 'mismatch', 'unresolved') NOT NULL DEFAULT 'unresolved',
  `details` JSON NULL,
  `checked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reconciliation_invoice` (`invoice`, `checked_at`),
  KEY `idx_reconciliation_status` (`status`, `checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @transaction_items_add_price = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'transaction_items'
        AND COLUMN_NAME = 'price'
    ),
    'SELECT 1',
    'ALTER TABLE `transaction_items` ADD COLUMN `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `qty`'
  )
);
PREPARE stmt FROM @transaction_items_add_price;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @transaction_items_add_subtotal = (
  SELECT IF(
    EXISTS (
      SELECT 1
      FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'transaction_items'
        AND COLUMN_NAME = 'subtotal'
    ),
    'SELECT 1',
    'ALTER TABLE `transaction_items` ADD COLUMN `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `price`'
  )
);
PREPARE stmt FROM @transaction_items_add_subtotal;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
