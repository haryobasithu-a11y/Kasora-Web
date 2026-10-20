-- =====================================================
-- Kasora - Skema Database MySQL
-- Cara pakai: import file ini lewat phpMyAdmin
-- atau jalankan: mysql -u root -p < database.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS kasora
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kasora;

CREATE TABLE IF NOT EXISTS pengguna (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  nama          VARCHAR(100) NOT NULL,
  username      VARCHAR(40)  NOT NULL UNIQUE,
  email         VARCHAR(254) NULL UNIQUE,
  google_id     VARCHAR(255) NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaksi (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pengguna_id INT UNSIGNED NULL,
  jenis       ENUM('masuk', 'keluar') NOT NULL,
  kategori    VARCHAR(50)    NOT NULL,
  nominal     DECIMAL(15,2)  NOT NULL,
  tanggal     DATE           NOT NULL,
  keterangan  VARCHAR(255)   NULL,
  created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pengguna_tanggal (pengguna_id, tanggal),
  INDEX idx_tanggal (tanggal),
  INDEX idx_jenis (jenis),
  INDEX idx_kategori (kategori),
  CONSTRAINT fk_transaksi_pengguna FOREIGN KEY (pengguna_id)
    REFERENCES pengguna(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;