<?php
require 'config.php';
use App\Controllers\PortofolioController;
use App\Core\Response;

try {
    $controller = new PortofolioController();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $controller->index();
            break;
        case 'POST':
            $controller->store();
            break;
        default:
            Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>