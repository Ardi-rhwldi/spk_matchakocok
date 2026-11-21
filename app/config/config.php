<?php
// app/config/config.php

if (php_sapi_name() !== 'cli') {
    if (session_status() === PHP_SESSION_NONE) {
        $sessDir = __DIR__ . '/../../sessions';
        if (!is_dir($sessDir)) {
            @mkdir($sessDir, 0777, true);
        }
        if (is_writable($sessDir)) {
            session_save_path($sessDir);
        }

        // Sesi berakhir otomatis setelah tidak aktif (default 120 menit) - FR-1.3
        $sessionLifetime = 7200; // 120 menit
        ini_set('session.gc_maxlifetime', $sessionLifetime);
        session_set_cookie_params([
            'lifetime' => $sessionLifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    }

    // Timeout handler jika tidak aktif > 120 menit
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_error'] = 'Sesi Anda telah berakhir karena tidak aktif selama 120 menit. Silakan login kembali.';
    }
    $_SESSION['last_activity'] = time();

    // Helper CSRF Token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

// Pengaturan Konfigurasi Aplikasi
define('APP_NAME', 'SPK Bundling Matcha Kocok');
define('APP_VERSION', '1.0.0');
define('APP_INSTITUTION', 'STIKOM El Rahma');
define('APP_STUDY_PROGRAM', 'Informatika');
define('APP_AUTHOR', 'Ardi Rihwaldi (NIM 2411070009)');
define('APP_OBJECT', 'Matcha Kocok Cidahu, Kab. Sukabumi');
// Deteksi BASE_URL secara dinamis (mendukung Apache XAMPP maupun PHP Built-in Server)
if (!defined('BASE_URL')) {
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $scriptDir = dirname($scriptName);

    if (strpos($reqUri, '/spk_matchakocok') === 0 || strpos($scriptName, '/spk_matchakocok') === 0) {
        define('BASE_URL', '/spk_matchakocok');
    } else {
        $base = ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') ? '' : rtrim(str_replace('\\', '/', $scriptDir), '/');
        define('BASE_URL', $base);
    }
}

// Pengaturan Database
define('DB_DRIVER', 'mysql'); // 'mysql' atau 'sqlite'
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'spk_matchakocok');
define('DB_USER', 'root');
define('DB_PASS', '');

// Path SQLite fallback jika MySQL tidak aktif
define('SQLITE_FILE', __DIR__ . '/../../database/spk_matchakocok.sqlite');

function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function format_decimal($angka, $decimals = 4) {
    return number_format((float)$angka, $decimals, ',', '.');
}

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function set_flash($type, $message) {
    $_SESSION['flash_' . $type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash_' . $type])) {
        $msg = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $msg;
    }
    return null;
}
