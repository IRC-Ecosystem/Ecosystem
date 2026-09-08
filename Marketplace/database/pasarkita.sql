CREATE DATABASE IF NOT EXISTS pasarkita CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pasarkita;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS seller_payouts;
DROP TABLE IF EXISTS chats;
DROP TABLE IF EXISTS vouchers;
DROP TABLE IF EXISTS ledgers;
DROP TABLE IF EXISTS payment_requests;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS stores;
DROP TABLE IF EXISTS wallets;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user', 'seller') NOT NULL DEFAULT 'user',
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    address TEXT NULL,
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    balance DECIMAL(14,2) NOT NULL DEFAULT 50000,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE stores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL UNIQUE,
    name VARCHAR(140) NOT NULL,
    description TEXT NULL,
    address TEXT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    smartbank_external_id VARCHAR(191) NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_store_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_stores_owner (owner_id, status)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(14,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    INDEX idx_products_store (store_id, status),
    INDEX idx_products_cat (category)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    store_id INT NULL,
    seller_external_id VARCHAR(191) NULL,
    order_code VARCHAR(40) NOT NULL UNIQUE,
    shipping_address TEXT NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL,
    voucher_discount DECIMAL(14,2) NOT NULL DEFAULT 0,
    marketplace_fee DECIMAL(14,2) NOT NULL,
    gateway_fee DECIMAL(14,2) NOT NULL,
    bank_fee DECIMAL(14,2) NOT NULL,
    tax DECIMAL(14,2) NOT NULL,
    shipping_fee DECIMAL(14,2) NOT NULL,
    total DECIMAL(14,2) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    order_status ENUM('processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'processing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_store FOREIGN KEY (store_id) REFERENCES stores(id),
    INDEX idx_orders_user (user_id, payment_status, order_status)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    store_id INT NOT NULL,
    product_name VARCHAR(160) NOT NULL,
    price DECIMAL(14,2) NOT NULL,
    qty INT NOT NULL,
    subtotal DECIMAL(14,2) NOT NULL,
    item_status ENUM('processing', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'processing',
    CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_item_store FOREIGN KEY (store_id) REFERENCES stores(id),
    INDEX idx_order_items_store (store_id),
    INDEX idx_order_items_order (order_id)
);

CREATE TABLE payment_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    from_app VARCHAR(80) NOT NULL,
    user_id INT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE ledgers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_id INT NULL,
    type ENUM('debit', 'credit', 'fee', 'tax') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ledger_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ledger_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
);
CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'fixed',
    discount_value DECIMAL(14,2) NOT NULL,
    min_purchase DECIMAL(14,2) NOT NULL DEFAULT 0,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    store_id INT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_chat_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_chat_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE seller_payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_id INT NOT NULL,
    order_id INT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    marketplace_fee DECIMAL(14,2) NOT NULL,
    net_amount DECIMAL(14,2) NOT NULL,
    status ENUM('pending', 'released') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payout_store FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
    CONSTRAINT fk_payout_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

INSERT INTO users (id, name, email, password, role, status, address, phone) VALUES
(1, 'Admin PasarKita', 'admin@pasarkita.test', '$2y$10$KSOvldSZu5cg3MNVKRH/OOnJsCu8JCklKY4F/LF44S4AAhxNa0Qey', 'admin', 'active', 'Kantor PasarKita', '0800000000'),
(2, 'Sari UMKM', 'seller@pasarkita.test', '$2y$10$KSOvldSZu5cg3MNVKRH/OOnJsCu8JCklKY4F/LF44S4AAhxNa0Qey', 'seller', 'active', 'Bandung', '0812345678'),
(3, 'Budi Pembeli', 'user@pasarkita.test', '$2y$10$KSOvldSZu5cg3MNVKRH/OOnJsCu8JCklKY4F/LF44S4AAhxNa0Qey', 'user', 'active', 'Cimahi', '0899999999');

INSERT INTO wallets (user_id, balance) VALUES
(1, 1000000000),
(2, 50000),
(3, 250000);

INSERT INTO stores (id, owner_id, name, description, address, status) VALUES
(1, 2, 'Dapur Sari', 'Produk makanan rumahan dan camilan UMKM.', 'Bandung', 'active');

INSERT INTO products (store_id, name, category, description, price, stock, image_url, status) VALUES
(1, 'Keripik Singkong Pedas', 'Makanan', 'Keripik renyah dengan bumbu pedas.', 12000, 40, 'https://images.unsplash.com/photo-1621939514649-280e2ee25f60?auto=format&fit=crop&w=900&q=80', 'active'),
(1, 'Kopi Arabika Lokal', 'Minuman', 'Kopi arabika roast medium dari petani lokal.', 35000, 25, 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?auto=format&fit=crop&w=900&q=80', 'active'),
(1, 'Sambal Bawang Botol', 'Makanan', 'Sambal bawang siap makan ukuran 180 gram.', 18000, 30, 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=900&q=80', 'active'),
(1, 'Tas Anyaman Mini', 'Kerajinan', 'Tas anyaman handmade untuk aktivitas harian.', 55000, 12, 'https://images.unsplash.com/photo-1590739225285-6330123b172c?auto=format&fit=crop&w=900&q=80', 'active');

INSERT INTO vouchers (code, label, discount_type, discount_value, min_purchase) VALUES
('UMKM10', 'Diskon 10% Produk UMKM', 'percentage', 10, 20000),
('ONGKIR5K', 'Subsidi Logistik Rp5.000', 'fixed', 5000, 10000);
