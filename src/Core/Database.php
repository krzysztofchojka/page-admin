<?php
namespace CMS\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = parse_ini_file(__DIR__ . '/../../.env');
        $dsn = "mysql:host=" . $config['DB_HOST'] . ";dbname=" . $config['DB_NAME'] . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
        } catch (PDOException $e) {
            // Zapisanie błędu do bezpiecznego pliku na serwerze
            $logDir = __DIR__ . '/../../logs/';
            if (!is_dir($logDir)) { mkdir($logDir, 0755, true); }
            $logMessage = "[" . date('Y-m-d H:i:s') . "] DB Connection Error: " . $e->getMessage() . "\n";
            file_put_contents($logDir . 'error.log', $logMessage, FILE_APPEND);
            
            // Ładny ekran dla użytkownika końcowego
            http_response_code(503);
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Przerwa Techniczna</title></head>";
            echo "<body style='font-family:sans-serif;background:#f3f4f6;color:#374151;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;'>";
            echo "<div style='background:#fff;padding:40px;border-radius:10px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);text-align:center;'>";
            echo "<h1 style='font-size:24px;color:#111827;margin-bottom:10px;'>🛠️ Przerwa Techniczna</h1>";
            echo "<p style='margin:0;'>Pracujemy nad optymalizacją systemu. Spróbuj odświeżyć stronę za kilka minut.</p>";
            echo "</div></body></html>";
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}