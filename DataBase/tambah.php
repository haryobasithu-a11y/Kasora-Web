<?php
/**
 * Kasora - Form pencatatan transaksi (kas masuk / kas keluar).
 */
$judul   = 'Catat Transaksi';
$halaman = 'tambah';
require_once __DIR__ . '/includes/helpers.php';
require_login();

// Ambil input lama bila validasi gagal
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

$jenis      = $old['jenis']      ?? 'masuk';
$kategori   = $old['kategori']   ?? '';
$nominal    = $old['nominal']    ?? '';
$tanggal    = $old['tanggal']    ?? date('Y-m-d');
$keterangan = $old['keterangan'] ?? '';

require __DIR__ . '/includes/header.php';
?>

<div class="mx-auto max-w-2xl">
    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Catat Transaksi</h1>
    <p class="mb-6 text-sm text-slate-500">Isi data kas masuk atau kas keluar dengan lengkap.</p>

    <form action="simpan.php" method="post" autocomplete="off"
          class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <?= csrf_field() ?>

        <!-- Jenis -->
        <fieldset>
            <legend class="mb-2 text-sm font-semibold">Jenis Transaksi</legend>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="jenis" value="masuk" class="peer sr-only" <?= $jenis === 'masuk' ? 'checked' : '' ?>>
                    <div class="rounded-xl border-2 border-slate-200 px-4 py-3 text-center text-sm font-bold text-slate-500 transition
                                peer-checked:border-emerald-500 peer-checked:bg-emerald-50 peer-checked:text-emerald-700
                                peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-400">
                        ↓ Kas Masuk
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="jenis" value="keluar" class="peer sr-only" <?= $jenis === 'keluar' ? 'checked' : '' ?>>
                    <div class="rounded-xl border-2 border-slate-200 px-4 py-3 text-center text-sm font-bold text-slate-500 transition
                                peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700
                                peer-focus-visible:ring-2 peer-focus-visible:ring-rose-400">
                        ↑ Kas Keluar
                    </div>
                </label>
            </div>
        </fieldset>

        <!-- Nominal -->
        <div>
            <label for="nominal" class="mb-2 block text-sm font-semibold">Nominal</label>
            <div class="flex overflow-hidden rounded-xl border border-slate-300 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-100">
                <span class="grid place-items-center bg-slate-50 px-4 text-sm font-bold text-slate-500">Rp</span>
                <input type="number" id="nominal" name="nominal" min="1" step="1" inputmode="numeric" required
                       value="<?= e($nominal) ?>" placeholder="0"
                       class="w-full px-4 py-3 text-lg font-bold outline-none">
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <!-- Kategori -->
            <div>
                <label for="kategori" class="mb-2 block text-sm font-semibold">Kategori</label>
                <select id="kategori" name="kategori" required
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
                    <option value="" disabled <?= $kategori === '' ? 'selected' : '' ?>>Pilih kategori</option>
                    <?php foreach (KATEGORI as $k): ?>
                        <option value="<?= e($k) ?>" <?= $kategori === $k ? 'selected' : '' ?>><?= e($k) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Tanggal -->
            <div>
                <label for="tanggal" class="mb-2 block text-sm font-semibold">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" required value="<?= e($tanggal) ?>"
                       class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100">
            </div>
        </div>

        <!-- Keterangan -->
        <div>
            <label for="keterangan" class="mb-2 block text-sm font-semibold">
                Keterangan <span class="font-normal text-slate-400">(opsional)</span>
            </label>
            <textarea id="keterangan" name="keterangan" rows="3" maxlength="255"
                      placeholder="Contoh: Snack rapat bulanan"
                      class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100"><?= e($keterangan) ?></textarea>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="index.php" class="rounded-xl border border-slate-300 px-6 py-3 text-center text-sm font-semibold text-slate-600 hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-xl bg-brand-600 px-6 py-3 text-sm font-bold text-white shadow-sm hover:bg-brand-700">
                Simpan Transaksi
            </button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>