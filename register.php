<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$user = "hezron";
$pass = "hezron";
$dbname = "hezron";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? '';
    $email = $_POST['email'] ?? ''; // Menggunakan email sesuai tabel
    $password = $_POST['password'] ?? '';
    $prodi = $_POST['prodi'] ?? '';
    $divisi = $_POST['divisi'] ?? '';
    $angkatan = $_POST['angkatan'] ?? '';
    $jabatan = $_POST['jabatan'] ?? 'Anggota';

    if (empty($nama) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Nama, Email, dan Password wajib diisi"]);
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Query disesuaikan dengan nama kolom 'email'
    $query = "INSERT INTO anggota (nama, email, password, prodi, divisi, angkatan, jabatan) 
              VALUES ('$nama', '$email', '$hashed_password', '$prodi', '$divisi', '$angkatan', '$jabatan')";

    if (mysqli_query($conn, $query)) {
        echo json_encode(["status" => "success", "message" => "Berhasil mendaftar"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Email sudah terdaftar atau error: " . mysqli_error($conn)]);
    }
}
mysqli_close($conn);
?>