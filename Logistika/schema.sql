CREATE DATABASE IF NOT EXISTS logistika CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE logistika;

-- PHP app schema (index.php + app/). DB name matches compose DB_NAME=logistika.

CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kurir', 'user', 'operator') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS layanan (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nama_layanan VARCHAR(50) NOT NULL,
    deskripsi VARCHAR(255),
    base_price DECIMAL(10,2) NOT NULL,
    estimasi_waktu VARCHAR(50)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengiriman (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    ext_order_id INT(11) DEFAULT NULL,
    ext_user_id INT(11) DEFAULT NULL,
    resi VARCHAR(50) UNIQUE NOT NULL,
    pengirim_nama VARCHAR(100),
    pengirim_telp VARCHAR(20),
    pengirim_alamat TEXT,
    penerima_nama VARCHAR(100),
    penerima_telp VARCHAR(20),
    penerima_alamat TEXT,
    berat DECIMAL(10,2),
    layanan_id INT(11),
    asuransi DECIMAL(10,2) DEFAULT 0,
    biaya_ongkir DECIMAL(15,2),
    biaya_layanan DECIMAL(15,2),
    tip DECIMAL(15,2) DEFAULT 0,
    is_paid BOOLEAN DEFAULT FALSE,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (layanan_id) REFERENCES layanan(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS riwayat_status (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    pengiriman_id INT(11) NOT NULL,
    status VARCHAR(50) NOT NULL,
    lokasi VARCHAR(100),
    keterangan TEXT,
    waktu_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    pengiriman_id INT(11) NOT NULL,
    bank_ref VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    payment_type VARCHAR(50),
    status VARCHAR(50) DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS penugasan_kurir (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    pengiriman_id INT(11) NOT NULL,
    kurir_id INT(11) NOT NULL,
    status VARCHAR(50) DEFAULT 'assigned',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengiriman_id) REFERENCES pengiriman(id) ON DELETE CASCADE,
    FOREIGN KEY (kurir_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembukuan (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    resi VARCHAR(50) NOT NULL,
    penerima_nama VARCHAR(100) NOT NULL,
    status_barang VARCHAR(50) NOT NULL,
    jenis ENUM('pemasukan', 'pengeluaran') NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    jumlah DECIMAL(15,2) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS api_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    endpoint VARCHAR(255) NOT NULL,
    user_app VARCHAR(100) NULL,
    status VARCHAR(20) NOT NULL,
    payload TEXT NULL,
    error TEXT NULL
) ENGINE=InnoDB;

-- Seed data (setup.php equivalent)
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Admin Logistikita', 'admin@logistikita.com', '$2y$10$ABziksN0lfPSWMWS5tnMY.zXIoARWgfW9.xV1KGu0Mh8O.fwfPuSW', 'admin'),
('Kurir Andalan', 'kurir@logistikita.com', '$2y$10$ABziksN0lfPSWMWS5tnMY.zXIoARWgfW9.xV1KGu0Mh8O.fwfPuSW', 'kurir'),
('User Test', 'user@logistikita.com', '$2y$10$ABziksN0lfPSWMWS5tnMY.zXIoARWgfW9.xV1KGu0Mh8O.fwfPuSW', 'user'),
('Operator Jakarta', 'operator.jkt@logistikita.com', '$2y$10$ABziksN0lfPSWMWS5tnMY.zXIoARWgfW9.xV1KGu0Mh8O.fwfPuSW', 'operator'),
('Operator Surabaya', 'operator.sby@logistikita.com', '$2y$10$ABziksN0lfPSWMWS5tnMY.zXIoARWgfW9.xV1KGu0Mh8O.fwfPuSW', 'operator'),
('Operator Bandung', 'operator.bdg@logistikita.com', '$2y$10$ABziksN0lfPSWMWS5tnMY.zXIoARWgfW9.xV1KGu0Mh8O.fwfPuSW', 'operator');

INSERT IGNORE INTO layanan (nama_layanan, deskripsi, base_price, estimasi_waktu) VALUES
('Logisti-Reguler', 'Pengiriman standar 2-3 hari', 10000, '2-3 Hari'),
('Logisti-Express', 'Pengiriman cepat 1 hari', 15000, '1 Hari'),
('Logisti-Priority', 'Pengiriman sameday', 25000, 'Sameday');
