<?php
/**
 * Kasora - Konfigurasi & koneksi database (PDO)
 * Sesuaikan nilai di bawah dengan server MySQL Anda.
 */

const DB_HOST = '127.0.0.1';
const DB_NAME = 'kasora';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal. Periksa config/database.php dan pastikan database "kasora" sudah di-import.');
        }
    }

    return $pdo;
}