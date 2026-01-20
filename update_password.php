<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// 1. KONEKSI DATABASE
$host = "localhost";
$user = "hezron";
$pass = "hezron";
$dbname = "hezron";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Koneksi Gagal"]));
}

// 2. CEK TOKEN (Sama seperti API lainnya)
$headers = array_change_key_case(getallheaders(), CASE_LOWER);
$token = $headers['authorization'] ?? ($_GET['Authorization'] ?? '');

if (empty($token)) {
    echo json_encode(["status" => "error", "message" => "Token tidak ditemukan"]);
    exit;
}

// Cari user berdasarkan token
$query_user = "SELECT id, password FROM anggota WHERE token = '$token' LIMIT 1";
$result_user = mysqli_query($conn, $query_user);
$user_login = mysqli_fetch_assoc($result_user);

if (!$user_login) {
    echo json_encode(["status" => "error", "message" => "Sesi tidak valid, silakan login ulang"]);
    exit;
}

// 3. LOGIKA UPDATE PASSWORD (METHOD PUT)
if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    
    // PHP tidak otomatis membaca data body pada method PUT, jadi kita ambil manual
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $password_lama = $_PUT['password_lama'] ?? '';
    $password_baru = $_PUT['password_baru'] ?? '';

    // Validasi input kosong
    if (empty($password_lama) || empty($password_baru)) {
        echo json_encode(["status" => "error", "message" => "Password lama dan baru wajib diisi"]);
        exit;
    }

    // Verifikasi apakah password lama benar
    if (password_verify($password_lama, $user_login['password'])) {
        
        // Hash password baru
        $hashed_baru = password_hash($password_baru, PASSWORD_BCRYPT);
        $user_id = $user_login['id'];

        // Update ke database
        $update_query = "UPDATE anggota SET password = '$hashed_baru' WHERE id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            echo json_encode([
                "status" => "success", 
                "message" => "Password berhasil diperbarui demi keamanan"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal memperbarui database"]);
        }

    } else {
        echo json_encode(["status" => "error", "message" => "Password lama salah"]);
    }

} else {
    echo json_encode(["status" => "error", "message" => "Method harus PUT"]);
}

mysqli_close($conn);
?>