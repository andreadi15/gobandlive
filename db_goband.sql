-- Database: gobandlive
-- Sistem Informasi Pemesanan Jasa Band & Vokalis Panggilan

CREATE DATABASE IF NOT EXISTS gobandlive;
USE gobandlive;

-- Tabel users (untuk semua pengguna: admin, pelanggan, band)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_hp VARCHAR(15) NOT NULL,
    alamat TEXT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pelanggan', 'band') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabel band (detail band/vokalis)
CREATE TABLE band (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nama_band VARCHAR(100) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    tarif DECIMAL(10,2) NOT NULL,
    kontak VARCHAR(15) NOT NULL,
    deskripsi TEXT,
    foto VARCHAR(255),
    status_ketersediaan ENUM('tersedia', 'tidak tersedia') DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel pesanan
CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    id_band INT NOT NULL,
    tanggal_acara DATE NOT NULL,
    lokasi TEXT NOT NULL,
    catatan TEXT,
    status ENUM('menunggu', 'diterima', 'dibatalkan', 'selesai') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_band) REFERENCES band(id) ON DELETE CASCADE
);

-- Tabel pembayaran
CREATE TABLE pembayaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    metode VARCHAR(50) NOT NULL,
    bukti VARCHAR(255),
    status ENUM('belum', 'menunggu', 'verified') DEFAULT 'belum',
    tanggal_bayar DATETIME,
    verified_at DATETIME,
    verified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Tabel jadwal_tampil
CREATE TABLE jadwal_tampil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_band INT NOT NULL,
    id_pesanan INT NOT NULL,
    tanggal DATE NOT NULL,
    lokasi TEXT NOT NULL,
    status ENUM('belum tampil', 'sudah tampil') DEFAULT 'belum tampil',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_band) REFERENCES band(id) ON DELETE CASCADE,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id) ON DELETE CASCADE
);

-- Tabel ulasan
CREATE TABLE ulasan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    id_user INT NOT NULL,
    id_band INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    komentar TEXT,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (id_band) REFERENCES band(id) ON DELETE CASCADE
);

-- Tabel laporan (untuk admin)
CREATE TABLE laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    total_transaksi INT DEFAULT 0,
    total_pendapatan DECIMAL(15,2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert data admin default
-- Password: admin123 (sudah di-hash dengan password_hash)
INSERT INTO users (nama, email, no_hp, username, password, role) VALUES 
('Administrator', 'admin@gobandlive.com', '081234567890', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert data band contoh
INSERT INTO users (nama, email, no_hp, username, password, role) VALUES 
('Rizky Febian', 'rizky@band.com', '082134567890', 'rizkyband', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'band');

INSERT INTO band (user_id, nama_band, genre, tarif, kontak, deskripsi) VALUES 
(2, 'Rizky & The Bands', 'Pop Rock', 5000000.00, '082134567890', 'Band profesional dengan pengalaman 5 tahun di industri musik');

-- Insert data pelanggan contoh
INSERT INTO users (nama, email, no_hp, username, password, role) VALUES 
('John Doe', 'john@email.com', '083134567890', 'johndoe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pelanggan');

-- CATATAN PENTING:
-- Password default untuk semua akun contoh: admin123
-- Pastikan untuk mengganti password setelah instalasi!