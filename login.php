<?php
// Aktifkan error reporting untuk melihat kesalahan asli
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

// 1. KONEKSI DATABASE
$host = "localhost";
$user = "hezron";
$pass = "hezron";
$dbname = "hezron";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Koneksi DB Gagal: " . mysqli_connect_error()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil input 'email' dari HTML (sesuai id di login.html)
    $email = $_POST['email'] ?? ''; 
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Email dan Password wajib diisi"]);
        exit;
    }

    // 2. QUERY DISESUAIKAN (Hanya kolom 'email' karena tidak ada kolom 'username')
    $query = "SELECT * FROM anggota WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        // Jika query error, tampilkan pesan errornya
        echo json_encode(["status" => "error", "message" => "Query Error: " . mysqli_error($conn)]);
        exit;
    }

    $user_data = mysqli_fetch_assoc($result);

    if ($user_data && password_verify($password, $user_data['password'])) {
        // Generate Token
        $newToken = bin2hex(random_bytes(16));
        
        // Simpan token baru ke database
        $update_token = mysqli_query($conn, "UPDATE anggota SET token = '$newToken' WHERE id = " . $user_data['id']);

        if($update_token) {
            echo json_encode([
                "status" => "success",
                "token" => $newToken,
                "user" => [
                    "nama" => $user_data['nama'],
                    "jabatan" => $user_data['jabatan']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan token ke database"]);
        }
        
    } else {
        echo json_encode(["status" => "error", "message" => "Email atau Password salah"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Method bukan POST"]);
}

mysqli_close($conn);
?>