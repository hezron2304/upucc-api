<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class EventController extends BaseController
{

    public function index()
    {
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        $res = mysqli_query($conn, "SELECT * FROM event ORDER BY tanggal DESC");
        Response::json('success', 'Data Event', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    public function store()
    {
        $this->mustBeAuthenticated();

        // Cek Role: Admin, Super Admin, Sekretaris
        // Note: 'Sekretaris' double 'Sektetaris' typo di legacy code, kita support dua-duanya dulu atau fix typo
        $allowed = ['Admin', 'Super Admin', 'Sekretaris', 'Sektetaris'];
        if (!in_array($this->user['jabatan'], $allowed)) {
            throw new AppException("Akses ditolak (Hanya Admin/Sekretaris)", 403);
        }

        $nama_event = $this->input('nama_event');
        $tanggal = $this->input('tanggal');
        $lokasi = $this->input('lokasi');
        $deskripsi = $this->input('deskripsi');

        if (empty($nama_event) || empty($tanggal)) {
            throw new AppException("Nama Event dan Tanggal wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO event (nama_event, tanggal, lokasi, deskripsi) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nama_event, $tanggal, $lokasi, $deskripsi);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Membuat Event: " . $nama_event);
            Response::json('success', 'Event dibuat', null, 201);
        } else {
            throw new AppException("Gagal membuat event", 500);
        }
    }
}
?>