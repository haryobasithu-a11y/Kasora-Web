<?php
/**
 * Kasora - Profil dan pengaturan keamanan akun.
 */
require_once __DIR__ . '/includes/helpers.php';
ensure_user_schema();
require_login();

$judul = 'Profil';
$halaman = 'profil';
$pdo = db();
$stmt = $pdo->prepare('SELECT id, nama, username, email, google_id, password_hash, created_at FROM pengguna WHERE id = ?');
$stmt->execute([$_SESSION['pengguna_id']]);
$pengguna = $stmt->fetch();

if (!$pengguna) {
    $_SESSION = [];
    session_destroy();
    redirect('login.Php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        flash_set('error', 'Sesi tidak valid. Silakan coba lagi.');
        redirect('profil.Php');
    }

    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'profil') {
        $nama = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        if ($nama === '' || strlen($nama) > 100) {
            flash_set('error', 'Nama wajib diisi dan maksimal 100 karakter.');
        } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,40}$/', $username)) {
            flash_set('error', 'Username harus 3–40 karakter dan hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.');
        } else {
            try {
                $stmt = $pdo->prepare('UPDATE pengguna SET nama = ?, username = ? WHERE id = ?');
                $stmt->execute([$nama, $username, $pengguna['id']]);
                $_SESSION['pengguna_nama'] = $nama;
                flash_set('sukses', 'Profil berhasil diperbarui.');
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') {
                    flash_set('error', 'Username tersebut sudah digunakan.');
                } else {
                    throw $exception;
                }
            }
        }
    } elseif ($aksi === 'password') {
        $current = $_POST['kata_sandi_lama'] ?? '';
        $new = $_POST['kata_sandi_baru'] ?? '';
        $confirmation = $_POST['konfirmasi_kata_sandi'] ?? '';
        if ($pengguna['password_hash'] !== null && !password_verify($current, $pengguna['password_hash'])) {
            flash_set('error', 'Kata sandi saat ini tidak sesuai.');
        } elseif (strlen($new) < 10) {
            flash_set('error', 'Kata sandi baru harus terdiri dari minimal 10 karakter.');
        } elseif ($new !== $confirmation) {
            flash_set('error', 'Konfirmasi kata sandi baru tidak sama.');
        } else {
            $stmt = $pdo->prepare('UPDATE pengguna SET password_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($new, PASSWORD_DEFAULT), $pengguna['id']]);
            flash_set('sukses', 'Kata sandi berhasil diperbarui.');
        }
    } else {
        flash_set('error', 'Permintaan profil tidak valid.');
    }
    redirect('profil.Php');
}

require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto max-w-3xl">
    <div class="mb-7">
        <p class="text-sm font-bold uppercase tracking-wider text-brand-600">Pengaturan akun</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight">Profil Anda</h1>
        <p class="mt-2 text-sm text-slate-500">Kelola informasi akun dan keamanan kata sandi.</p>
    </div>

    <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex items-center gap-4 bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white">
            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-white/15 text-2xl font-extrabold"><?= e(strtoupper(substr($pengguna['nama'], 0, 1))) ?></span>
            <div>
                <h2 class="text-xl font-extrabold"><?= e($pengguna['nama']) ?></h2>
                <p class="text-sm text-indigo-100"><?= e($pengguna['email'] ?? $pengguna['username']) ?></p>
            </div>
        </div>
        <form method="post" class="space-y-5 p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="profil">
            <div>
                <label for="nama" class="mb-2 block text-sm font-semibold">Nama</label>
                <input id="nama" name="nama" type="text" maxlength="100" required value="<?= e($pengguna['nama']) ?>"
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label for="username" class="mb-2 block text-sm font-semibold">Username</label>
                <input id="username" name="username" type="text" minlength="3" maxlength="40" pattern="[a-zA-Z0-9._\-]+" required value="<?= e($pengguna['username']) ?>"
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label for="email" class="mb-2 block text-sm font-semibold">Email</label>
                <input id="email" type="email" readonly value="<?= e($pengguna['email'] ?? 'Belum ditautkan') ?>"
                       class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                <p class="mt-1 text-xs text-slate-400">Email terverifikasi Google atau email pendaftaran tidak dapat diubah di sini.</p>
            </div>
            <button type="submit" class="rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-brand-700">Simpan profil</button>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-extrabold">Keamanan</h2>
        <p class="mt-1 text-sm text-slate-500"><?= $pengguna['password_hash'] === null ? 'Tambahkan kata sandi agar akun Google juga dapat digunakan untuk masuk dengan username/email.' : 'Perbarui kata sandi akun Anda.' ?></p>
        <form method="post" class="mt-5 space-y-5">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="password">
            <?php if ($pengguna['password_hash'] !== null): ?>
                <div>
                    <label for="kata_sandi_lama" class="mb-2 block text-sm font-semibold">Kata sandi saat ini</label>
                    <input id="kata_sandi_lama" name="kata_sandi_lama" type="password" autocomplete="current-password" required
                           class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                </div>
            <?php endif; ?>
            <div>
                <label for="kata_sandi_baru" class="mb-2 block text-sm font-semibold">Kata sandi baru</label>
                <input id="kata_sandi_baru" name="kata_sandi_baru" type="password" minlength="10" autocomplete="new-password" required
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
            <div>
                <label for="konfirmasi_kata_sandi" class="mb-2 block text-sm font-semibold">Konfirmasi kata sandi baru</label>
                <input id="konfirmasi_kata_sandi" name="konfirmasi_kata_sandi" type="password" minlength="10" autocomplete="new-password" required
                       class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </div>
            <button type="submit" class="rounded-xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Perbarui kata sandi</button>
        </form>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
