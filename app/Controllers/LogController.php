<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;

class LogController extends BaseController
{

    public function index()
    {
        $this->mustBeSuperAdmin(); // ONLY SUPER ADMIN

        $conn = Database::getConnection();
        // Limit 100 terbaru
        $query = "SELECT * FROM logs ORDER BY tanggal DESC LIMIT 100";
        $res = mysqli_query($conn, $query);

        Response::json('success', 'Log aktivitas berhasil dimuat', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }
}
?>