<?php
namespace CMS\Core;

class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            
            // 1. Zabezpieczenie przed systemowym GC - własny folder na sesje
            // Używamy realpath, aby mieć pewność absolutnej i poprawnej ścieżki
            $baseDir = realpath(__DIR__ . '/../../');
            $sessionPath = $baseDir . '/sessions';
            
            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0775, true);
            }

            // BEZPIECZNIK: Nadpisz ścieżkę tylko wtedy, gdy serwer fizycznie może tam zapisać dane.
            // Jeśli uprawnień brakuje, logowanie zadziała po staremu (bez wydłużonego czasu trwania).
            if (is_dir($sessionPath) && is_writable($sessionPath)) {
                session_save_path($sessionPath);
            }

            // Informujemy przeglądarkę, aby zatrzymała ciastko na bardzo długi czas (1 rok w sekundach).
            session_set_cookie_params(31536000);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            
            // Set a long GC maxlifetime (e.g., 1 year)
            ini_set('session.gc_maxlifetime', 31536000);
            
            session_start();
            
            // 2. TIMEOUT CHECK
            self::checkLifetime();
        }
    }

    private static function checkLifetime() {
        // Only check if we are actually logged in/unlocked
        if (!empty($_SESSION)) {
            $lastActivity = $_SESSION['last_activity'] ?? time();
            
            // Fetch setting (default 7 days)
            // We use a raw PDO call here to avoid circular dependency loops if Database uses Session
            $days = 7; 
            try {
                $db = \CMS\Core\Database::getInstance();
                $row = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'session_days'")->fetch();
                if ($row) $days = (int)$row['setting_value'];
            } catch (\Exception $e) {}

            $lifetimeSeconds = $days * 24 * 60 * 60;

            if (time() - $lastActivity > $lifetimeSeconds) {
                // Session expired
                self::destroy();
                // Optional: header('Location: /'); exit; 
            } else {
                // Update activity timestamp
                $_SESSION['last_activity'] = time();
            }
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        session_destroy();
        $_SESSION = [];
    }

    // Flash Messages (e.g., "Invalid Password")
    public static function setFlash($message, $type = 'error') {
        $_SESSION['flash'] = ['msg' => $message, 'type' => $type];
    }

    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}