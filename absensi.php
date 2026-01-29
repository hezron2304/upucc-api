<?php
require 'config.php';
use App\Controllers\AbsensiController;
use App\Core\Response;
use App\Core\AppException;

try {
    $controller = new AbsensiController();
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
    // Gunakan Status Code dari Exception jika ada (misal 400, 401, 404), default 500.
    $code = ($e instanceof \App\Core\AppException) ? $e->getHttpCode() : 500;
    Response::json('error', $e->getMessage(), null, $code);
}
?>