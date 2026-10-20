<?php
/**
 * Kasora - Fungsi bantu (helper) yang dipakai di semua halaman.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Jakarta');

/** Daftar kategori transaksi. Tambah/ubah sesuai kebutuhan organisasi. */
const KATEGORI = [
    'Konsumsi',
    'Operasional',
    'Transportasi',
    'Perlengkapan',
    'Gaji & Honor',
    'Donasi',
    'Lainnya',
];

/** Escape output HTML (cegah XSS). */
function e($teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

/** Format angka ke Rupiah: 1500000 -> Rp 1.500.000 */
function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

/** Format tanggal Y-m-d -> 5 Okt 2026 */
function tanggal_indo(string $tanggal): string
{
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($tanggal);
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t)] . ' ' . date('Y', $t);
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function require_login(): void
{
    if (empty($_SESSION['pengguna_id'])) {
        redirect('login.php');
    }

    ensure_transaction_schema();
}

function ensure_transaction_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();
    $columns = $pdo->query(
        'SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'transaksi\''
    )->fetchAll(PDO::FETCH_KEY_PAIR);

    $indexes = $pdo->query(
        'SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'transaksi\''
    )->fetchAll(PDO::FETCH_COLUMN);
    $foreignKey = $pdo->prepare(
        'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
           AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = ?'
    );
    $foreignKey->execute(['transaksi', 'pengguna_id', 'pengguna']);
    if (isset($columns['pengguna_id'])
        && $columns['pengguna_id'] === 'NO'
        && in_array('idx_pengguna_tanggal', $indexes, true)
        && $foreignKey->fetchColumn()
    ) {
        $ready = true;
        return;
    }

    $needsOwnershipBackfill = !isset($columns['pengguna_id']) || $columns['pengguna_id'] === 'YES';
    if (!isset($columns['pengguna_id'])) {
        $pdo->exec('ALTER TABLE transaksi ADD COLUMN pengguna_id INT UNSIGNED NULL AFTER id');
    }

    if ($needsOwnershipBackfill) {
        $pemilikAwal = $pdo->query('SELECT MIN(id) FROM pengguna')->fetchColumn();
        if (!$pemilikAwal) {
            $pemilikAwal = (int) $_SESSION['pengguna_id'];
        }
        $stmt = $pdo->prepare(
            'UPDATE transaksi t LEFT JOIN pengguna p ON p.id = t.pengguna_id
             SET t.pengguna_id = ? WHERE p.id IS NULL'
        );
        $stmt->execute([$pemilikAwal]);
    }

    if (!isset($columns['pengguna_id']) || $columns['pengguna_id'] === 'YES') {
        $pdo->exec('ALTER TABLE transaksi MODIFY pengguna_id INT UNSIGNED NOT NULL');
    }

    if (!in_array('idx_pengguna_tanggal', $indexes, true)) {
        $pdo->exec('ALTER TABLE transaksi ADD INDEX idx_pengguna_tanggal (pengguna_id, tanggal)');
    }

    $foreignKey->execute(['transaksi', 'pengguna_id', 'pengguna']);
    if (!$foreignKey->fetchColumn()) {
        $pdo->exec(
            'ALTER TABLE transaksi ADD CONSTRAINT fk_transaksi_pengguna
             FOREIGN KEY (pengguna_id) REFERENCES pengguna(id) ON DELETE CASCADE'
        );
    }

    $ready = true;
}

function filter_transaksi(array $query, int $userId): array
{
    $fJenis = is_string($query['jenis'] ?? null) ? $query['jenis'] : '';
    $fKategori = is_string($query['kategori'] ?? null) ? $query['kategori'] : '';
    $fBulan = is_string($query['bulan'] ?? null) ? $query['bulan'] : '';
    $fCari = is_string($query['q'] ?? null) ? trim($query['q']) : '';

    $where = ['pengguna_id = ?'];
    $params = [$userId];

    if (in_array($fJenis, ['masuk', 'keluar'], true)) {
        $where[] = 'jenis = ?';
        $params[] = $fJenis;
    } else {
        $fJenis = '';
    }

    if (in_array($fKategori, KATEGORI, true)) {
        $where[] = 'kategori = ?';
        $params[] = $fKategori;
    } else {
        $fKategori = '';
    }

    if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $fBulan)) {
        $where[] = 'tanggal BETWEEN ? AND ?';
        $params[] = $fBulan . '-01';
        $params[] = date('Y-m-t', strtotime($fBulan . '-01'));
    } else {
        $fBulan = '';
    }

    if ($fCari !== '') {
        $where[] = '(keterangan LIKE ? OR kategori LIKE ?)';
        $like = '%' . addcslashes($fCari, '%_\\') . '%';
        $params[] = $like;
        $params[] = $like;
    }

    return [
        'where' => $where,
        'params' => $params,
        'jenis' => $fJenis,
        'kategori' => $fKategori,
        'bulan' => $fBulan,
        'q' => $fCari,
        'active' => $fJenis !== '' || $fKategori !== '' || $fBulan !== '' || $fCari !== '',
    ];
}

function ensure_user_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pengguna (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            nama VARCHAR(100) NOT NULL,
            username VARCHAR(40) NOT NULL,
            email VARCHAR(254) NULL,
            google_id VARCHAR(255) NULL,
            password_hash VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_pengguna_username (username),
            UNIQUE KEY uq_pengguna_email (email),
            UNIQUE KEY uq_pengguna_google_id (google_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $check = $pdo->prepare(
        'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?'
    );
    $check->execute(['pengguna', 'chk_satu_pengguna', 'CHECK']);
    if ($check->fetchColumn()) {
        $pdo->exec('ALTER TABLE pengguna DROP CONSTRAINT `chk_satu_pengguna`');
    }

    $columns = $pdo->query(
        'SELECT COLUMN_NAME, CONCAT(EXTRA, \'|\', IS_NULLABLE) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'pengguna\''
    )->fetchAll(PDO::FETCH_KEY_PAIR);

    if (isset($columns['id']) && stripos($columns['id'], 'auto_increment') === false) {
        $pdo->exec('ALTER TABLE pengguna MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT');
    }
    if (!isset($columns['email'])) {
        $pdo->exec('ALTER TABLE pengguna ADD COLUMN email VARCHAR(254) NULL AFTER username');
    }
    if (!isset($columns['google_id'])) {
        $pdo->exec('ALTER TABLE pengguna ADD COLUMN google_id VARCHAR(255) NULL AFTER email');
    }
    if (isset($columns['password_hash']) && substr($columns['password_hash'], -4) !== '|YES') {
        $pdo->exec('ALTER TABLE pengguna MODIFY password_hash VARCHAR(255) NULL');
    }

    $indexes = $pdo->query(
        'SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = \'pengguna\''
    )->fetchAll(PDO::FETCH_COLUMN);
    foreach (['email' => 'uq_pengguna_email', 'google_id' => 'uq_pengguna_google_id'] as $column => $index) {
        if (!in_array($index, $indexes, true)) {
            $pdo->exec('ALTER TABLE pengguna ADD UNIQUE KEY `' . $index . '` (`' . $column . '`)');
        }
    }
    $ready = true;
}

function login_user(array $pengguna): void
{
    session_regenerate_id(true);
    unset($_SESSION['csrf']);
    $_SESSION['pengguna_id'] = (int) $pengguna['id'];
    $_SESSION['pengguna_nama'] = $pengguna['nama'];
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

/* ---------- Flash message ---------- */

function flash_set(string $tipe, string $pesan): void
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}