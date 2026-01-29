<?php
require 'config.php';
use App\Controllers\LogController;
use App\Core\Response;

try {
    $controller = new LogController();
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $controller->index();
    } else {
        Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>