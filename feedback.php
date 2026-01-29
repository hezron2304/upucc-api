<?php
require 'config.php';
use App\Controllers\FeedbackController;
use App\Core\Response;

try {
    $controller = new FeedbackController();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $controller->indexFeedback();
            break;
        case 'POST':
            $controller->createFeedback();
            break;
        default:
            Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>