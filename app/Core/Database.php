<?php
namespace App\Core;

class Database
{
    private static $conn;

    public static function getConnection()
    {
        if (self::$conn === null) {
            // Strict retrieval from $_ENV - no hardcoded defaults!
            $host = $_ENV['DB_HOST'] ?? null;
            $user = $_ENV['DB_USER'] ?? null;
            $pass = $_ENV['DB_PASS'] ?? null;
            $name = $_ENV['DB_NAME'] ?? null;

            if (!$host || !$user || !$name) {
                // If Env is not loaded, we should not attempt to connect with defaults/empty
                throw new \Exception("Database Configuration Error: Missing environment variables (DB_HOST, DB_USER, DB_NAME). Check local config.");
            }

            self::$conn = mysqli_connect($host, $user, $pass, $name);

            if (!self::$conn) {
                throw new \Exception("Database Connection Failed: " . mysqli_connect_error());
            }
        }
        return self::$conn;
    }
}
?>