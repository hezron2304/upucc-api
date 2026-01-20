<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

$host = "localhost";
$user = "hezron";
$pass = "hezron";
$dbname = "hezron";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Koneksi Gagal"]));
}

// 1. FUNGSI CEK TOKEN (Wajib Login)
function get_user_from_token($conn) {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $token = $headers['authorization'] ?? ($_GET['Authorization'] ?? '');

    if (empty($token)) {
        echo json_encode(["status" => "error", "message" => "Silakan login terlebih dahulu"]);
        exit;
    }
    
    $query = "SELECT id, nama, jabatan FROM anggota WHERE token = '$token' LIMIT 1";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

$user_login = get_user_from_token($conn);
if (!$user_login) {
    echo json_encode(["status" => "error", "message" => "Sesi habis, silakan login ulang"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    // GET: MENAMPILKAN SEMUA DATA
    case 'GET':
        // Jika ada parameter ID di URL (contoh: anggota.php?id=5)
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $query = "SELECT id, nama, email, prodi, divisi, angkatan, jabatan FROM anggota WHERE id = $id";
        } else {
            // TAMPILKAN SEMUA DATA (Tanpa melihat jabatan Admin atau bukan)
            // Kita tidak menarik kolom 'password' dan 'token' demi keamanan
            $query = "SELECT id, nama, email, prodi, divisi, angkatan, jabatan FROM anggota ORDER BY nama ASC";
        }
        
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        echo json_encode([
            "status" => "success",
            "message" => "Data berhasil dimuat",
            "total_data" => count($data),
            "data" => $data
        ]);
        break;

    // POST, PUT, DELETE (Tetap gunakan proteksi Admin jika diperlukan)
    case 'POST':
        if ($user_login['jabatan'] != 'Admin') {
            echo json_encode(["status" => "error", "message" => "Hanya Admin yang bisa menambah anggota"]);
            break;
        }
        // ... kode tambah data anda ...
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Method tidak didukung"]);
        break;
}

mysqli_close($conn);
?>