<?php
require 'config.php';
use App\Controllers\MateriController;
use App\Core\Response;

try {
    $controller = new MateriController();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $controller->index();
            break;
        case 'POST':
            $controller->store();
            break;
        case 'PUT':
            $controller->updateStatus();
            break; // New Endpoint
        case 'DELETE':
            $controller->destroy();
            break;
        default:
            Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>