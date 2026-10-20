<?php
/**
 * Kasora - Proses simpan transaksi (menerima POST dari tambah.php).
 */
require_once __DIR__ . '/includes/helpers.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('tambah.php');
}

$input = [
    'jenis'      => $_POST['jenis']      ?? '',
    'kategori'   => trim($_POST['kategori']   ?? ''),
    'nominal'    => trim($_POST['nominal']    ?? ''),
    'tanggal'    => trim($_POST['tanggal']    ?? ''),
    'keterangan' => trim($_POST['keterangan'] ?? ''),
];

function gagal(string $pesan, array $input): void
{
    $_SESSION['old'] = $input;
    flash_set('error', $pesan);
    redirect('tambah.php');
}

if (!csrf_valid($_POST['csrf'] ?? null)) {
    gagal('Sesi tidak valid. Silakan coba lagi.', $input);
}

if (!in_array($input['jenis'], ['masuk', 'keluar'], true)) {
    gagal('Jenis transaksi tidak valid.', $input);
}

if (!in_array($input['kategori'], KATEGORI, true)) {
    gagal('Silakan pilih kategori yang tersedia.', $input);
}

if (!ctype_digit($input['nominal']) || (int) $input['nominal'] < 1) {
    gagal('Nominal harus berupa angka bulat lebih dari 0.', $input);
}

if (strlen($input['nominal']) > 13) {
    gagal('Nominal terlalu besar.', $input);
}

$tgl = DateTime::createFromFormat('Y-m-d', $input['tanggal']);
if (!$tgl || $tgl->format('Y-m-d') !== $input['tanggal']) {
    gagal('Format tanggal tidak valid.', $input);
}

if (mb_strlen($input['keterangan']) > 255) {
    gagal('Keterangan maksimal 255 karakter.', $input);
}

$stmt = db()->prepare(
    'INSERT INTO transaksi (pengguna_id, jenis, kategori, nominal, tanggal, keterangan)
     VALUES (:pengguna_id, :jenis, :kategori, :nominal, :tanggal, :keterangan)'
);
$stmt->execute([
    ':pengguna_id' => $_SESSION['pengguna_id'],
    ':jenis'      => $input['jenis'],
    ':kategori'   => $input['kategori'],
    ':nominal'    => $input['nominal'],
    ':tanggal'    => $input['tanggal'],
    ':keterangan' => $input['keterangan'] !== '' ? $input['keterangan'] : null,
]);

flash_set('sukses', 'Transaksi berhasil disimpan.');
redirect('riwayat.php');