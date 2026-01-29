<?php
require 'config.php';
use App\Controllers\DivisiController;
use App\Core\Response;

try {
    $controller = new DivisiController();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'GET') {
        $controller->index();
    } else {
        Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>