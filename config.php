<?php
define('APP_START', true);

// config.php - Modernized Bootstrapper
// Refactored by Antigravity (Phase 2)

// 1. Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Security Headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
// Note: Content-Type JSON dipindah ke Response class agar lebih fleksibel

// 2. Autoloader (PSR-4 Style Simple Implementation)
spl_autoload_register(function ($class) {
    // Prefix namespace
    $prefix = 'App\\';

    // Base directory untuk namespace prefix
    $base_dir = __DIR__ . '/app/';

    // Cek apakah class menggunakan prefix ini
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Ambil nama relative class
    $relative_class = substr($class, $len);

    // Ganti backslash dengan directory separator
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Jika file ada, require
    if (file_exists($file)) {
        require $file;
    }
});

// 3. Environment Loader
function loadEnv()
{
    $path = __DIR__ . '/env_settings.php';

    if (!file_exists($path)) {
        return; // Silent fail if file missing (e.g. in production using server ENV)
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false)
            continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
loadEnv();

// 4. Global Exception Handler
set_exception_handler(function ($e) {
    // Jika error berasal dari AppException, gunakan code yang ditentukan
    // Jika error system/php lain, gunakan 500 dan pesan error asli (untuk debug, nanti di production bisa disembunyikan)

    $code = ($e instanceof \App\Core\AppException) ? $e->getHttpCode() : 500;

    \App\Core\Response::json('error', $e->getMessage(), null, $code);
});

// 5. Helper Function Log (Legacy Support, bisa dipindah ke Log Service nanti)
function write_log($user_id, $username, $aksi)
{
    try {
        $conn = \App\Core\Database::getConnection();
        $endpoint = $_SERVER['REQUEST_URI'];
        $stmt = $conn->prepare("INSERT INTO logs (user_id, nama_user, aksi, endpoint) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $username, $aksi, $endpoint);
        $stmt->execute();
    } catch (Exception $e) {
        // Silent fail for logging errors 
    }
}
?>