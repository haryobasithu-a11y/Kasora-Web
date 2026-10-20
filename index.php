<?php
/**
 * Kasora - Dashboard keuangan pribadi.
 */
$judul = 'Dashboard';
$halaman = 'dashboard';
require_once __DIR__ . '/includes/helpers.php';
require_login();

$pdo = db();
$userId = (int) $_SESSION['pengguna_id'];

$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN nominal ELSE 0 END), 0) AS masuk,
        COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN nominal ELSE 0 END), 0) AS keluar
     FROM transaksi WHERE pengguna_id = ?"
);
$stmt->execute([$userId]);
$total = $stmt->fetch();
$totalMasuk = (float) $total['masuk'];
$totalKeluar = (float) $total['keluar'];
$saldo = $totalMasuk - $totalKeluar;

$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN nominal ELSE 0 END), 0) AS masuk,
        COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN nominal ELSE 0 END), 0) AS keluar
     FROM transaksi
     WHERE pengguna_id = ? AND tanggal BETWEEN ? AND ?"
);
$stmt->execute([$userId, date('Y-m-01'), date('Y-m-t')]);
$bulanIni = $stmt->fetch();

$stmt = $pdo->prepare(
    "SELECT kategori, SUM(nominal) AS total
     FROM transaksi
     WHERE pengguna_id = ? AND jenis = 'keluar'
       AND tanggal BETWEEN ? AND ?
     GROUP BY kategori
     ORDER BY total DESC
     LIMIT 5"
);
$stmt->execute([$userId, date('Y-m-01'), date('Y-m-t')]);
$kategoriTop = $stmt->fetchAll();

$stmt = $pdo->prepare(
    'SELECT id, jenis, kategori, nominal, tanggal, keterangan
     FROM transaksi WHERE pengguna_id = ?
     ORDER BY tanggal DESC, id DESC LIMIT 6'
);
$stmt->execute([$userId]);
$terbaru = $stmt->fetchAll();

$chartStart = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
$chartEnd = (new DateTimeImmutable('last day of this month'))->format('Y-m-d');
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(tanggal, '%Y-%m') AS bulan,
            SUM(CASE WHEN jenis = 'masuk' THEN nominal ELSE 0 END) AS masuk,
            SUM(CASE WHEN jenis = 'keluar' THEN nominal ELSE 0 END) AS keluar
     FROM transaksi
     WHERE pengguna_id = ? AND tanggal BETWEEN ? AND ?
     GROUP BY DATE_FORMAT(tanggal, '%Y-%m')
     ORDER BY bulan"
);
$stmt->execute([$userId, $chartStart->format('Y-m-d'), $chartEnd]);
$chartData = [];
foreach ($stmt->fetchAll() as $row) {
    $chartData[$row['bulan']] = ['masuk' => (float) $row['masuk'], 'keluar' => (float) $row['keluar']];
}

$chartMonths = [];
$monthNames = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$chartMax = 0.0;
$chartMasuk = [];
$chartKeluar = [];
for ($i = 0; $i < 6; $i++) {
    $month = $chartStart->modify('+' . $i . ' months');
    $key = $month->format('Y-m');
    $values = $chartData[$key] ?? ['masuk' => 0.0, 'keluar' => 0.0];
    $chartMonths[] = ['label' => $monthNames[(int) $month->format('n')], 'key' => $key];
    $chartMasuk[] = $values['masuk'];
    $chartKeluar[] = $values['keluar'];
    $chartMax = max($chartMax, $values['masuk'], $values['keluar']);
}
$chartMax = max(1.0, $chartMax);
$namaBulanIni = $monthNames[(int) date('n')] . ' ' . date('Y');
$categoryColors = ['#168fe5', '#f45b78', '#ff8a1c', '#13b99a', '#8157e5'];
$persenMasukBulan = $totalMasuk > 0 ? round($bulanIni['masuk'] / $totalMasuk * 100) : 0;
$persenKeluarBulan = $totalKeluar > 0 ? round($bulanIni['keluar'] / $totalKeluar * 100) : 0;
$hariNames = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
$tanggalSekarang = new DateTimeImmutable('now');
$hariIni = $tanggalSekarang->format('Y-m-d');
$namaHariIni = $hariNames[(int) $tanggalSekarang->format('N')];
$angkaHariIni = $tanggalSekarang->format('j');
$timestampSekarang = time() * 1000;
$chartPointsMasuk = [];
$chartPointsKeluar = [];
foreach ($chartMonths as $i => $month) {
    $x = 48 + ($i * 126);
    $chartPointsMasuk[] = $x . ',' . round(166 - ($chartMasuk[$i] / $chartMax * 132), 1);
    $chartPointsKeluar[] = $x . ',' . round(166 - ($chartKeluar[$i] / $chartMax * 132), 1);
}

require __DIR__ . '/includes/header.php';
?>

<div class="mobile-dashboard">
    <section class="mobile-dashboard__hero">
        <div class="mobile-dashboard__topline">
            <a href="index.php" class="mobile-dashboard__brand"><span>K</span> Kasora</a>
            <div class="mobile-dashboard__actions">
                <a href="export.php" class="mobile-dashboard__icon-button" aria-label="Ekspor ke Excel" title="Ekspor ke Excel">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0 4-4m-4 4-4-4M5 16v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3"/></svg>
                </a>
                <a href="profil.php" class="mobile-dashboard__avatar" aria-label="Buka profil"><?= e(strtoupper(substr($_SESSION['pengguna_nama'] ?? 'K', 0, 1))) ?></a>
            </div>
        </div>
        <p class="mobile-dashboard__greeting">Hai, <?= e(explode(' ', trim($_SESSION['pengguna_nama'] ?? 'Kamu'))[0]) ?>!</p>
        <p class="mobile-dashboard__balance-label">Uang kamu tersisa</p>
        <p class="mobile-dashboard__balance"><?= rupiah($saldo) ?></p>
        <div class="mobile-dashboard__date">
            <span><?= e($namaHariIni) ?>, <?= e($angkaHariIni) ?> <?= e($monthNames[(int) $tanggalSekarang->format('n')]) ?> <?= e($tanggalSekarang->format('Y')) ?></span>
            <time data-mobile-clock datetime="<?= e($tanggalSekarang->format(DateTimeInterface::ATOM)) ?>"><?= e($tanggalSekarang->format('H:i:s')) ?></time>
        </div>
        <div class="mobile-dashboard__summary">
            <a href="riwayat.php?jenis=masuk" class="mobile-dashboard__summary-card">
                <span>Pemasukan <span aria-hidden="true" class="mobile-dashboard__summary-arrow">↗</span></span>
                <strong><?= rupiah($bulanIni['masuk']) ?></strong>
                <small>Bulan ini · <?= $persenMasukBulan ?>% dari total</small>
            </a>
            <a href="riwayat.php?jenis=keluar" class="mobile-dashboard__summary-card mobile-dashboard__summary-card--expense">
                <span>Pengeluaran <span aria-hidden="true" class="mobile-dashboard__summary-arrow">↘</span></span>
                <strong><?= rupiah($bulanIni['keluar']) ?></strong>
                <small>Bulan ini · <?= $persenKeluarBulan ?>% dari total</small>
            </a>
        </div>
        <div class="mobile-dashboard__categories">
            <div class="mobile-dashboard__section-heading">
                <div>
                    <h2>Kategori pengeluaran</h2>
                    <p>Proporsi bulan ini</p>
                </div>
                <a href="riwayat.php?jenis=keluar">Lihat semua <span aria-hidden="true">›</span></a>
            </div>
            <?php if (!$kategoriTop): ?>
                <p class="mobile-dashboard__empty-category">Belum ada pengeluaran bulan ini.</p>
            <?php else: ?>
                <div class="mobile-dashboard__category-list">
                    <?php foreach ($kategoriTop as $index => $kategori):
                        $persen = (float) $bulanIni['keluar'] > 0 ? min(100, round((float) $kategori['total'] / (float) $bulanIni['keluar'] * 100)) : 0;
                        $derajat = round($persen * 3.6, 1);
                        $warnaKategori = $categoryColors[$index % count($categoryColors)];
                        ?>
                        <a href="riwayat.php?jenis=keluar&amp;kategori=<?= rawurlencode($kategori['kategori']) ?>" class="mobile-dashboard__category">
                            <span class="mobile-dashboard__category-ring" style="--ring-color: <?= e($warnaKategori) ?>; --ring-angle: <?= $derajat ?>deg">
                                <span><?= $persen ?><small>%</small></span>
                            </span>
                            <span class="mobile-dashboard__category-name"><?= e($kategori['kategori']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="mobile-dashboard__transactions">
        <div class="mobile-dashboard__section-heading mobile-dashboard__transaction-heading">
            <div>
                <h2>Transaksi terakhir</h2>
                <p>Catatan keuanganmu</p>
            </div>
            <a href="riwayat.php">Lihat semua <span aria-hidden="true">›</span></a>
        </div>
        <?php if (!$terbaru): ?>
            <div class="mobile-dashboard__empty">
                <span aria-hidden="true">＋</span>
                <strong>Belum ada transaksi</strong>
                <p>Mulai catat pemasukan atau pengeluaranmu.</p>
                <a href="tambah.php">Catat transaksi</a>
            </div>
        <?php else: ?>
            <ul class="mobile-dashboard__transaction-list">
                <?php foreach ($terbaru as $transaksi):
                    $isMasuk = $transaksi['jenis'] === 'masuk'; ?>
                    <li class="mobile-dashboard__transaction">
                        <span class="mobile-dashboard__transaction-icon <?= $isMasuk ? 'is-income' : 'is-expense' ?>" aria-hidden="true">
                            <?= $isMasuk ? '↓' : '↑' ?>
                        </span>
                        <span class="mobile-dashboard__transaction-info">
                            <strong><?= e($transaksi['keterangan'] ?: $transaksi['kategori']) ?></strong>
                            <small><?= e($transaksi['kategori']) ?> · <?= e(tanggal_indo($transaksi['tanggal'])) ?></small>
                        </span>
                        <strong class="mobile-dashboard__transaction-amount <?= $isMasuk ? 'is-income' : 'is-expense' ?>">
                            <?= $isMasuk ? '+' : '−' ?> <?= rupiah($transaksi['nominal']) ?>
                        </strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <details class="mobile-dashboard__calendar">
        <summary>Kalender dan tanggal</summary>
        <section data-calendar data-today="<?= e($hariIni) ?>" data-now="<?= $timestampSekarang ?>">
            <div class="mobile-dashboard__calendar-clock">
                <span><?= e($namaHariIni) ?>, <?= e($angkaHariIni) ?> <?= e($monthNames[(int) $tanggalSekarang->format('n')]) ?> <?= e($tanggalSekarang->format('Y')) ?></span>
                <time data-live-clock datetime="<?= e($tanggalSekarang->format(DateTimeInterface::ATOM)) ?>"><?= e($tanggalSekarang->format('H:i:s')) ?></time>
            </div>
            <div class="mobile-dashboard__calendar-controls">
                <button type="button" class="calendar-nav" data-calendar-prev aria-label="Bulan sebelumnya">‹</button>
                <h3 class="text-sm font-extrabold capitalize" data-calendar-month><?= e($namaBulanIni) ?></h3>
                <button type="button" class="calendar-nav" data-calendar-next aria-label="Bulan berikutnya">›</button>
            </div>
            <div class="calendar-grid calendar-weekdays" aria-hidden="true">
                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
            </div>
            <div class="calendar-grid mt-1" data-calendar-days role="grid" aria-label="Tanggal dalam bulan"></div>
            <p class="mt-3 min-h-5 text-center text-xs font-medium text-slate-500" data-calendar-selected aria-live="polite">
                Hari ini: <?= e($namaHariIni) ?>, <?= e($angkaHariIni) ?> <?= e($monthNames[(int) $tanggalSekarang->format('n')]) ?>
            </p>
            <button type="button" class="mt-2 w-full rounded-lg py-2 text-xs font-bold text-brand-600 transition hover:bg-brand-50" data-calendar-today>Kembali ke bulan ini</button>
        </section>
    </details>
</div>

<div class="desktop-dashboard">
<div class="mb-7 flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-sm font-semibold text-brand-600">RINGKASAN KEUANGAN</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl">Halo, <?= e($_SESSION['pengguna_nama'] ?? 'Pengguna') ?> 👋</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau pemasukan dan pengeluaran pribadimu.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="export.php" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
            <svg aria-hidden="true" class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="currentColor"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zm0 2 4 4h-4zM8 12h2l1 1.7 1-1.7h2l-2 3 2 3h-2l-1-1.8-1 1.8H8l2-3z"/></svg>
            Export Excel
        </a>
        <a href="tambah.php" class="inline-flex items-center gap-2 rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-brand-700">
            <span class="text-lg leading-none">+</span> Catat Transaksi
        </a>
    </div>
</div>

<section class="dashboard-summary grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <article class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-brand-600 to-violet-700 p-5 text-white shadow-lg shadow-indigo-200/60">
        <div class="absolute -right-8 -top-10 h-36 w-36 rounded-full border-[22px] border-white/10"></div>
        <div class="relative flex items-center justify-between">
            <p class="text-sm font-semibold text-indigo-100">Saldo saat ini</p>
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 text-xl">◈</span>
        </div>
        <p class="relative mt-5 text-3xl font-extrabold tracking-tight"><?= rupiah($saldo) ?></p>
        <p class="relative mt-2 text-xs text-indigo-100">Total pemasukan dikurangi pengeluaran</p>
    </article>
    <article class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Pemasukan bulan ini</p>
                <p class="mt-1 text-xs text-slate-400"><?= e($namaBulanIni) ?></p>
            </div>
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-xl font-bold text-emerald-600">↓</span>
        </div>
        <p class="mt-5 text-2xl font-extrabold tracking-tight text-emerald-600"><?= rupiah($bulanIni['masuk']) ?></p>
        <p class="mt-2 text-xs text-slate-400">Total sepanjang waktu: <?= rupiah($totalMasuk) ?></p>
    </article>
    <article class="rounded-2xl border border-rose-100 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-500">Pengeluaran bulan ini</p>
                <p class="mt-1 text-xs text-slate-400"><?= e($namaBulanIni) ?></p>
            </div>
            <span class="grid h-11 w-11 place-items-center rounded-2xl bg-rose-50 text-xl font-bold text-rose-600">↑</span>
        </div>
        <p class="mt-5 text-2xl font-extrabold tracking-tight text-rose-600"><?= rupiah($bulanIni['keluar']) ?></p>
        <p class="mt-2 text-xs text-slate-400">Total sepanjang waktu: <?= rupiah($totalKeluar) ?></p>
    </article>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 xl:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">Arus kas</h2>
                <p class="mt-1 text-sm text-slate-500">Pemasukan dan pengeluaran 6 bulan terakhir</p>
            </div>
            <div class="flex gap-4 text-xs font-semibold">
                <span class="inline-flex items-center gap-2 text-emerald-700"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>Pemasukan</span>
                <span class="inline-flex items-center gap-2 text-rose-700"><span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>Pengeluaran</span>
            </div>
        </div>
        <div class="mt-5 overflow-x-auto">
            <svg class="h-56 min-w-[620px] w-full" viewBox="0 0 720 220" role="img" aria-label="Grafik pemasukan dan pengeluaran selama enam bulan">
                <line x1="42" y1="34" x2="690" y2="34" stroke="#e2e8f0" stroke-dasharray="4 5"/>
                <line x1="42" y1="78" x2="690" y2="78" stroke="#e2e8f0" stroke-dasharray="4 5"/>
                <line x1="42" y1="122" x2="690" y2="122" stroke="#e2e8f0" stroke-dasharray="4 5"/>
                <line x1="42" y1="166" x2="690" y2="166" stroke="#e2e8f0"/>
                <polyline points="<?= e(implode(' ', $chartPointsMasuk)) ?>" fill="none" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="<?= e(implode(' ', $chartPointsKeluar)) ?>" fill="none" stroke="#f43f5e" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                <?php foreach ($chartMonths as $i => $month): ?>
                    <circle cx="<?= 48 + ($i * 126) ?>" cy="<?= round(166 - ($chartMasuk[$i] / $chartMax * 132), 1) ?>" r="4" fill="#10b981"/>
                    <circle cx="<?= 48 + ($i * 126) ?>" cy="<?= round(166 - ($chartKeluar[$i] / $chartMax * 132), 1) ?>" r="4" fill="#f43f5e"/>
                    <text x="<?= 48 + ($i * 126) ?>" y="196" text-anchor="middle" fill="#64748b" font-size="12"><?= e($month['label']) ?></text>
                <?php endforeach; ?>
            </svg>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" data-calendar data-today="<?= e($hariIni) ?>" data-now="<?= $timestampSekarang ?>">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold">Kalender</h2>
                <p class="mt-1 text-sm text-slate-500">Tanggal dan waktu sekarang</p>
            </div>
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-50 text-lg text-brand-600" aria-hidden="true">▦</span>
        </div>
        <div class="mt-4 rounded-2xl bg-gradient-to-br from-indigo-600 to-brand-700 p-4 text-white">
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-100">Hari ini · Jakarta</p>
            <p class="mt-1 text-lg font-bold"><?= e($namaHariIni) ?>, <?= e($angkaHariIni) ?> <?= e($monthNames[(int) $tanggalSekarang->format('n')]) ?> <?= e($tanggalSekarang->format('Y')) ?></p>
            <time class="mt-2 block text-3xl font-extrabold tabular-nums tracking-wide" data-live-clock datetime="<?= e($tanggalSekarang->format(DateTimeInterface::ATOM)) ?>"><?= e($tanggalSekarang->format('H:i:s')) ?></time>
        </div>
        <div class="mt-5">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" class="calendar-nav" data-calendar-prev aria-label="Bulan sebelumnya">‹</button>
                <h3 class="text-sm font-extrabold capitalize" data-calendar-month><?= e($namaBulanIni) ?></h3>
                <button type="button" class="calendar-nav" data-calendar-next aria-label="Bulan berikutnya">›</button>
            </div>
            <div class="calendar-grid calendar-weekdays" aria-hidden="true">
                <span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span>
            </div>
            <div class="calendar-grid mt-1" data-calendar-days role="grid" aria-label="Tanggal dalam bulan"></div>
            <p class="mt-3 min-h-5 text-center text-xs font-medium text-slate-500" data-calendar-selected aria-live="polite">
                Hari ini: <?= e($namaHariIni) ?>, <?= e($angkaHariIni) ?> <?= e($monthNames[(int) $tanggalSekarang->format('n')]) ?>
            </p>
            <button type="button" class="mt-2 w-full rounded-lg py-2 text-xs font-bold text-brand-600 transition hover:bg-brand-50" data-calendar-today>Kembali ke bulan ini</button>
        </div>
    </section>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="font-extrabold">Transaksi terbaru</h2>
                <p class="mt-1 text-xs text-slate-400">Catatan keuangan pribadimu</p>
            </div>
            <a href="riwayat.php" class="text-sm font-bold text-brand-600 hover:underline">Lihat semua</a>
        </div>
        <?php if (!$terbaru): ?>
            <div class="px-5 py-12 text-center">
                <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 text-xl text-slate-400">◷</span>
                <p class="mt-3 font-semibold text-slate-700">Belum ada transaksi</p>
                <p class="mt-1 text-sm text-slate-500">Catat pemasukan atau pengeluaran pertamamu.</p>
                <a href="tambah.php" class="mt-4 inline-flex rounded-xl bg-brand-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Catat transaksi</a>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-slate-100">
                <?php foreach ($terbaru as $t):
                    $masuk = $t['jenis'] === 'masuk'; ?>
                    <li class="flex items-center gap-3 px-5 py-4">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl <?= $masuk ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?> text-lg font-bold"><?= $masuk ? '↓' : '↑' ?></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold"><?= e($t['keterangan'] ?: $t['kategori']) ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?= e($t['kategori']) ?> · <?= e(tanggal_indo($t['tanggal'])) ?></p>
                        </div>
                        <p class="whitespace-nowrap text-sm font-extrabold <?= $masuk ? 'text-emerald-600' : 'text-rose-600' ?>"><?= $masuk ? '+' : '−' ?> <?= rupiah($t['nominal']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="font-extrabold">Pengeluaran bulan ini</h2>
                <p class="mt-1 text-xs text-slate-400">Berdasarkan kategori</p>
            </div>
            <a href="riwayat.php?jenis=keluar&bulan=<?= e(date('Y-m')) ?>" aria-label="Lihat semua pengeluaran" class="rounded-lg px-2 py-1 text-sm font-bold text-brand-600 hover:bg-brand-50">→</a>
        </div>
        <?php if (!$kategoriTop): ?>
            <p class="py-10 text-center text-sm text-slate-400">Belum ada pengeluaran bulan ini.</p>
        <?php else: ?>
            <div class="mt-5 space-y-5">
                <?php foreach ($kategoriTop as $k):
                    $persen = (float) $bulanIni['keluar'] > 0 ? min(100, round((float) $k['total'] / (float) $bulanIni['keluar'] * 100)) : 0; ?>
                    <div>
                        <div class="mb-2 flex justify-between gap-3 text-sm">
                            <span class="truncate font-semibold"><?= e($k['kategori']) ?></span>
                            <span class="whitespace-nowrap text-xs font-semibold text-slate-500"><?= rupiah($k['total']) ?></span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-gradient-to-r from-rose-400 to-rose-500" style="width: <?= $persen ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
 </div>

<?php require __DIR__ . '/includes/footer.php'; ?>
