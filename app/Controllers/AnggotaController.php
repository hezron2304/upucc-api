<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class AnggotaController extends BaseController
{

    public function index()
    {
        // GET: List Anggota
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        $res = mysqli_query($conn, "SELECT id, username, email, prodi, divisi, angkatan, jabatan FROM anggota ORDER BY username ASC");

        Response::json('success', 'Data Anggota', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    public function store()
    {
        // POST: Tambah Anggota (Admin only)
        $this->mustBeAdmin();

        $username = $this->input('username');
        $email = $this->input('email');
        $jabatan = $this->input('jabatan', 'Anggota');

        if (empty($username) || empty($email)) {
            throw new AppException("Username dan Email wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO anggota (username, email, jabatan) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $jabatan);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Menambah anggota: $username");
            Response::json('success', 'Anggota berhasil ditambah', null, 201);
        } else {
            throw new AppException("Gagal menambah anggota " . $stmt->error, 500);
        }
    }

    public function destroy()
    {
        // DELETE: Hapus Anggota
        $this->mustBeAdmin();
        $id = $this->input('id');

        if (!$id) {
            throw new AppException("ID Anggota wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM anggota WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Menghapus anggota ID: $id");
            Response::json('success', 'Anggota dihapus');
        } else {
            throw new AppException("Gagal menghapus anggota", 500);
        }
    }
}
?>