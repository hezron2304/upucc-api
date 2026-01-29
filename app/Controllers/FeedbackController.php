<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Response;
use App\Core\AppException;
use App\Core\Auth; // For manual check in createFeedback

class FeedbackController extends BaseController
{

    // --- FEEDBACK SECTION ---

    public function indexFeedback()
    {
        // GET: List Feedback (Admin Only)
        $this->mustBeAdmin();

        $conn = Database::getConnection();
        $res = mysqli_query($conn, "SELECT * FROM feedback ORDER BY created_at DESC");
        Response::json('success', 'Data Feedback', mysqli_fetch_all($res, MYSQLI_ASSOC));
    }

    public function createFeedback()
    {
        // POST: Kirim Saran
        // Note: Bisa diakses publik (tanpa token) atau member (dengan token)
        // BaseController otomatis load user di __construct, tapi tidak throw error jika kosong.
        // Kita cek manual.

        $pesan = $this->input('pesan');
        if (empty($pesan))
            throw new AppException("Pesan saran tidak boleh kosong", 400);

        $is_anonim = $this->input('is_anonim', 'ya');
        $nama = 'Anonim';
        $email = '-';

        if ($is_anonim == 'tidak' && $this->user) {
            $nama = $this->user['username'];
            // Asumsi email ada di user session atau query ulang, disini kita simpan '-' atau implement fetch email
            // Untuk simplicity gunakan username dulu atau tarik email jika perlu
        } elseif ($this->input('nama_manual')) {
            $nama = $this->input('nama_manual');
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO feedback (nama, email, pesan) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nama, $email, $pesan);

        if ($stmt->execute()) {
            Response::json('success', 'Terima kasih atas sarannya!');
        } else {
            throw new AppException("Gagal mengirim saran", 500);
        }
    }

    // --- VOTING SECTION ---

    public function indexVoting()
    {
        $this->mustBeAuthenticated();
        $conn = Database::getConnection();

        // Daftar Topik & Hasil
        $query = "SELECT t.*, (SELECT COUNT(*) FROM voting_hasil WHERE id_topic = t.id) as total_vote FROM voting_topic t ORDER BY t.created_at DESC";
        $res = mysqli_query($conn, $query);
        $topics = mysqli_fetch_all($res, MYSQLI_ASSOC);

        // Options
        foreach ($topics as $key => $topic) {
            $id_t = $topic['id'];
            $res_opt = mysqli_query($conn, "SELECT o.*, (SELECT COUNT(*) FROM voting_hasil WHERE id_option = o.id) as suara FROM voting_option o WHERE id_topic = $id_t");
            $topics[$key]['options'] = mysqli_fetch_all($res_opt, MYSQLI_ASSOC);
        }
        Response::json('success', 'Data Voting', $topics);
    }

    public function vote()
    {
        // Submit VOTE (Anggota) atau CREATE TOPIC (Admin)
        // Disarankan dipisah method, tapi mengikuti legacy logic POST `action`
        $action = $this->input('action');

        if ($action == 'create_topic') {
            $this->mustBeAdmin();
            // Implement logic create topic here if needed (Placeholder as per legacy)
            Response::json('success', 'Topik voting dibuat (Logic Placeholder)');

        } elseif ($action == 'vote') {
            $this->mustBeAuthenticated();
            $id_topic = (int) $this->input('id_topic');
            $id_option = (int) $this->input('id_option');

            $conn = Database::getConnection();
            $stmt = $conn->prepare("INSERT INTO voting_hasil (id_topic, id_anggota, id_option) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id_topic, $this->user['id'], $id_option);

            try {
                if ($stmt->execute()) {
                    write_log($this->user['id'], $this->user['username'], "Melakukan vote pada topik ID: $id_topic");
                    Response::json('success', 'Suara Anda berhasil dikirim');
                }
            } catch (\Exception $e) {
                // Biasanya duplicate entry error
                throw new AppException("Anda sudah memberikan suara pada topik ini", 409);
            }
        }
    }
}
?>