<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class AuthController
{

    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new AppException("Method not allowed", 405);
        }

        $identifier = $_POST['username'] ?? ($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($identifier) || empty($password)) {
            throw new AppException("Email/Username dan Password wajib diisi", 400);
        }

        // Coba Login
        $user = Auth::attempt($identifier, $password);

        if ($user) {
            // Login Berhasil
            $payload = [
                "id" => $user['id'],
                "username" => $user['username'],
                "exp" => time() + (3600 * 24) // 24 Jam
            ];

            $token = Auth::createToken($payload);

            // Log manual (karena write_log ada di global/config, bisa dipanggil langsung atau better via Helper class)
            write_log($user['id'], $user['username'], "Login Berhasil");

            Response::json('success', "Selamat datang, " . $user['nama'] . "! Login berhasil.", [
                "token" => $token,
                "user" => [
                    "nama" => $user['nama'],
                    "username" => $user['username'],
                    "jabatan" => $user['jabatan']
                ]
            ]);
        } else {
            // Login Gagal
            // Kita query user by identifier dulu untuk log (opsional, tapi bagus untuk security audit)
            $conn = Database::getConnection();
            $stmt = $conn->prepare("SELECT id, username FROM anggota WHERE email = ? OR username = ? LIMIT 1");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $u = $stmt->get_result()->fetch_assoc();

            $id_log = $u ? $u['id'] : 0;
            write_log($id_log, $identifier, "Login Gagal: Password salah atau user tidak ditemukan");

            throw new AppException("Email/Username atau Password salah!", 401);
        }
    }

    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new AppException("Method not allowed", 405);
        }

        $nama = $_POST['nama'] ?? '';
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $prodi = $_POST['prodi'] ?? '';
        $divisi = $_POST['divisi'] ?? '';
        $angkatan = $_POST['angkatan'] ?? date('Y');

        if (empty($nama) || empty($username) || empty($email) || empty($password)) {
            throw new AppException("Data tidak lengkap (Nama, Username, Email, Password wajib)", 400);
        }

        $conn = Database::getConnection();

        // Cek Duplikasi
        $stmt_cek = $conn->prepare("SELECT id FROM anggota WHERE email = ? OR username = ?");
        $stmt_cek->bind_param("ss", $email, $username);
        $stmt_cek->execute();

        if ($stmt_cek->get_result()->num_rows > 0) {
            throw new AppException("Username atau Email sudah terdaftar", 409);
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $jabatan = 'Anggota';

        $stmt = $conn->prepare("INSERT INTO anggota (nama, username, email, password, prodi, divisi, angkatan, jabatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $nama, $username, $email, $hashed_password, $prodi, $divisi, $angkatan, $jabatan);

        if ($stmt->execute()) {
            write_log($conn->insert_id, $username, "Mendaftar sebagai anggota baru");
            Response::json('success', 'Pendaftaran berhasil, silakan login', null, 201);
        } else {
            throw new AppException("Gagal menyimpan data", 500);
        }
    }

    public function logout()
    {
        // Client side just drops token, server side logs it
        $user = Auth::user();
        if ($user) {
            write_log($user['id'], $user['username'], "Melakukan Logout");
        }
        Response::json('success', 'Berhasil logout. Hapus token Anda.');
    }
}
?>