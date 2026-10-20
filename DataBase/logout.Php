<?php
/**
 * Kasora - Keluar dari sesi.
 */
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!csrf_valid($_POST['csrf'] ?? null)) {
    flash_set('error', 'Sesi tidak valid. Silakan coba lagi.');
    redirect('index.php');
}

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

session_destroy();
redirect('login.php');
