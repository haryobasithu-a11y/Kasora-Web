<?php
/**
 * Kasora - Konfigurasi & koneksi database (PDO)
 * Membaca kredensial dari variabel lingkungan Railway MySQL.
 * Fallback ke nilai lokal jika variabel tidak tersedia.
 */

const DB_HOST = '127.0.0.1';
const DB_NAME = 'kasora';
const DB_USER = 'root';
const DB_PASS = '';

function db_env(string $key, string $fallback): string
{
    $value = getenv($key);
    return ($value !== false && $value !== '') ? $value : $fallback;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = db_env('MYSQLHOST', '127.0.0.1');
        $port = db_env('MYSQLPORT', '3306');
        $name = db_env('MYSQLDATABASE', DB_NAME);
        $user = db_env('MYSQLUSER', DB_USER);
        $pass = db_env('MYSQLPASSWORD', DB_PASS);

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal. Periksa variabel MYSQL* di Railway dan pastikan database sudah di-import.');
        }
    }

    return $pdo;
}

