<?php
/**
 * Kasora - Riwayat transaksi: tabel + filter + hapus.
 */
$judul   = 'Riwayat Transaksi';
$halaman = 'riwayat';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$filter = filter_transaksi($_GET, (int) $_SESSION['pengguna_id']);
$fJenis = $filter['jenis'];
$fKategori = $filter['kategori'];
$fBulan = $filter['bulan'];
$fCari = $filter['q'];
$halaman = $fJenis === 'masuk' ? 'masuk' : ($fJenis === 'keluar' ? 'keluar' : 'riwayat');
$judul = $fJenis === 'masuk' ? 'Pemasukan' : ($fJenis === 'keluar' ? 'Pengeluaran' : 'Riwayat Transaksi');
$where = $filter['where'];
$params = $filter['params'];

$sql = 'SELECT * FROM transaksi'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY tanggal DESC, id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$sumMasuk = $sumKeluar = 0.0;
foreach ($rows as $r) {
    if ($r['jenis'] === 'masuk') {
        $sumMasuk += (float) $r['nominal'];
    } else {
        $sumKeluar += (float) $r['nominal'];
    }
}

// URL saat ini (untuk kembali setelah hapus, dengan filter tetap aktif)
$kembali = 'riwayat.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
$adaFilter = $filter['active'];
$exportQuery = array_filter(
    ['q' => $fCari, 'jenis' => $fJenis, 'kategori' => $fKategori, 'bulan' => $fBulan],
    static function ($value) {
        return $value !== '';
    }
);

/** Tombol hapus (dipakai di tabel & kartu mobile). */
function tombol_hapus(array $t, string $kembali): string
{
    $label = e($t['kategori'] . ' - ' . rupiah($t['nominal']));
    return '<form action="hapus.php" method="post" class="inline"
                  onsubmit="return confirm(\'Hapus transaksi ' . $label . '? Tindakan ini tidak bisa dibatalkan.\');">'
         . csrf_field()
         . '<input type="hidden" name="id" value="' . (int) $t['id'] . '">'
         . '<input type="hidden" name="kembali" value="' . e($kembali) . '">'
         . '<button type="submit" class="rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">Hapus</button>'
         . '</form>';
}

require __DIR__ . '/includes/header.php';
?>

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl"><?= $halaman === 'masuk' ? 'Riwayat Pemasukan' : ($halaman === 'keluar' ? 'Riwayat Pengeluaran' : 'Riwayat Transaksi') ?></h1>
        <p class="text-sm text-slate-500"><?= count($rows) ?> transaksi<?= $adaFilter ? ' (difilter)' : '' ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="export.Php<?= $exportQuery ? '?' . e(http_build_query($exportQuery)) : '' ?>"
           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <span class="font-extrabold text-emerald-600">XLSX</span> Export Excel
        </a>
        <a href="tambah.php"
           class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-700">
            <span class="text-lg leading-none">+</span> Catat Transaksi
        </a>
    </div>
</div>

<!-- Filter -->
<form method="get" class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
    <input type="search" name="q" value="<?= e($fCari) ?>" placeholder="Cari keterangan…"
           class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 lg:col-span-2">

    <select name="jenis" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500">
        <option value="">Semua jenis</option>
        <option value="masuk"  <?= $fJenis === 'masuk'  ? 'selected' : '' ?>>Kas Masuk</option>
        <option value="keluar" <?= $fJenis === 'keluar' ? 'selected' : '' ?>>Kas Keluar</option>
    </select>

    <select name="kategori" class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500">
        <option value="">Semua kategori</option>
        <?php foreach (KATEGORI as $k): ?>
            <option value="<?= e($k) ?>" <?= $fKategori === $k ? 'selected' : '' ?>><?= e($k) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="month" name="bulan" value="<?= e($fBulan) ?>"
           class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-brand-500">

    <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
        <button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">Terapkan</button>
        <?php if ($adaFilter): ?>
            <a href="riwayat.php" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Reset</a>
        <?php endif; ?>
    </div>
</form>

<!-- Ringkasan hasil filter -->
<div class="mb-4 grid grid-cols-3 gap-3 text-center">
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Masuk</p>
        <p class="text-sm font-extrabold text-emerald-600 sm:text-base"><?= rupiah($sumMasuk) ?></p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Keluar</p>
        <p class="text-sm font-extrabold text-rose-600 sm:text-base"><?= rupiah($sumKeluar) ?></p>
    </div>
    <div class="rounded-xl border border-slate-200 bg-white p-3">
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Selisih</p>
        <p class="text-sm font-extrabold sm:text-base <?= ($sumMasuk - $sumKeluar) >= 0 ? 'text-slate-800' : 'text-rose-600' ?>">
            <?= rupiah($sumMasuk - $sumKeluar) ?>
        </p>
    </div>
</div>

<?php if (!$rows): ?>
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
        <p class="font-semibold">Tidak ada transaksi<?= $adaFilter ? ' yang cocok dengan filter' : '' ?>.</p>
        <p class="mt-1 text-sm text-slate-500">
            <?= $adaFilter ? 'Coba ubah atau reset filter.' : 'Catat kas masuk atau keluar pertama Anda.' ?>
        </p>
    </div>
<?php else: ?>

    <!-- Tabel (tablet & laptop) -->
    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3 font-semibold">Tanggal</th>
                    <th class="px-5 py-3 font-semibold">Jenis</th>
                    <th class="px-5 py-3 font-semibold">Kategori</th>
                    <th class="px-5 py-3 font-semibold">Keterangan</th>
                    <th class="px-5 py-3 text-right font-semibold">Nominal</th>
                    <th class="px-5 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($rows as $t):
                    $masuk = $t['jenis'] === 'masuk'; ?>
                    <tr class="transition hover:bg-slate-50/70">
                        <td class="whitespace-nowrap px-5 py-3.5 text-slate-600"><?= e(tanggal_indo($t['tanggal'])) ?></td>
                        <td class="px-5 py-3.5">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-bold
                                <?= $masuk ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                                <?= $masuk ? '↓ Masuk' : '↑ Keluar' ?>
                            </span>
                        </td>
                        <td class="px-5 py-3.5 font-medium"><?= e($t['kategori']) ?></td>
                        <td class="max-w-xs truncate px-5 py-3.5 text-slate-500"><?= e($t['keterangan'] ?? '—') ?></td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold <?= $masuk ? 'text-emerald-600' : 'text-rose-600' ?>">
                            <?= $masuk ? '+' : '−' ?> <?= rupiah($t['nominal']) ?>
                        </td>
                        <td class="px-5 py-3.5 text-right"><?= tombol_hapus($t, $kembali) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Kartu (HP) -->
    <ul class="space-y-3 md:hidden">
        <?php foreach ($rows as $t):
            $masuk = $t['jenis'] === 'masuk'; ?>
            <li class="rounded-2xl border border-slate-200 border-l-4 bg-white p-4 shadow-sm
                       <?= $masuk ? 'border-l-emerald-500' : 'border-l-rose-500' ?>">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-bold"><?= e($t['kategori']) ?></p>
                        <p class="text-xs text-slate-500"><?= e(tanggal_indo($t['tanggal'])) ?></p>
                    </div>
                    <p class="whitespace-nowrap text-base font-extrabold <?= $masuk ? 'text-emerald-600' : 'text-rose-600' ?>">
                        <?= $masuk ? '+' : '−' ?> <?= rupiah($t['nominal']) ?>
                    </p>
                </div>
                <?php if (!empty($t['keterangan'])): ?>
                    <p class="mt-2 text-sm text-slate-600"><?= e($t['keterangan']) ?></p>
                <?php endif; ?>
                <div class="mt-2 flex items-center justify-between">
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold
                        <?= $masuk ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' ?>">
                        <?= $masuk ? 'Kas Masuk' : 'Kas Keluar' ?>
                    </span>
                    <?= tombol_hapus($t, $kembali) ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>