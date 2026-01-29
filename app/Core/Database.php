<?php
namespace App\Core;

class Database
{
    private static $conn;

    public static function getConnection()
    {
        if (self::$conn === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';
            $name = $_ENV['DB_NAME'] ?? '';

            self::$conn = mysqli_connect($host, $user, $pass, $name);

            if (!self::$conn) {
                throw new \Exception("Database Connection Failed: " . mysqli_connect_error());
            }
        }
        return self::$conn;
    }
}
?>