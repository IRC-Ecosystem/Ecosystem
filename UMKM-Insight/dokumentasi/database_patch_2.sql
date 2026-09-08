-- Database Patch 2: API Simulation Tables & Subscriptions

USE `umkm_insight`;

-- 1. SmartBank Accounts
CREATE TABLE IF NOT EXISTS `smartbank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `smartbank_id` varchar(50) NOT NULL UNIQUE,
  `owner_name` varchar(100) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. SmartBank Transactions
CREATE TABLE IF NOT EXISTS `smartbank_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `smartbank_id` varchar(50) NOT NULL,
  `type` enum('Income','Expense') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`smartbank_id`) REFERENCES `smartbank_accounts`(`smartbank_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. POS & Marketplace Sales (Unified Shadow Table for simplicity)
CREATE TABLE IF NOT EXISTS `external_sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `external_id` varchar(100) DEFAULT NULL,
  `smartbank_id` varchar(50) DEFAULT NULL,
  `warungpos_id` varchar(50) DEFAULT NULL,
  `source` enum('POS','Marketplace') NOT NULL,
  `platform_name` varchar(50) DEFAULT NULL,
  `product_name` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `source_payload` json DEFAULT NULL,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_external_sales_source` (`source`,`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Subscription Payments (For Langganan feature)
CREATE TABLE IF NOT EXISTS `subscription_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `proof_image` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Market Trends Cache (Data tren global PasarKita yang di-cache oleh UMKM Insight)
CREATE TABLE IF NOT EXISTS `market_trends_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `total_sold_global` int(11) NOT NULL DEFAULT 0,
  `avg_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `trend_direction` enum('up','down','stable') NOT NULL DEFAULT 'stable',
  `synced_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tidak ada dummy data pada patch integrasi.
-- Tabel di atas hanya disiapkan sebagai cache/compatibility layer jika service eksternal mengirim data.
