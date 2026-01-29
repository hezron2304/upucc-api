<?php
require 'config.php';
use App\Controllers\FeedbackController;
use App\Core\Response;

try {
    $controller = new FeedbackController(); // Voting uses FeedbackController
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            $controller->indexVoting();
            break;
        case 'POST':
            $controller->vote();
            break;
        default:
            Response::json('error', 'Method not allowed', null, 405);
    }
} catch (Exception $e) {
    Response::json('error', $e->getMessage(), null, 500);
}
?>