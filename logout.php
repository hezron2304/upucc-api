<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$host = "localhost";
$user = "hezron";
$pass = "hezron";
$dbname = "hezron";

$conn = mysqli_connect($host, $user, $pass, $dbname);

$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$token = $headers['authorization'] ?? ($_GET['Authorization'] ?? '');

if ($token) {
    mysqli_query($conn, "UPDATE anggota SET token = NULL WHERE token = '$token'");
    echo json_encode(["status" => "success", "message" => "Logout berhasil"]);
} else {
    echo json_encode(["status" => "error", "message" => "Token tidak ditemukan"]);
}
?>