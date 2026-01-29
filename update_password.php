<?php
require 'config.php';
use App\Controllers\ProfileController;
use App\Core\Response;

try {
    $controller = new ProfileController();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method == 'PUT' || $method == 'POST') {
        $controller->updatePassword();
    } else {
        Response::json('error', 'Method not allowed (Use PUT/POST)', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>