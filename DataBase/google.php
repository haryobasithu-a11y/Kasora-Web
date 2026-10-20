<?php
/**
 * Kasora - Google OAuth login and callback.
 */
require_once __DIR__ . '/includes/helpers.php';
ensure_user_schema();

if (empty($_GET['code']) && !isset($_GET['error']) && !empty($_SESSION['pengguna_id'])) {
    redirect('index.php');
}

$clientId = getenv('KASORA_GOOGLE_CLIENT_ID');
$clientSecret = getenv('KASORA_GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('KASORA_GOOGLE_REDIRECT_URI');

if (!$clientId || !$clientSecret || !$redirectUri) {
    flash_set('error', 'Google Login belum dikonfigurasi. Atur Client ID, Client Secret, dan Redirect URI OAuth.');
    redirect('login.php');
}

if (!empty($_GET['code'])) {
    $state = $_GET['state'] ?? '';
    if (!is_string($state) || empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
        unset($_SESSION['google_oauth_state']);
        flash_set('error', 'Verifikasi Google tidak valid atau sudah kedaluwarsa. Silakan coba lagi.');
        redirect('login.php');
    }
    unset($_SESSION['google_oauth_state']);

    if (isset($_GET['error'])) {
        flash_set('error', 'Login Google dibatalkan atau tidak berhasil.');
        redirect('login.php');
    }

    $curl = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'code' => $_GET['code'],
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $tokenResponse = curl_exec($curl);
    $tokenStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($tokenResponse === false || $tokenStatus !== 200) {
        error_log('Google OAuth token request failed (HTTP ' . $tokenStatus . '): ' . $curlError);
        flash_set('error', 'Tidak dapat menyelesaikan login Google. Silakan coba lagi.');
        redirect('login.php');
    }
    $token = json_decode($tokenResponse, true);
    if (!is_array($token) || empty($token['access_token'])) {
        error_log('Google OAuth token response did not contain an access token.');
        flash_set('error', 'Respons login Google tidak valid. Silakan coba lagi.');
        redirect('login.php');
    }

    $curl = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token['access_token']],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    $profileResponse = curl_exec($curl);
    $profileStatus = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($profileResponse === false || $profileStatus !== 200) {
        error_log('Google OAuth userinfo request failed (HTTP ' . $profileStatus . '): ' . $curlError);
        flash_set('error', 'Tidak dapat mengambil profil Google. Silakan coba lagi.');
        redirect('login.php');
    }
    $googleUser = json_decode($profileResponse, true);
    if (!is_array($googleUser)
        || empty($googleUser['sub'])
        || empty($googleUser['email'])
        || filter_var($googleUser['email'], FILTER_VALIDATE_EMAIL) === false
        || ($googleUser['email_verified'] ?? false) !== true
    ) {
        flash_set('error', 'Akun Google tidak memiliki email terverifikasi yang valid.');
        redirect('login.php');
    }

    $email = strtolower($googleUser['email']);
    $stmt = db()->prepare('SELECT id, nama, username, email, google_id FROM pengguna WHERE google_id = ? LIMIT 1');
    $stmt->execute([$googleUser['sub']]);
    $pengguna = $stmt->fetch();

    if (!$pengguna) {
        $stmt = db()->prepare('SELECT id, nama, username, email, google_id FROM pengguna WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $pengguna = $stmt->fetch();

        try {
            if ($pengguna) {
                if ($pengguna['google_id'] !== null && $pengguna['google_id'] !== $googleUser['sub']) {
                    flash_set('error', 'Email Google ini sudah terhubung ke akun yang berbeda.');
                    redirect('login.php');
                }
                $stmt = db()->prepare('UPDATE pengguna SET google_id = ? WHERE id = ?');
                $stmt->execute([$googleUser['sub'], $pengguna['id']]);
            } else {
                $base = preg_replace('/[^a-zA-Z0-9._-]/', '', strstr($email, '@', true) ?: 'google');
                $base = substr($base, 0, 30);
                if (strlen($base) < 3) {
                    $base = 'google' . $base;
                }
                $username = $base;
                $suffix = 1;
                $stmt = db()->prepare('SELECT 1 FROM pengguna WHERE username = ?');
                while (true) {
                    $stmt->execute([$username]);
                    if (!$stmt->fetchColumn()) {
                        break;
                    }
                    $username = substr($base, 0, 34) . $suffix++;
                }
                $nama = trim($googleUser['name'] ?? '');
                if ($nama === '') {
                    $nama = $username;
                }
                $stmt = db()->prepare(
                    'INSERT INTO pengguna (nama, username, email, google_id) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$nama, $username, $email, $googleUser['sub']]);
                $pengguna = [
                    'id' => db()->lastInsertId(),
                    'nama' => $nama,
                ];
            }
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                flash_set('error', 'Akun Google ini sudah terdaftar. Silakan masuk menggunakan metode yang terhubung.');
                redirect('login.php');
            }
            throw $exception;
        }
    }

    login_user($pengguna);
    redirect('index.php');
}

if (isset($_GET['error'])) {
    flash_set('error', 'Login Google dibatalkan atau tidak berhasil.');
    redirect('login.php');
}

$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$authorizationUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account',
]);
redirect($authorizationUrl);
