<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class InventoryController extends BaseController
{

    // --- MANAJEMEN BARANG ---

    public function index()
    {
        // GET: List Barang
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        $res = mysqli_query($conn, "SELECT * FROM inventory ORDER BY kategori ASC, nama_alat ASC");
        Response::json('success', 'Data Inventory', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    public function store()
    {
        // POST: Tambah Barang
        $this->mustBeAdmin(); // Helper dari BaseController

        $nama = $this->input('nama_alat');
        $stok = (int) $this->input('stok', 0);
        $kategori = $this->input('kategori', 'Umum');
        $deskripsi = $this->input('deskripsi', '-');
        $kondisi = $this->input('kondisi', 'Baik');

        if (empty($nama)) {
            throw new AppException("Nama alat wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO inventory (nama_alat, deskripsi, stok, kondisi, kategori) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $nama, $deskripsi, $stok, $kondisi, $kategori);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Menambah alat baru: $nama");
            Response::json('success', 'Alat berhasil ditambahkan', null, 201);
        } else {
            throw new AppException("Gagal menambah alat", 500);
        }
    }

    public function destroy()
    {
        // DELETE: Hapus Barang
        $this->mustBeAdmin();
        $id = $this->input('id');

        if (!$id)
            throw new AppException("ID Alat required", 400);

        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Menghapus alat ID: $id");
            Response::json('success', 'Alat berhasil dihapus');
        } else {
            throw new AppException("Gagal menghapus alat", 500);
        }
    }

    // --- MANAJEMEN PEMINJAMAN (TRANSAKSI) ---

    public function indexTransactions()
    {
        $this->mustBeAuthenticated();
        $conn = Database::getConnection();

        // Jika Admin: lihat semua. Jika Anggota: lihat history sendiri.
        if (in_array($this->user['jabatan'], ['Admin', 'Super Admin'])) {
            $query = "SELECT p.*, a.username, i.nama_alat FROM peminjaman p 
                      JOIN anggota a ON p.id_anggota = a.id 
                      JOIN inventory i ON p.id_alat = i.id ORDER BY p.created_at DESC";
            $res = mysqli_query($conn, $query);
            $data = mysqli_fetch_all($res, MYSQLI_ASSOC);
        } else {
            $stmt = $conn->prepare("SELECT p.*, i.nama_alat FROM peminjaman p 
                                    JOIN inventory i ON p.id_alat = i.id 
                                    WHERE p.id_anggota = ? ORDER BY p.created_at DESC");
            $stmt->bind_param("i", $this->user['id']);
            $stmt->execute();
            $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        Response::json('success', 'Data Peminjaman', $data);
    }

    public function borrow()
    {
        $this->mustBeAuthenticated();

        $id_alat = (int) $this->input('id_alat');
        $jumlah = (int) $this->input('jumlah');

        if ($id_alat <= 0 || $jumlah <= 0) {
            throw new AppException("ID Alat dan Jumlah harus valid", 400);
        }

        $conn = Database::getConnection();

        // 1. ATOMIC UPDATE (Kurangi stok HANYA JIKA stok cukup)
        $stmt_upd = $conn->prepare("UPDATE inventory SET stok = stok - ? WHERE id = ? AND stok >= ?");
        $stmt_upd->bind_param("iii", $jumlah, $id_alat, $jumlah);
        $stmt_upd->execute();

        if ($stmt_upd->affected_rows > 0) {
            $tgl_pinjam = date('Y-m-d');
            $stmt_ins = $conn->prepare("INSERT INTO peminjaman (id_anggota, id_alat, jumlah, tanggal_pinjam) VALUES (?, ?, ?, ?)");
            $stmt_ins->bind_param("iiis", $this->user['id'], $id_alat, $jumlah, $tgl_pinjam);

            if ($stmt_ins->execute()) {
                write_log($this->user['id'], $this->user['username'], "Meminjam alat ID: $id_alat ($jumlah unit)");
                Response::json('success', 'Peminjaman berhasil dicatat', null, 201);
            } else {
                // Critical Error
                throw new AppException("Gagal mencatat history peminjaman (Stok mungkin terpotong)", 500);
            }
        } else {
            throw new AppException("Stok tidak mencukupi atau alat tidak ditemukan", 400);
        }
    }

    public function returnItem()
    {
        $this->mustBeAdmin();

        $id_pinjam = (int) $this->input('id_peminjaman');
        if (!$id_pinjam)
            throw new AppException("ID Peminjaman required", 400);

        $conn = Database::getConnection();

        // Cek Data
        $stmt_p = $conn->prepare("SELECT id_alat, jumlah, status FROM peminjaman WHERE id = ?");
        $stmt_p->bind_param("i", $id_pinjam);
        $stmt_p->execute();
        $data = $stmt_p->get_result()->fetch_assoc();

        if (!$data || $data['status'] == 'Dikembalikan') {
            throw new AppException("Data tidak ditemukan atau sudah dikembalikan", 404);
        }

        // Update Status
        $tgl_kembali = date('Y-m-d');
        $stmt_ret = $conn->prepare("UPDATE peminjaman SET status = 'Dikembalikan', tanggal_kembali = ? WHERE id = ?");
        $stmt_ret->bind_param("si", $tgl_kembali, $id_pinjam);

        if ($stmt_ret->execute()) {
            // Restore Stok
            $stmt_add = $conn->prepare("UPDATE inventory SET stok = stok + ? WHERE id = ?");
            $stmt_add->bind_param("ii", $data['jumlah'], $data['id_alat']);
            $stmt_add->execute();

            write_log($this->user['id'], $this->user['username'], "Memproses pengembalian alat ID Pinjam: $id_pinjam");
            Response::json('success', 'Barang berhasil dikembalikan');
        } else {
            throw new AppException("Gagal memproses pengembalian", 500);
        }
    }
}
?>