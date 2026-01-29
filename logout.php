<?php
include 'config.php';

// Ambil data user dari token JWT
$user_login = get_auth_user($conn);

if ($user_login) {
    // UPDATE: Ganti 'nama' menjadi 'username' agar sesuai database terbaru
    write_log($conn, $user_login['id'], $user_login['username'], "Melakukan Logout / Keluar Aplikasi");
}

echo json_encode([
    "status" => "success", 
    "message" => "Berhasil logout. Silakan hapus token di sisi client/aplikasi."
]);
mysqli_close($conn);
?>