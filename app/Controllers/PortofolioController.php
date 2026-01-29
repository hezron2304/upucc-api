<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;

class PortofolioController extends BaseController
{

    public function index()
    {
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        $query = "SELECT p.*, a.username FROM portofolio p JOIN anggota a ON p.id_anggota = a.id ORDER BY p.created_at DESC";
        $res = mysqli_query($conn, $query);

        Response::json('success', 'Data Portofolio', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    public function store()
    {
        $this->mustBeAuthenticated();

        $judul = $this->input('judul');
        $link = $this->input('link_gambar');
        $kat = $this->input('kategori', 'Umum');
        $desk = $this->input('deskripsi', '');

        if (empty($judul) || empty($link)) {
            throw new AppException("Judul dan Link Gambar wajib diisi", 400);
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO portofolio (judul, deskripsi, link_gambar, id_anggota, kategori) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $judul, $desk, $link, $this->user['id'], $kat);

        if ($stmt->execute()) {
            write_log($this->user['id'], $this->user['username'], "Menambah portofolio: $judul");
            Response::json('success', 'Karya berhasil dipublikasikan', null, 201);
        } else {
            throw new AppException("Gagal menyimpan portofolio", 500);
        }
    }
}
?>