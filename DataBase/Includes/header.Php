<?php
/**
 * Kasora - Header + navigasi.
 * Variabel opsional sebelum include: $judul (string), $halaman (string)
 */
require_once __DIR__ . '/helpers.php';

$judul   = $judul   ?? 'Dashboard';
$halaman = $halaman ?? 'dashboard';
$flash   = flash_get();

$menu = [
    'dashboard' => ['Dashboard', 'index.php', '⌂'],
    'masuk'     => ['Pemasukan', 'riwayat.php?jenis=masuk', '↓'],
    'keluar'    => ['Pengeluaran', 'riwayat.php?jenis=keluar', '↑'],
    'riwayat'   => ['Riwayat Transaksi', 'riwayat.php', '◷'],
    'profil'    => ['Profil', 'profil.php', '○'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul) ?> · Kasora</title>
    <link rel="stylesheet" href="assets/app.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#eef2ff', 100:'#e0e7ff', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca' }
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased <?= $halaman === 'dashboard' ? 'dashboard-page' : '' ?>">

<aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col bg-slate-950 px-4 py-6 text-white lg:flex">
    <a href="index.php" class="mb-9 flex items-center gap-3 px-2">
        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-brand-600 text-xl font-extrabold shadow-lg shadow-indigo-950/30">K</span>
        <span>
            <span class="block text-lg font-extrabold tracking-tight">Kasora</span>
            <span class="block text-xs text-slate-400">Keuangan pribadi</span>
        </span>
    </a>
    <a href="tambah.php" class="mb-7 flex items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-bold shadow-lg shadow-indigo-950/30 transition hover:bg-brand-500">
        <span class="text-lg leading-none">+</span> Catat Transaksi
    </a>
    <p class="mb-3 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Menu utama</p>
    <nav class="space-y-1">
        <?php foreach ($menu as $key => [$label, $href, $icon]): ?>
            <a href="<?= $href ?>"
               class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition
                      <?= $halaman === $key ? 'bg-brand-600/20 text-indigo-200 ring-1 ring-inset ring-brand-500/30' : 'text-slate-300 hover:bg-white/5 hover:text-white' ?>">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-white/5 text-base"><?= $icon ?></span>
                <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="mt-auto border-t border-white/10 pt-4">
        <div class="mb-3 flex items-center gap-3 px-2">
            <span class="grid h-9 w-9 place-items-center rounded-full bg-indigo-500/20 font-bold text-indigo-200"><?= e(strtoupper(substr($_SESSION['pengguna_nama'] ?? 'K', 0, 1))) ?></span>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold"><?= e($_SESSION['pengguna_nama'] ?? 'Pengguna') ?></p>
                <p class="text-xs text-slate-500">Akun pribadi</p>
            </div>
        </div>
        <form action="logout.php" method="post">
            <?= csrf_field() ?>
            <button type="submit" class="w-full rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-400 transition hover:bg-white/5 hover:text-white">Keluar</button>
        </form>
    </div>
</aside>

<div class="min-h-screen lg:pl-64">
<header class="dashboard-site-header sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur site-enter">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <a href="index.php" class="flex items-center gap-2 lg:hidden">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-lg font-extrabold text-white">K</span>
                <span class="text-lg font-extrabold tracking-tight">Kasora</span>
            </a>
            <span class="hidden text-sm font-semibold text-slate-500 lg:inline">Keuangan pribadi · <?= e($judul) ?></span>
        </div>
        <div class="flex items-center gap-3">
            <a href="riwayat.php" aria-label="Buka riwayat transaksi" class="grid h-10 w-10 place-items-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-brand-700">
                <svg aria-hidden="true" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
            </a>
            <a href="profil.php" class="flex items-center gap-2 rounded-xl px-1.5 py-1 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 sm:px-2 sm:py-1.5">
                <span class="grid h-8 w-8 place-items-center rounded-full bg-indigo-100 font-bold text-brand-700"><?= e(strtoupper(substr($_SESSION['pengguna_nama'] ?? 'K', 0, 1))) ?></span>
                <span class="hidden sm:inline"><?= e($_SESSION['pengguna_nama'] ?? '') ?></span>
            </a>
            <form action="logout.php" method="post" class="lg:hidden">
                <?= csrf_field() ?>
                <button type="submit" class="rounded-xl border border-slate-200 px-2.5 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 sm:px-3 sm:text-sm">Keluar</button>
            </form>
        </div>
    </div>
</header>

<main class="mobile-app-content mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-8 lg:px-10 site-enter">

<?php if ($flash): ?>
    <div class="mb-6 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm font-medium
        <?= $flash['tipe'] === 'sukses'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
            : 'border-rose-200 bg-rose-50 text-rose-800' ?>">
        <span class="mt-0.5"><?= $flash['tipe'] === 'sukses' ? '✓' : '!' ?></span>
        <span><?= e($flash['pesan']) ?></span>
    </div>
<?php endif; ?>