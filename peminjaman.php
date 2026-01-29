<?php
require 'config.php';
use App\Controllers\InventoryController;
use App\Core\Response;

try {
    $controller = new InventoryController(); // Peminjaman uses InventoryController
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $controller->indexTransactions();
            break;
        case 'POST':
            $controller->borrow();
            break;
        case 'PUT':
            $controller->returnItem();
            break;
        default:
            Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>