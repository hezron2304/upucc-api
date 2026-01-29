<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class ProfileController extends BaseController
{

    public function index()
    {
        // GET: My Profile
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT id, username, email, prodi, divisi, angkatan, jabatan FROM anggota WHERE id = ?");
        $stmt->bind_param("i", $this->user['id']);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        Response::json('success', 'Profile Data', $data);
    }

    public function update()
    {
        // POST: Update Profile
        $this->mustBeAuthenticated();

        $email = $this->input('email');
        $prodi = $this->input('prodi');

        if (empty($email) || empty($prodi)) {
            throw new AppException("Email dan Prodi wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE anggota SET email = ?, prodi = ? WHERE id = ?");
        $stmt->bind_param("ssi", $email, $prodi, $this->user['id']);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Memperbarui data profil");
            Response::json('success', 'Profil berhasil diperbarui');
        } else {
            throw new AppException("Gagal memperbarui profil", 500);
        }
    }

    public function updatePassword()
    {
        // PUT: Change Password
        $this->mustBeAuthenticated();

        $pass_lama = $this->input('password_lama');
        $pass_baru = $this->input('password_baru');

        if (empty($pass_lama) || empty($pass_baru)) {
            throw new AppException("Password lama dan baru wajib diisi", 400);
        }

        $conn = Database::getConnection();

        // Verifikasi pass lama
        $stmt = $conn->prepare("SELECT password FROM anggota WHERE id = ?");
        $stmt->bind_param("i", $this->user['id']);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        if (password_verify($pass_lama, $data['password'])) {
            $hash = password_hash($pass_baru, PASSWORD_BCRYPT);
            $up = $conn->prepare("UPDATE anggota SET password = ? WHERE id = ?");
            $up->bind_param("si", $hash, $this->user['id']);

            if ($up->execute()) {
                write_log($this->user['id'], $this->user['username'], "Ganti Password");
                Response::json('success', 'Password diperbarui');
            } else {
                throw new AppException("Gagal update password DB", 500);
            }
        } else {
            throw new AppException("Password lama salah", 401);
        }
    }
}
?>