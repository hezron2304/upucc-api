<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class AbsensiController extends BaseController
{

    public function index()
    {
        // GET: List Absensi By Event
        $this->mustBeAuthenticated();

        // Menggunakan helper input() yang sudah support $_GET juga
        $id_event = $this->input('id_event');
        if (!$id_event) {
            throw new AppException("Parameter id_event required", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT absensi.*, anggota.username FROM absensi JOIN anggota ON absensi.id_anggota = anggota.id WHERE absensi.id_event = ?");
        $stmt->bind_param("i", $id_event);
        $stmt->execute();

        Response::json('success', 'Data Absensi', $stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function store()
    {
        // POST: Lakukan Absensi
        $this->mustBeAuthenticated();

        $id_event = $this->input('id_event');
        $status_kehadiran = $this->input('status_kehadiran', 'Hadir');
        $keterangan = $this->input('keterangan', '-');

        if (empty($id_event)) {
            throw new AppException("ID Event wajib diisi", 400);
        }

        $conn = Database::getConnection();

        // 1. CEK DUPLIKASI
        $stmt_cek = $conn->prepare("SELECT id FROM absensi WHERE id_event = ? AND id_anggota = ?");
        $stmt_cek->bind_param("ii", $id_event, $this->user['id']);
        $stmt_cek->execute();

        if ($stmt_cek->get_result()->num_rows > 0) {
            throw new AppException("Anda sudah melakukan absensi untuk event ini", 409);
        }

        // 2. INSERT ABSENSI
        $stmt = $conn->prepare("INSERT INTO absensi (id_event, id_anggota, status_kehadiran, keterangan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $id_event, $this->user['id'], $status_kehadiran, $keterangan);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Absensi Event ID: " . $id_event);
            Response::json('success', 'Berhasil melakukan absensi', null, 201);
        } else {
            throw new AppException("Gagal mencatat absensi", 500);
        }
    }
}
?>