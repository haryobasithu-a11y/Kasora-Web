<?php
/**
 * Kasora - Konfigurasi & koneksi database (PDO)
 * Membaca variabel MySQL Railway; fallback ke nilai lokal jika tidak ada.
 */

const DB_NAME = 'kasora';

function db_env(string $key, string $default): string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = db_env('MYSQLHOST', '127.0.0.1');
        $port = db_env('MYSQLPORT', '3306');
        $name = db_env('MYSQLDATABASE', DB_NAME);
        $user = db_env('MYSQLUSER', 'root');
        $pass = db_env('MYSQLPASSWORD', '');

        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            exit('Koneksi database gagal. Periksa DataBase/config/database.php dan pastikan database "' . $name . '" sudah di-import.');
        }
    }

    return $pdo;
}

