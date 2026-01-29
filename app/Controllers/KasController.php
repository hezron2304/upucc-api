<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class KasController
{

    public function index()
    {
        // GET: Lihat Kas
        $conn = Database::getConnection();

        $res = mysqli_query($conn, "SELECT kas.*, anggota.username as pencatat FROM kas LEFT JOIN anggota ON kas.id_anggota = anggota.id ORDER BY kas.tanggal DESC");
        $data = mysqli_fetch_all($res, MYSQLI_ASSOC);

        $res_saldo = mysqli_query($conn, "SELECT SUM(CASE WHEN tipe = 'Masuk' THEN jumlah ELSE 0 END) as masuk, SUM(CASE WHEN tipe = 'Keluar' THEN jumlah ELSE 0 END) as keluar FROM kas");
        $saldo = mysqli_fetch_assoc($res_saldo);

        Response::json('success', 'Data Kas', [
            "total_saldo" => (int) $saldo['masuk'] - (int) $saldo['keluar'],
            "data" => $data
        ]);
    }

    public function store()
    {
        // POST: Tambah Kas
        $user = Auth::user();

        // RBAC Check
        if (!$user) {
            throw new AppException("Unauthorized", 401);
        }

        if (!in_array($user['jabatan'], ['Admin', 'Super Admin', 'Bendahara'])) {
            throw new AppException("Akses ditolak: Hanya Bendahara/Admin yg boleh catat kas", 403);
        }

        $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
        $keterangan = $_POST['keterangan'] ?? '';
        $tipe = $_POST['tipe'] ?? '';
        $jumlah = $_POST['jumlah'] ?? 0;
        $id_anggota = $user['id'];

        if (empty($keterangan) || empty($tipe) || empty($jumlah)) {
            throw new AppException("Data tidak lengkap", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO kas (tanggal, keterangan, tipe, jumlah, id_anggota) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $tanggal, $keterangan, $tipe, $jumlah, $id_anggota);

        if ($stmt->execute()) {
            write_log($user['id'], $user['username'], "Input Kas $tipe: $jumlah");
            Response::json('success', 'Kas berhasil dicatat', null, 201);
        } else {
            throw new AppException("Gagal menyimpan data kas", 500);
        }
    }
}
?>