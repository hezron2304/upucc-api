<?php
header("Content-Type: application/json");
http_response_code(403);
echo json_encode(["status" => "error", "message" => "Direct access not allowed"]);
exit;