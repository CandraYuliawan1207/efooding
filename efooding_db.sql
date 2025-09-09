CREATE DATABASE efooding_db;

USE efooding_db;

-- Tabel untuk users (username, password, department, role)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user'
);

-- Tabel untuk admin (login untuk admin)
CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Tabel untuk pengajuan fooding
CREATE TABLE fooding_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    jumlah INT NOT NULL,
    status ENUM('Menunggu', 'Diperiksa', 'Disetujui') DEFAULT 'Menunggu',
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel untuk stok fooding
CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    unit VARCHAR(50) NOT NULL
);

-- Tabel untuk notifikasi
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    status ENUM('read', 'unread') DEFAULT 'unread',
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Populasi awal data stok
INSERT INTO stock (item_name, quantity, unit) VALUES
('Indomie', 100, 'pcs'),
('Kopi', 100, 'pcs');
