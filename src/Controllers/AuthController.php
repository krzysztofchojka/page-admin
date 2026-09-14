<?php
namespace CMS\Controllers;

use CMS\Core\Session;
use CMS\Models\User;

class AuthController {

    // Pobieramy ustawienia z bazy
    private function getSettings() {
        $db = \CMS\Core\Database::getInstance();
        $settingsRows = $db->query("SELECT * FROM pa_settings")->fetchAll();
        $settings = [];
        foreach($settingsRows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    }

    // Przykład modyfikacji metody loginForm()
    public function loginForm() {
        Session::init();
        if (Session::isLoggedIn()) {
            header('Location: /admin');
            exit;
        }
        $settings = $this->getSettings();

        // SPRAWDZENIE CUSTOMOWEJ STRONY
        if (!empty($settings['login_page_id'])) {
            $db = \CMS\Core\Database::getInstance();
            $page = $db->query("SELECT * FROM pa_data WHERE id = :id", ['id' => $settings['login_page_id']])->fetch();
            
            if ($page) {
                $blocks = json_decode($page['contents'], true) ?? [];
                // Załadowanie globalnej stopki
                $footerBlocks = null;
                if (($settings['hide_footer'] ?? 0) != 1 && !empty($settings['footer_page_id'])) {
                    $footerPage = $db->query("SELECT contents FROM pa_data WHERE id = :id", ['id' => $settings['footer_page_id']])->fetch();
                    if ($footerPage && $footerPage['contents']) {
                        $footerBlocks = json_decode($footerPage['contents'], true);
                    }
                }
                require_once __DIR__ . '/../Views/public/page.php';
                return; // Przerywamy, nie ładujemy domyślnego widoku!
            }
        }

        // Widok domyślny jako fallback
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function login() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $db = \CMS\Core\Database::getInstance();
        $settings = $this->getSettings();
        
        $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'];
        
        Session::set('old_login', $login);

        if (!$login || !$password) {
            Session::setFlash('Wypełnij wszystkie pola.');
            header('Location: /login');
            exit;
        }

        // 1. AUTOMATYCZNA MIGRACJA TABELI ANTI-BRUTEFORCE
        try {
            $db->query("CREATE TABLE IF NOT EXISTS pa_login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                username VARCHAR(255) NOT NULL,
                attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            // Czyszczenie starych prób (starszych niż 24h)
            $db->query("DELETE FROM pa_login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        } catch (\Exception $e) {}

        // 2. POZIOM 3: TWARDA BLOKADA IP (Chamski brute-force)
        // 30 błędów w ciągu ostatnich 60 minut z jednego IP
        $ipFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 60 MINUTE)", [$ip])->fetch()['c'];
        if ($ipFails >= 30) {
            http_response_code(429); // Too Many Requests
            die("Zbyt wiele prób logowania z tego adresu IP. Twój dostęp został zablokowany. Spróbuj ponownie za godzinę.");
        }

        // 3. POZIOM 1: MIĘKKA BLOKADA KONTA (Targetowany brute-force)
        // 5 błędów w ciągu 15 minut dla konkretnego loginu
        $accountFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE username = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$login])->fetch()['c'];
        if ($accountFails >= 5) {
            Session::setFlash('Konto tymczasowo zablokowane na 15 minut z powodu zbyt wielu błędnych logowań.');
            header('Location: /login');
            exit;
        }

        // 4. WERYFIKACJA CAPTCHA (Jeśli IP ma na koncie 3+ błędy w ciągu 15 minut)
        $recentIpFails15m = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetch()['c'];
        if ($recentIpFails15m >= 3) {
            $expectedCaptcha = Session::get('login_captcha');
            $providedCaptcha = strtolower(trim($_POST['captcha_answer'] ?? ''));
            
            if (!$expectedCaptcha || $providedCaptcha !== strtolower($expectedCaptcha)) {
                Session::setFlash('Nieprawidłowy kod z obrazka (Captcha).');
                header('Location: /login');
                exit;
            }
        }

        // --- OBSŁUGA UKRYTEJ REJESTRACJI (Bez zmian) ---
        $regMode = $settings['reg_mode'] ?? 'disabled';
        $secretLogin = trim($settings['reg_secret_login'] ?? '');
        $secretPass = $settings['reg_secret_pass'] ?? '';

        if ($regMode === 'secret' && $secretLogin !== '') {
            if ($login === $secretLogin && $password === $secretPass) {
                Session::set('secret_reg_unlocked', true);
                header('Location: /register');
                exit;
            } else {
                Session::remove('secret_reg_unlocked');
            }
        }

        // 5. WERYFIKACJA UŻYTKOWNIKA W BAZIE
        $userModel = new \CMS\Models\User();
        $user = $userModel->findByEmail($login);
        if (!$user) {
            $user = $userModel->findByUsername($login);
        }

        if ($user && password_verify($password, $user['pass'])) {
            // SUKCES: Czyścimy historię błędów dla tego IP i loginu
            $db->query("DELETE FROM pa_login_attempts WHERE username = ? OR ip_address = ?", [$login, $ip]);
            Session::remove('login_captcha');
            
            if ($user['pass_expired'] == 1) {
                Session::set('temp_user_id', $user['id']);
                header('Location: /change-password');
                exit;
            }
            
            session_regenerate_id(true);
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['uname']);
            Session::set('is_admin', $user['admin']);
            header('Location: /admin');
            exit;
        }

        // BŁĄD: Rejestrujemy nieudaną próbę
        $db->query("INSERT INTO pa_login_attempts (ip_address, username) VALUES (?, ?)", [$ip, $login]);
        Session::setFlash('Nieprawidłowe dane logowania.');
        header('Location: /login');
        exit;
    }

    public function registerForm() {
        Session::init();
        $settings = $this->getSettings();
        $regMode = $settings['reg_mode'] ?? 'disabled';
    
        // 1. Odrzucamy jeśli rejestracja jest całkowicie wyłączona
        if ($regMode === 'disabled') {
            Session::setFlash('Rejestracja jest zablokowana.', 'error');
            header('Location: /login');
            exit;
        }
    
        // 2. Odrzucamy jeśli tryb to secret, a użytkownik NIE ma ścisłego klucza w sesji
        if ($regMode === 'secret' && Session::get('secret_reg_unlocked') !== true) {
            Session::setFlash('Rejestracja ukryta. Wpisz poprawne dane dostępu w logowaniu.', 'error');
            header('Location: /login');
            exit;
        }
    
        // 3. SPRAWDZENIE CUSTOMOWEJ STRONY REJESTRACJI (Wbudowanej w Page Builder)
        if (!empty($settings['register_page_id'])) {
            $db = \CMS\Core\Database::getInstance();
            $page = $db->query("SELECT * FROM pa_data WHERE id = :id", ['id' => $settings['register_page_id']])->fetch();
            
            if ($page) {
                $blocks = json_decode($page['contents'], true) ?? [];
                
                // Załadowanie globalnej stopki, jeśli jest włączona
                $footerBlocks = null;
                if (($settings['hide_footer'] ?? 0) != 1 && !empty($settings['footer_page_id'])) {
                    $footerPage = $db->query("SELECT contents FROM pa_data WHERE id = :id", ['id' => $settings['footer_page_id']])->fetch();
                    if ($footerPage && $footerPage['contents']) {
                        $footerBlocks = json_decode($footerPage['contents'], true);
                    }
                }
                
                // Renderowanie pełnego widoku jak dla zwykłej strony
                require_once __DIR__ . '/../Views/public/page.php';
                return; // Przerywamy działanie funkcji, aby nie załadować domyślnego widoku!
            }
        }
    
        // 4. Jeśli nie ustawiono customowej strony - ładujemy domyślny widok awaryjny (fallback)
        require_once __DIR__ . '/../Views/auth/register.php';
    }

    public function register() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $settings = $this->getSettings();
        $regMode = $settings['reg_mode'] ?? 'disabled';

        // Podwójne zabezpieczenie na metodzie POST ze ścisłym sprawdzeniem typu bool
        if ($regMode === 'disabled' || ($regMode === 'secret' && Session::get('secret_reg_unlocked') !== true)) {
            header('Location: /login');
            exit;
        }

        $db = \CMS\Core\Database::getInstance();
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $pass2 = $_POST['confirm_password'] ?? '';

        // Zapisujemy email w sesji
        Session::set('old_email', $email);

        if ($pass !== $pass2) {
            Session::setFlash('Hasła nie są identyczne.');
            header('Location: /register');
            exit;
        }
        if (strlen($pass) < 6) {
            Session::setFlash('Hasło musi mieć minimum 6 znaków.');
            header('Location: /register');
            exit;
        }

        $exists = $db->query("SELECT id FROM pa_users WHERE email = :e", ['e' => $email])->fetch();
        if ($exists) {
            Session::setFlash('Ten adres email jest już zarejestrowany.');
            header('Location: /register');
            exit;
        }

        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->query("INSERT INTO pa_users (uname, email, pass, admin, pass_expired) VALUES (:u, :e, :p, 0, 0)", [
            'u' => $email,
            'e' => $email,
            'p' => $hash
        ]);

        // Ściągamy blokadę po udanej rejestracji (żeby następnym razem ukryty formularz znowu zniknął)
        Session::remove('secret_reg_unlocked');

        // Automatyczne logowanie po rejestracji
        $userId = $db->getConnection()->lastInsertId();
        Session::set('user_id', $userId);
        Session::set('user_name', $email);
        Session::set('is_admin', 0);
        
        header('Location: /');
        exit;
    }

    public function changePasswordForm() {
        Session::init();
        
        // Sprawdzenie, czy użytkownik ma uprawnienia do przebywania na tej stronie
        if (!Session::get('temp_user_id')) {
            header('Location: /login');
            exit;
        }
    
        $settings = $this->getSettings();
    
        // SPRAWDZENIE CUSTOMOWEJ STRONY ZMIANY HASŁA (Wbudowanej w Page Builder)
        if (!empty($settings['change_password_page_id'])) {
            $db = \CMS\Core\Database::getInstance();
            $page = $db->query("SELECT * FROM pa_data WHERE id = :id", ['id' => $settings['change_password_page_id']])->fetch();
            
            if ($page) {
                $blocks = json_decode($page['contents'], true) ?? [];
                
                // Załadowanie globalnej stopki, jeśli jest włączona
                $footerBlocks = null;
                if (($settings['hide_footer'] ?? 0) != 1 && !empty($settings['footer_page_id'])) {
                    $footerPage = $db->query("SELECT contents FROM pa_data WHERE id = :id", ['id' => $settings['footer_page_id']])->fetch();
                    if ($footerPage && $footerPage['contents']) {
                        $footerBlocks = json_decode($footerPage['contents'], true);
                    }
                }
                
                // Renderowanie pełnego widoku jak dla zwykłej strony
                require_once __DIR__ . '/../Views/public/page.php';
                return; // Przerywamy działanie funkcji, aby nie załadować domyślnego widoku!
            }
        }
    
        // Jeśli nie ustawiono customowej strony - ładujemy domyślny widok awaryjny (fallback)
        require_once __DIR__ . '/../Views/auth/change_password.php';
    }

    public function changePassword() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? ''); // <-- DODANE
        
        $userId = Session::get('temp_user_id');
        if (!$userId) {
            header('Location: /login');
            exit;
        }
        $pass1 = $_POST['pass1'] ?? '';
        $pass2 = $_POST['pass2'] ?? '';

        if ($pass1 !== $pass2) {
            Session::setFlash('Hasła nie są identyczne.');
            header('Location: /change-password');
            exit;
        }

        if (strlen($pass1) < 6) {
            Session::setFlash('Hasło musi mieć minimum 6 znaków.');
            header('Location: /change-password');
            exit;
        }

        $db = \CMS\Core\Database::getInstance();
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $db->query("UPDATE pa_users SET pass = :pass, pass_expired = 0 WHERE id = :id", [
            'pass' => $hash,
            'id' => $userId
        ]);

        Session::remove('temp_user_id');
        $user = $db->query("SELECT * FROM pa_users WHERE id = :id", ['id' => $userId])->fetch();

        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['uname']);
        Session::set('is_admin', $user['admin']);
        
        header('Location: /admin');
        exit;
    }

    private function ensureResetColumnsExist($db) {
        try {
            $db->query("ALTER TABLE pa_users ADD COLUMN reset_token VARCHAR(64) NULL AFTER pass_expired, ADD COLUMN reset_expires DATETIME NULL AFTER reset_token");
        } catch (\Exception $e) {}
    }

    public function forgotPasswordForm() {
        Session::init();
        if (Session::isLoggedIn()) {
            header('Location: /admin');
            exit;
        }
        $settings = $this->getSettings();
        require_once __DIR__ . '/../Views/auth/forgot_password.php';
    }

    public function sendResetLink() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        $db = \CMS\Core\Database::getInstance();
        $this->ensureResetColumnsExist($db);

        $user = $db->query("SELECT id FROM pa_users WHERE email = :e", ['e' => $email])->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $db->query("UPDATE pa_users SET reset_token = :t, reset_expires = :d WHERE id = :id", [
                't' => $token, 'd' => $expires, 'id' => $user['id']
            ]);

            $domain = $_SERVER['HTTP_HOST'] ?? 'domena.pl';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $resetLink = "{$protocol}://{$domain}/reset-password?token={$token}";

            require_once __DIR__ . '/../Services/MailerService.php';
            $mailer = new \CMS\Services\MailerService();
            $body = "Witaj,<br><br>Otrzymaliśmy prośbę o reset hasła do Twojego konta.<br>Kliknij w poniższy link, aby ustawić nowe hasło (link wygasa za godzinę):<br><br><a href='{$resetLink}'>{$resetLink}</a><br><br>Jeśli to nie Ty prosiłeś o reset, zignoruj tę wiadomość.";
            
            $mailer->send($email, "Reset hasła - " . $domain, $body);
        }

        // Zawsze pokazujemy ten sam komunikat, aby nie zdradzać istnienia maili w bazie (bezpieczeństwo)
        Session::setFlash('Jeśli podany adres istnieje w bazie, wysłano na niego link do resetu hasła.', 'success');
        header('Location: /forgot-password');
        exit;
    }

    public function resetPasswordForm() {
        Session::init();
        $token = $_GET['token'] ?? '';
        if (!$token) {
            Session::setFlash('Brakujący token resetowania.');
            header('Location: /login');
            exit;
        }

        $db = \CMS\Core\Database::getInstance();
        $this->ensureResetColumnsExist($db);
        
        $user = $db->query("SELECT id FROM pa_users WHERE reset_token = :t AND reset_expires > NOW()", ['t' => $token])->fetch();
        if (!$user) {
            Session::setFlash('Link do resetowania hasła jest nieprawidłowy lub wygasł.');
            header('Location: /login');
            exit;
        }

        $settings = $this->getSettings();
        require_once __DIR__ . '/../Views/auth/reset_password.php';
    }

    public function updatePassword() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $token = $_POST['token'] ?? '';
        $pass1 = $_POST['pass1'] ?? '';
        $pass2 = $_POST['pass2'] ?? '';

        $db = \CMS\Core\Database::getInstance();
        $user = $db->query("SELECT id FROM pa_users WHERE reset_token = :t AND reset_expires > NOW()", ['t' => $token])->fetch();

        if (!$user) {
            Session::setFlash('Zły lub przeterminowany token.');
            header('Location: /login');
            exit;
        }

        if ($pass1 !== $pass2) {
            Session::setFlash('Hasła nie są identyczne.');
            header("Location: /reset-password?token={$token}");
            exit;
        }

        if (strlen($pass1) < 6) {
            Session::setFlash('Hasło musi mieć minimum 6 znaków.');
            header("Location: /reset-password?token={$token}");
            exit;
        }

        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        $db->query("UPDATE pa_users SET pass = :p, reset_token = NULL, reset_expires = NULL WHERE id = :id", [
            'p' => $hash, 'id' => $user['id']
        ]);

        Session::setFlash('Hasło zostało pomyślnie zmienione. Możesz się zalogować.', 'success');
        header('Location: /login');
        exit;
    }

    public function logout() {
        Session::init();
        Session::destroy();
        header('Location: /login');
        exit;
    }
}