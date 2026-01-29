<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class MateriController extends BaseController
{

    public function index()
    {
        // GET: List Materi
        // Logic Baru:
        // - Admin/Humas: Lihat SEMUA (Pending, ACC, Reject).
        // - Anggota biasa: Hanya lihat 'ACC'

        $isAdminOrHumas = in_array($this->user['jabatan'] ?? '', ['Admin', 'Super Admin', 'Humas']);

        $kategori = $_GET['kategori'] ?? null;
        $status = $_GET['status'] ?? null; // Filter optional untuk admin

        $whereClause = [];
        $params = [];
        $types = "";

        // Filter Role Access
        if (!$isAdminOrHumas) {
            $whereClause[] = "status = 'acc'";
        } else {
            // Admin bisa filter by status specific, kalo kosong tampil semua
            if ($status) {
                $whereClause[] = "status = ?";
                $params[] = $status;
                $types .= "s";
            }
        }

        // Filter Kategori
        if ($kategori) {
            $whereClause[] = "kategori = ?";
            $params[] = $kategori;
            $types .= "s";
        }

        // Build Query
        $sql = "SELECT * FROM materi";
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(" AND ", $whereClause);
        }
        $sql .= " ORDER BY created_at DESC";

        $conn = Database::getConnection();

        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $res = $stmt->get_result();
        } else {
            $res = mysqli_query($conn, $sql);
        }

        $data = mysqli_fetch_all($res, MYSQLI_ASSOC);
        Response::json('success', 'Materi berhasil dimuat', [
            'total' => count($data),
            'data' => $data
        ]);
    }

    public function store()
    {
        // POST: Upload Materi
        // Bisa Admin, Humas, atau Anggota (tapi statys Pending)
        $this->mustBeAuthenticated();

        $this->validate([
            'judul' => 'required',
            'tipe' => 'required',
            'link' => 'required',
            'kategori' => 'required'
        ]);

        $judul = $this->input('judul');
        $desk = $this->input('deskripsi', '');
        $tipe = $this->input('tipe');
        $link = $this->input('link');
        $kategori = $this->input('kategori');
        $id_divisi = $this->input('id_divisi', null); // Optional

        // Tentukan Status Awal
        $isAdminOrHumas = in_array($this->user['jabatan'], ['Admin', 'Super Admin', 'Humas']);
        $status = $isAdminOrHumas ? 'acc' : 'pending';

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO materi (judul, deskripsi, tipe, link, kategori, status, id_divisi) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $judul, $desk, $tipe, $link, $kategori, $status, $id_divisi);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Upload materi ($status): $judul");
            Response::json('success', "Materi berhasil diupload ($status)", null, 201);
        } else {
            throw new AppException("Gagal menyimpan materi", 500);
        }
    }

    public function updateStatus()
    {
        // PUT: Approve / Reject Materi
        $this->mustBeAuthenticated();

        // Cek Role
        $allowed = ['Admin', 'Super Admin', 'Humas'];
        if (!in_array($this->user['jabatan'], $allowed)) {
            throw new AppException("Akses ditolak (Hanya Admin/Humas)", 403);
        }

        $id = $this->input('id');
        $status = $this->input('status'); // acc / reject

        if (!$id || !in_array($status, ['acc', 'reject', 'pending'])) {
            throw new AppException("ID dan Status valid (acc/reject) wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE materi SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Update status materi ID $id ke $status");
            Response::json('success', "Status materi diperbarui menjadi $status");
        } else {
            throw new AppException("Gagal update status", 500);
        }
    }

    public function destroy()
    {
        $this->mustBeAdmin(); // Hapus cuma Admin/Super Admin (Humas cuma bisa reject/acc?)
        // Atau Humas boleh hapus? Kita allow Admin only dulu biar safe.

        $id = $this->input('id');
        if (!$id)
            throw new AppException("ID Materi diperlukan", 400);

        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM materi WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            write_log($this->user['id'], $this->user['username'], "Menghapus materi ID: $id");
            Response::json('success', 'Materi telah dihapus');
        } else {
            throw new AppException("Materi tidak ditemukan", 404);
        }
    }
}
?>