<?php
namespace App\Core;

use App\Core\Database;

class Auth
{

    public static function attempt($identifier, $password)
    {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT id, nama, username, jabatan, password FROM anggota WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return null;
    }

    public static function user()
    {
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $auth = $headers['authorization'] ?? '';
        $token = str_replace('Bearer ', '', $auth);

        if (empty($token))
            return null;

        $part = explode('.', $token);
        if (count($part) != 3)
            return null;

        $secret = $_ENV['JWT_SECRET'] ?? 'default_secret';

        $validSignature = hash_hmac('sha256', $part[0] . "." . $part[1], $secret, true);
        $base64ValidSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));

        if (!hash_equals($base64ValidSignature, $part[2]))
            return null;

        $payload = json_decode(base64_decode($part[1]), true);
        if (!$payload)
            return null;

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT id, nama, username, jabatan FROM anggota WHERE id = ?");
        $stmt->bind_param("i", $payload['id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public static function createToken($payload)
    {
        $secret = $_ENV['JWT_SECRET'] ?? 'default_secret';
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
}
?>