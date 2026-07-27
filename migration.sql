-- Script Migrasi Database Manajemen Pemesanan
-- Database: db_mpem

CREATE DATABASE IF NOT EXISTS db_mpem;
USE db_mpem;

-- 1. Tabel Penjual
CREATE TABLE IF NOT EXISTS penjual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_toko VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Pelanggan
CREATE TABLE IF NOT EXISTS pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    alamat TEXT NOT NULL,
    no_whatsapp VARCHAR(50) NOT NULL,
    penjual_id INT NOT NULL,
    FOREIGN KEY (penjual_id) REFERENCES penjual(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Pesanan
CREATE TABLE IF NOT EXISTS pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT NOT NULL,
    penjual_id INT NOT NULL,
    deskripsi_pesanan TEXT NOT NULL,
    gambar_sample VARCHAR(255) DEFAULT NULL,
    estimasi_harga DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    ongkos_kirim DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    total_harga DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    waktu_pengambilan DATETIME DEFAULT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Baru',
    status_pembayaran VARCHAR(50) NOT NULL DEFAULT 'Belum Lunas',
    metode_pembayaran VARCHAR(50) DEFAULT NULL,
    jumlah_dp DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    token VARCHAR(64) DEFAULT NULL,
    notif_token VARCHAR(64) DEFAULT NULL,
    gambar_bukti_kirim VARCHAR(255) DEFAULT NULL,
    tanggal_pesan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE,
    FOREIGN KEY (penjual_id) REFERENCES penjual(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Pengaturan
CREATE TABLE IF NOT EXISTS pengaturan (
    penjual_id INT PRIMARY KEY,
    qris_statis TEXT,
    detail_pembayaran TEXT,
    opsi_pembayaran_aktif VARCHAR(255) DEFAULT 'qris,transfer',
    no_whatsapp_penjual VARCHAR(50) DEFAULT NULL,
    FOREIGN KEY (penjual_id) REFERENCES penjual(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ubah otentikasi root agar bisa diakses oleh PHP tanpa password
ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING '';
FLUSH PRIVILEGES;
