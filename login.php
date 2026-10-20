<?php
/**
 * Kasora - Login.
 */
require_once __DIR__ . '/includes/helpers.php';
ensure_user_schema();

if (!empty($_SESSION['pengguna_id'])) {
    redirect('index.php');
}

$error = '';
$identifier = '';
$notice = flash_get();
$googleReady = getenv('KASORA_GOOGLE_CLIENT_ID')
    && getenv('KASORA_GOOGLE_CLIENT_SECRET')
    && getenv('KASORA_GOOGLE_REDIRECT_URI');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } else {
        $stmt = db()->prepare(
            'SELECT id, nama, username, email, password_hash
            FROM pengguna WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$identifier, $identifier]);
        $pengguna = $stmt->fetch();

        if ($pengguna && $pengguna['password_hash'] !== null && password_verify($password, $pengguna['password_hash'])) {
            login_user($pengguna);
            redirect('index.php');
        }
        $error = 'Username/email atau kata sandi salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · Kasora</title>
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
                    colors: { brand: { 50:'#eef2ff', 100:'#e0e7ff', 500:'#6366f1', 600:'#4f46e5', 700:'#4338ca' } }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<main class="grid min-h-screen lg:grid-cols-2 site-enter">
    <section class="relative hidden overflow-hidden bg-gradient-to-br from-indigo-700 via-brand-600 to-violet-700 p-12 text-white lg:flex lg:flex-col lg:justify-between">
        <div class="absolute -right-28 -top-28 h-96 w-96 rounded-full border-[48px] border-white/10"></div>
        <div class="absolute -bottom-40 -left-28 h-[30rem] w-[30rem] rounded-full border-[64px] border-white/10"></div>
        <a href="login.php" class="relative flex items-center gap-3">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white text-xl font-extrabold text-brand-700 shadow-lg">K</span>
            <span class="text-2xl font-extrabold tracking-tight">Kasora</span>
        </a>
        <div class="relative max-w-lg">
            <p class="mb-4 text-sm font-bold uppercase tracking-[0.2em] text-indigo-200">Kelola kas lebih mudah</p>
            <h1 class="text-4xl font-extrabold leading-tight xl:text-5xl">Semua catatan keuangan, dalam satu tempat.</h1>
            <p class="mt-5 max-w-md leading-7 text-indigo-100">Pantau saldo, catat transaksi, dan lihat riwayat keuangan dengan aman.</p>
        </div>
        <p class="relative text-sm text-indigo-200">Kasora · Pengelolaan Kas &amp; Anggaran</p>
    </section>

    <section class="flex items-center justify-center px-5 py-12 sm:px-10">
        <div class="w-full max-w-md auth-enter">
            <a href="login.php" class="mb-10 flex items-center justify-center gap-3 lg:hidden">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-600 text-lg font-extrabold text-white">K</span>
                <span class="text-2xl font-extrabold tracking-tight">Kasora</span>
            </a>
            <div class="mb-8">
                <p class="mb-2 text-sm font-bold text-brand-600">SELAMAT DATANG</p>
                <h2 class="text-3xl font-extrabold tracking-tight">Masuk ke Kasora</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Masuk untuk melanjutkan ke pengelolaan kas.</p>
            </div>

            <?php if ($error !== ''): ?>
                <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($notice): ?>
                <div role="status" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"><?= e($notice['pesan']) ?></div>
            <?php endif; ?>

            <form method="post" class="space-y-5" data-transition-message="Menyiapkan dashboard...">
                <?= csrf_field() ?>
                <div>
                    <label for="identifier" class="mb-2 block text-sm font-semibold">Username atau email</label>
                    <input id="identifier" name="identifier" type="text" autocomplete="username" required value="<?= e($identifier) ?>"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="Username atau email">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="Masukkan kata sandi">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100">Masuk</button>
            </form>

            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                <div class="relative flex justify-center"><span class="bg-slate-50 px-3 text-xs font-medium text-slate-400">atau</span></div>
            </div>
            <?php if ($googleReady): ?>
                <a href="google.php" class="flex w-full items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white px-5 py-3.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z" transform="translate(0 4)"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.75 7.18l7.73 6C44.43 37.86 46.98 31.72 46.98 24.55z"/><path fill="#FBBC05" d="M10.53 28.59A14.4 14.4 0 0 1 9.75 24c0-1.59.27-3.13.76-4.59l-7.98-6.19A23.9 23.9 0 0 0 0 24c0 3.88.93 7.56 2.56 10.78l7.97-6.19z" transform="translate(0 4)"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.91-5.8l-7.73-6c-2.15 1.45-4.92 2.3-8.18 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z" transform="translate(0 -4)"/></svg>
                    Lanjutkan dengan Google
                </a>
            <?php else: ?>
                <button type="button" disabled class="flex w-full cursor-not-allowed items-center justify-center gap-3 rounded-xl border border-slate-200 bg-slate-100 px-5 py-3.5 text-sm font-bold text-slate-400" title="Atur Google OAuth terlebih dahulu">
                    Lanjutkan dengan Google
                </button>
                <p class="mt-2 text-center text-xs text-slate-400">Google Login memerlukan konfigurasi OAuth.</p>
            <?php endif; ?>

            <p class="mt-7 text-center text-sm text-slate-500">Belum punya akun?
                <a href="daftar.php" class="font-bold text-brand-600 hover:text-brand-700">Daftar sekarang</a>
            </p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/transition.php'; ?>
<script src="assets/app.js" defer></script>
</body>
</html>
