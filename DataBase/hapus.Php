<?php
/**
 * Kasora - Hapus transaksi (hanya lewat POST + token CSRF).
 */
require_once __DIR__ . '/includes/helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('riwayat.php');
}

if (!csrf_valid($_POST['csrf'] ?? null)) {
    flash_set('error', 'Sesi tidak valid. Silakan coba lagi.');
    redirect('riwayat.php');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) {
    flash_set('error', 'ID transaksi tidak valid.');
    redirect('riwayat.php');
}

$stmt = db()->prepare('DELETE FROM transaksi WHERE id = ? AND pengguna_id = ?');
$stmt->execute([$id, $_SESSION['pengguna_id']]);

if ($stmt->rowCount() > 0) {
    flash_set('sukses', 'Transaksi berhasil dihapus.');
} else {
    flash_set('error', 'Transaksi tidak ditemukan.');
}

// Pertahankan filter yang sedang aktif (hanya path internal)
$kembali = $_POST['kembali'] ?? '';
if (!is_string($kembali) || !preg_match('/^riwayat\.php(\?[A-Za-z0-9=&%_.\-+]*)?$/', $kembali)) {
    $kembali = 'riwayat.php';
}
redirect($kembali);