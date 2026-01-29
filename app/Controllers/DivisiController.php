<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;

class DivisiController extends BaseController
{

    public function index()
    {
        // Public or Authenticated? Let's keep it Authenticated
        $this->mustBeAuthenticated();

        $conn = Database::getConnection();
        $res = mysqli_query($conn, "SELECT * FROM divisi ORDER BY nama_divisi ASC");

        Response::json('success', 'Data Divisi', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }
}
?>