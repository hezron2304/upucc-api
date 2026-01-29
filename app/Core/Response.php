<?php
namespace App\Core;

class Response
{
    public static function json($status, $message, $data = null, $code = 200)
    {
        http_response_code($code);
        header("Content-Type: application/json");
        echo json_encode([
            "status" => $status,
            "message" => $message,
            "data" => $data
        ]);
        exit;
    }
}
?>