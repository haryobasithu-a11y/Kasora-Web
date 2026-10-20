</main>

<footer class="mx-auto max-w-7xl px-4 pb-8 text-center text-xs text-slate-400 sm:px-6 lg:px-10">
    Kasora &copy; <?= date('Y') ?> &middot; Aplikasi Pengelolaan Kas &amp; Anggaran
</footer>
</div>

<nav class="mobile-app-nav lg:hidden" aria-label="Navigasi utama">
    <a href="index.php" class="mobile-app-nav__item <?= $halaman === 'dashboard' ? 'is-active' : '' ?>" <?= $halaman === 'dashboard' ? 'aria-current="page"' : '' ?>>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-6v-7h-4v7H4a1 1 0 0 1-1-1z"/></svg>
        <span>Home</span>
    </a>
    <a href="riwayat.php?jenis=masuk" class="mobile-app-nav__item <?= $halaman === 'masuk' ? 'is-active' : '' ?>" <?= $halaman === 'masuk' ? 'aria-current="page"' : '' ?>>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m-6 6 6-6 6 6"/><path stroke-linecap="round" d="M4 20h16"/></svg>
        <span>Masuk</span>
    </a>
    <a href="tambah.php" class="mobile-app-nav__add" aria-label="Catat transaksi">
        <span aria-hidden="true">+</span>
    </a>
    <a href="riwayat.php?jenis=keluar" class="mobile-app-nav__item <?= $halaman === 'keluar' ? 'is-active' : '' ?>" <?= $halaman === 'keluar' ? 'aria-current="page"' : '' ?>>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m6-6-6 6-6-6"/><path stroke-linecap="round" d="M4 4h16"/></svg>
        <span>Keluar</span>
    </a>
    <a href="profil.php" class="mobile-app-nav__item <?= $halaman === 'profil' ? 'is-active' : '' ?>" <?= $halaman === 'profil' ? 'aria-current="page"' : '' ?>>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5 20a7 7 0 0 1 14 0"/></svg>
        <span>Profil</span>
    </a>
</nav>

<?php require __DIR__ . '/transition.php'; ?>
<script src="assets/app.js" defer></script>
</body>
</html>