<?php
require 'config.php';

use App\Controllers\AuthController;
use App\Core\Response;

// Routes to AuthController->login()
try {
    $auth = new AuthController();
    $auth->login();
} catch (Exception $e) {
    // Fallback jika global handler di config.php gagal atau belum terset
    Response::json('error', $e->getMessage(), null, 500);
}
?>