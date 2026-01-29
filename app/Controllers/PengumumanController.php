<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class PengumumanController extends BaseController
{

    public function index()
    {
        // GET: List Pengumuman (Public/Auth)
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        // Join with creator name if needed, but basic select is fine
        $query = "SELECT p.*, a.username as pembuat FROM pengumuman p LEFT JOIN anggota a ON p.created_by = a.id ORDER BY p.created_at DESC";
        $res = mysqli_query($conn, $query);

        Response::json('success', 'Data Pengumuman', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    public function store()
    {
        // POST: Create (Admin or Humas)
        $this->mustBeAuthenticated();

        // Cek Role: Admin, Super Admin, Humas
        $allowed = ['Admin', 'Super Admin', 'Humas'];
        // Note: Asumsi 'Humas' ada di kolom jabatan.
        if (!in_array($this->user['jabatan'], $allowed)) {
            // Jika logika 'divisi' terpisah dari 'jabatan', perlu query join table divisi. 
            // Tapi untuk simplicity sesuai request user "Role Humas", kita anggap jabatan.
            throw new AppException("Akses ditolak (Hanya Admin/Humas)", 403);
        }

        $this->validate([
            'judul' => 'required',
            'deskripsi' => 'required'
        ]);

        $judul = $this->input('judul');
        $desk = $this->input('deskripsi');
        $cover = $this->input('gambar_cover', ''); // Link or base64

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO pengumuman (judul, deskripsi, gambar_cover, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $judul, $desk, $cover, $this->user['id']);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Membuat Pengumuman: $judul");
            Response::json('success', 'Pengumuman diterbitkan', null, 201);
        } else {
            throw new AppException("Gagal membuat pengumuman", 500);
        }
    }

    public function destroy()
    {
        // DELETE: Hapus (Admin or Humas)
        $this->mustBeAuthenticated();

        $allowed = ['Admin', 'Super Admin', 'Humas'];
        if (!in_array($this->user['jabatan'], $allowed)) {
            throw new AppException("Akses ditolak", 403);
        }

        $id = $this->input('id');
        if (!$id)
            throw new AppException("ID required", 400);

        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM pengumuman WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Menghapus Pengumuman ID: $id");
            Response::json('success', 'Pengumuman dihapus');
        } else {
            throw new AppException("Gagal menghapus pengumuman", 500);
        }
    }
}
?>