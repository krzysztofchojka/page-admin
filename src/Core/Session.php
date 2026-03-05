<?php
namespace CMS\Core;

class Session {
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            $handler = new DatabaseSessionHandler();
            session_set_save_handler($handler, true);
            session_set_cookie_params(31536000);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            session_start();
            self::checkLifetime();
        }
    }

    private static function checkLifetime() {
        if (!empty($_SESSION)) {
            $lastActivity = $_SESSION['last_activity'] ?? time();
            $days = 7;
            try {
                $db = Database::getInstance();
                $row = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'session_days'")->fetch();
                if ($row) $days = (int)$row['setting_value'];
            } catch (\Exception $e) {}
            
            $lifetimeSeconds = $days * 24 * 60 * 60;
            if (time() - $lastActivity > $lifetimeSeconds) {
                self::destroy();
                exit;
            } else {
                $_SESSION['last_activity'] = time();
            }
        }
    }

    public static function set($key, $value) { $_SESSION[$key] = $value; }
    public static function get($key) { return $_SESSION[$key] ?? null; }
    public static function remove($key) { unset($_SESSION[$key]); }
    public static function destroy() { session_destroy(); $_SESSION = []; }
    public static function setFlash($message, $type = 'error') { $_SESSION['flash'] = ['msg' => $message, 'type' => $type]; }
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    public static function isLoggedIn() { return isset($_SESSION['user_id']); }

    // --- OCHRONA CSRF ---
    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken($token) {
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            http_response_code(403);
            die('Błąd CSRF (Cross-Site Request Forgery). Żądanie odrzucone dla Twojego bezpieczeństwa.');
        }
    }
}