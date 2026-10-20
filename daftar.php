<?php
/**
 * Kasora - Pendaftaran pengguna baru.
 */
require_once __DIR__ . '/includes/helpers.php';
ensure_user_schema();

if (!empty($_SESSION['pengguna_id'])) {
    redirect('index.php');
}

$error = '';
$nama = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['konfirmasi_password'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif ($nama === '' || strlen($nama) > 100) {
        $error = 'Nama wajib diisi dan maksimal 100 karakter.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username)) {
        $error = 'Username harus 3–40 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $error = 'Masukkan alamat email yang valid.';
    } elseif (strlen($password) < 10) {
        $error = 'Kata sandi harus terdiri dari minimal 10 karakter.';
    } elseif ($password !== $confirmation) {
        $error = 'Konfirmasi kata sandi tidak sama.';
    } else {
        try {
            $stmt = db()->prepare(
                'INSERT INTO pengguna (nama, username, email, password_hash) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$nama, $username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            login_user([
                'id' => db()->lastInsertId(),
                'nama' => $nama,
            ]);
            redirect('index.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'Username atau email tersebut sudah digunakan.';
            } else {
                throw $exception;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar · Kasora</title>
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
            <p class="mb-4 text-sm font-bold uppercase tracking-[0.2em] text-indigo-200">Mulai bersama Kasora</p>
            <h1 class="text-4xl font-extrabold leading-tight xl:text-5xl">Buat akun dan mulai kelola kas.</h1>
            <p class="mt-5 max-w-md leading-7 text-indigo-100">Daftar untuk mengakses dashboard dan fitur pengelolaan keuangan.</p>
        </div>
        <p class="relative text-sm text-indigo-200">Kasora · Pengelolaan Kas &amp; Anggaran</p>
    </section>
    <section class="flex items-center justify-center px-5 py-12 sm:px-10">
        <div class="w-full max-w-md auth-enter">
            <a href="login.php" class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-600 text-lg font-extrabold text-white">K</span>
                <span class="text-2xl font-extrabold tracking-tight">Kasora</span>
            </a>
            <div class="mb-7">
                <p class="mb-2 text-sm font-bold text-brand-600">BUAT AKUN BARU</p>
                <h2 class="text-3xl font-extrabold tracking-tight">Daftar Kasora</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Isi data akun Anda untuk memulai.</p>
            </div>
            <?php if ($error !== ''): ?>
                <div role="alert" class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" class="space-y-4" data-transition-message="Membuat akun dan menyiapkan dashboard...">
                <?= csrf_field() ?>
                <div>
                    <label for="nama" class="mb-2 block text-sm font-semibold">Nama</label>
                    <input id="nama" name="nama" type="text" maxlength="100" autocomplete="name" required value="<?= e($nama) ?>" placeholder="Nama lengkap"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label for="username" class="mb-2 block text-sm font-semibold">Username</label>
                    <input id="username" name="username" type="text" maxlength="40" minlength="3" pattern="[a-zA-Z0-9._\-]+" autocomplete="username" required value="<?= e($username) ?>" placeholder="Pilih username"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold">Email</label>
                    <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= e($email) ?>" placeholder="nama@email.com"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold">Kata sandi</label>
                    <input id="password" name="password" type="password" minlength="10" autocomplete="new-password" required placeholder="Minimal 10 karakter"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <div>
                    <label for="konfirmasi_password" class="mb-2 block text-sm font-semibold">Konfirmasi kata sandi</label>
                    <input id="konfirmasi_password" name="konfirmasi_password" type="password" minlength="10" autocomplete="new-password" required placeholder="Ulangi kata sandi"
                           class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-200 transition hover:bg-brand-700 focus:outline-none focus:ring-4 focus:ring-brand-100">Buat akun</button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-500">Sudah punya akun?
                <a href="login.php" class="font-bold text-brand-600 hover:text-brand-700">Masuk</a>
            </p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/transition.php'; ?>
<script src="assets/app.js" defer></script>
</body>
</html>
