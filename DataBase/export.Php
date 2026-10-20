<?php
/**
 * Kasora - Unduh laporan transaksi pribadi dalam format Excel XLSX.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/excel.php';
require_login();

$filter = filter_transaksi($_GET, (int) $_SESSION['pengguna_id']);
$stmt = db()->prepare(
    'SELECT tanggal, jenis, kategori, keterangan, nominal
     FROM transaksi WHERE ' . implode(' AND ', $filter['where']) . '
     ORDER BY tanggal ASC, id ASC'
);
$stmt->execute($filter['params']);
$transactions = $stmt->fetchAll();

$totalMasuk = 0.0;
$totalKeluar = 0.0;
foreach ($transactions as $transaction) {
    if ($transaction['jenis'] === 'masuk') {
        $totalMasuk += (float) $transaction['nominal'];
    } else {
        $totalKeluar += (float) $transaction['nominal'];
    }
}

try {
    $temporaryFile = buat_file_excel_transaksi($transactions, $totalMasuk, $totalKeluar, $filter['active']);
} catch (RuntimeException $exception) {
    error_log('Kasora XLSX export failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('File Excel gagal dibuat. Pastikan ekstensi PHP ZipArchive aktif lalu coba lagi.');
}

$filename = 'kasora-transaksi-' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($temporaryFile));
header('Cache-Control: private, no-store, max-age=0');
readfile($temporaryFile);
unlink($temporaryFile);
exit;
