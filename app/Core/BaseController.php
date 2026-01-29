<?php
namespace App\Core;

use App\Core\Auth;
use App\Core\Response;
use App\Core\AppException;

class BaseController
{
    protected $user;

    public function __construct()
    {
        // Otomatis load user auth di setiap controller yang extend ini
        $this->user = Auth::user();
    }

    protected function mustBeAuthenticated()
    {
        if (!$this->user) {
            throw new AppException("Unauthorized", 401);
        }
    }

    protected function mustBeAdmin()
    {
        $this->mustBeAuthenticated();
        if (!in_array($this->user['jabatan'], ['Admin', 'Super Admin'])) {
            throw new AppException("Access Denied: Admin only", 403);
        }
    }

    protected function mustBeSuperAdmin()
    {
        $this->mustBeAuthenticated();
        if ($this->user['jabatan'] !== 'Super Admin') {
            throw new AppException("Access Denied: Super Admin only", 403);
        }
    }

    // Helper untuk mengambil input JSON atau POST
    protected function input($key, $default = null)
    {
        // Prioritas: JSON Body -> POST -> GET (Query Param)
        // Cek JSON dulu (untuk standar API modern)
        $json = json_decode(file_get_contents("php://input"), true);
        $data = $json[$key] ?? null;

        // Jika tidak ada di JSON, cek POST
        if ($data === null) {
            $data = $_POST[$key] ?? null;
        }

        // Jika tidak ada di POST, cek GET
        if ($data === null) {
            $data = $_GET[$key] ?? null;
        }

        return $data ?? $default;
    }

    protected function validate($rules)
    {
        $data = array_merge($_GET, $_POST, (array) json_decode(file_get_contents("php://input"), true));
        \App\Core\Validator::validate($data, $rules);
    }
}
?>