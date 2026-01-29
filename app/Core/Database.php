<?php
namespace App\Core;

class Database
{
    private static $conn;

    public static function getConnection()
    {
        if (self::$conn === null) {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'hezron';
            $pass = $_ENV['DB_PASS'] ?? 'hezron';
            $name = $_ENV['DB_NAME'] ?? 'hezron';

            self::$conn = mysqli_connect($host, $user, $pass, $name);

            if (!self::$conn) {
                throw new \Exception("Database Connection Failed: " . mysqli_connect_error());
            }
        }
        return self::$conn;
    }
}
?>