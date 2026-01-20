<?php
// Aktifkan laporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Authorization, Content-Type");

// 1. KONEKSI DATABASE
$host = "localhost";
$user = "hezron";
$pass = "hezron";
$dbname = "hezron";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Koneksi Database Gagal: " . mysqli_connect_error()]);
    exit;
}

// 2. FUNGSI CEK TOKEN (Lebih Kuat)
function get_user_from_token($conn) {
    $token = "";
    
    // Cek di Header Authorization
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    if (isset($headers['authorization'])) {
        $token = $headers['authorization'];
    } 
    // Cek di URL Parameter
    elseif (isset($_GET['Authorization'])) {
        $token = $_GET['Authorization'];
    }

    if (empty($token)) {
        echo json_encode(["status" => "error", "message" => "Token tidak ditemukan. Kirim token lewat Header 'Authorization' atau URL ?Authorization="]);
        exit;
    }
    
    // Cari user berdasarkan token
    $query = "SELECT id, nama, jabatan FROM anggota WHERE token = '$token' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        echo json_encode(["status" => "error", "message" => "Query Token Gagal: " . mysqli_error($conn)]);
        exit;
    }
    
    return mysqli_fetch_assoc($result);
}

// Validasi Login
$user_login = get_user_from_token($conn);
if (!$user_login) {
    echo json_encode(["status" => "error", "message" => "Token tidak valid atau tidak terdaftar di database"]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Ambil data kas
        $query = "SELECT kas.*, anggota.nama as pencatat 
                  FROM kas 
                  LEFT JOIN anggota ON kas.id_anggota = anggota.id 
                  ORDER BY kas.tanggal DESC";
        
        $result = mysqli_query($conn, $query);
        if (!$result) {
            echo json_encode(["status" => "error", "message" => "Gagal ambil data kas: " . mysqli_error($conn)]);
            break;
        }

        $data = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // Hitung Saldo
        $q_saldo = "SELECT 
                    SUM(CASE WHEN tipe = 'Masuk' THEN jumlah ELSE 0 END) as total_masuk,
                    SUM(CASE WHEN tipe = 'Keluar' THEN jumlah ELSE 0 END) as total_keluar
                    FROM kas";
        $res_saldo = mysqli_query($conn, $q_saldo);
        $saldo = mysqli_fetch_assoc($res_saldo);

        $total_masuk = $saldo['total_masuk'] ?? 0;
        $total_keluar = $saldo['total_keluar'] ?? 0;

        echo json_encode([
            "status" => "success",
            "user_akses" => $user_login['nama'],
            "total_saldo" => $total_masuk - $total_keluar,
            "ringkasan" => [
                "masuk" => (int)$total_masuk,
                "keluar" => (int)$total_keluar
            ],
            "data" => $data
        ]);
        break;

    case 'POST':
        if ($user_login['jabatan'] != 'Admin') {
            echo json_encode(["status" => "error", "message" => "Akses ditolak. Hanya Admin yang bisa input kas!"]);
            break;
        }

        // Ambil data dari POST
        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $keterangan = $_POST['keterangan'] ?? '';
        $tipe = $_POST['tipe'] ?? ''; // Masuk / Keluar
        $jumlah = $_POST['jumlah'] ?? 0;
        $id_anggota = $user_login['id'];

        if (empty($keterangan) || empty($tipe) || $jumlah <= 0) {
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap (keterangan, tipe, jumlah wajib diisi)"]);
            break;
        }

        $query = "INSERT INTO kas (tanggal, keterangan, tipe, jumlah, id_anggota) 
                  VALUES ('$tanggal', '$keterangan', '$tipe', '$jumlah', '$id_anggota')";
        
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "Berhasil mencatat kas"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal simpan: " . mysqli_error($conn)]);
        }
        break;

    case 'DELETE':
        if ($user_login['jabatan'] != 'Admin') {
            echo json_encode(["status" => "error", "message" => "Akses ditolak"]);
            break;
        }

        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'] ?? '';

        if (empty($id)) {
            echo json_encode(["status" => "error", "message" => "ID Kas wajib dikirim"]);
            break;
        }

        $query = "DELETE FROM kas WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            echo json_encode(["status" => "success", "message" => "Data kas dihapus"]);
        } else {
            echo json_encode(["status" => "error", "message" => mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Method tidak dikenal"]);
        break;
}

mysqli_close($conn);
?>