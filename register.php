<?php
require 'config.php';

use App\Controllers\AuthController;
use App\Core\Response;

// Routes to AuthController->register()
try {
    $auth = new AuthController();
    $auth->register();
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>