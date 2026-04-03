<?php
namespace Vsys\Lib;

use PDO;
use PDOException;

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        // Load config if exists
        $configPath = dirname(__DIR__) . '/config/config.php';
        if (file_exists($configPath)) {
            require_once $configPath;
        }

        $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1');
        $name = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'gozziar_vs_system_erp');
        $user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'gozziar_javiergdm');
        $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: 'Andrea1910');
        $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';

        $dsn = "mysql:host=" . $host . ";dbname=" . $name . ";charset=" . $charset;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->conn = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->getConnection();
    }

    public function getConnection()
    {
        return $this->conn;
    }
}