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

    // Automatyczna migracja nowych kolumn zabezpieczeń dla starych instalacji
    private function ensureVerificationColumnsExist($db) {
        try {
            $db->query("ALTER TABLE pa_users ADD COLUMN is_verified TINYINT(1) DEFAULT 1 AFTER pass_expired, ADD COLUMN verification_token VARCHAR(64) NULL AFTER is_verified");
        } catch (\Exception $e) {}
    }

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

        // Uruchamiamy autouzupełnianie brakujących kolumn
        $this->ensureVerificationColumnsExist($db);

        // Zastąpiono przestarzały (deprecated) filtr FILTER_SANITIZE_STRING funkcją strip_tags()
        $login = strip_tags(trim($_POST['login'] ?? ''));
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

        // 2. WERYFIKACJA CAPTCHA (Zamiast twardego blokowania IP i konta)
        // Uwaga: W przyszłości można tu dodać twarde banowanie, 
        // ale powinno opierać się o fingerprint przeglądarki, a nie samo IP.
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

        // --- OBSŁUGA UKRYTEJ REJESTRACJI ---
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

        // 3. WERYFIKACJA UŻYTKOWNIKA W BAZIE
        $userModel = new \CMS\Models\User();
        $user = $userModel->findByEmail($login);
        if (!$user) {
            $user = $userModel->findByUsername($login);
        }

        if ($user && password_verify($password, $user['pass'])) {
            // Sprawdzenie wymogu weryfikacji mailowej
            if (isset($user['is_verified']) && $user['is_verified'] == 0) {
                Session::setFlash('Twoje konto nie zostało jeszcze zweryfikowane. Sprawdź swoją skrzynkę email.', 'error');
                header('Location: /login');
                exit;
            }

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
        $this->ensureVerificationColumnsExist($db);

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
        
        $isVerified = ($settings['email_verification_enabled'] ?? 0) == 1 ? 0 : 1;
        $token = bin2hex(random_bytes(32));

        $db->query("INSERT INTO pa_users (uname, email, pass, admin, pass_expired, is_verified, verification_token) VALUES (:u, :e, :p, 0, 0, :v, :t)", [
            'u' => $email,
            'e' => $email,
            'p' => $hash,
            'v' => $isVerified,
            't' => $token
        ]);

        // Ściągamy blokadę po udanej rejestracji
        Session::remove('secret_reg_unlocked');

        if ($isVerified === 0) {
            require_once __DIR__ . '/../Services/MailerService.php';
            $mailer = new \CMS\Services\MailerService();
            $domain = $_SERVER['HTTP_HOST'] ?? 'domena.pl';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $verifyLink = "{$protocol}://{$domain}/verify-email?token={$token}";
            $body = "Witaj,<br><br>Dziękujemy za rejestrację.<br>Aby aktywować swoje konto, kliknij w poniższy link:<br><br><a href='{$verifyLink}'>{$verifyLink}</a>";
            $mailer->send($email, "Weryfikacja konta - " . $domain, $body);

            Session::setFlash('Konto zostało utworzone. Na Twój adres email wysłaliśmy link weryfikacyjny.', 'success');
            header('Location: /login');
            exit;
        }

        // Automatyczne logowanie po rejestracji, jeśli nie ma weryfikacji
        $userId = $db->getConnection()->lastInsertId();
        Session::set('user_id', $userId);
        Session::set('user_name', $email);
        Session::set('is_admin', 0);

        header('Location: /');
        exit;
    }
    
    public function verifyEmail() {
        Session::init();
        $token = $_GET['token'] ?? '';
        if (!$token) {
            header('Location: /');
            exit;
        }

        $db = \CMS\Core\Database::getInstance();
        $this->ensureVerificationColumnsExist($db);

        $user = $db->query("SELECT id FROM pa_users WHERE verification_token = :t", ['t' => $token])->fetch();

        if ($user) {
            $db->query("UPDATE pa_users SET is_verified = 1, verification_token = NULL WHERE id = :id", ['id' => $user['id']]);
            Session::setFlash('Konto zostało pomyślnie zweryfikowane. Możesz się zalogować.', 'success');
        } else {
            Session::setFlash('Nieprawidłowy lub wygasły link weryfikacyjny.', 'error');
        }
        
        header('Location: /login');
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
        
        // Sprawdzenie, czy ten sam adres IP nie prosił o reset ułamek czasu temu (cooldown)
        $ip = $_SERVER['REMOTE_ADDR'];
        $requireCaptcha = false;
        try {
            $db = \CMS\Core\Database::getInstance();
            $recentResets = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND username = '__reset_email__' AND attempt_time > DATE_SUB(NOW(), INTERVAL 2 MINUTE)", [$ip])->fetch()['c'];
            if ($recentResets > 0) {
                $requireCaptcha = true;
            }
        } catch (\Exception $e) {}

        require_once __DIR__ . '/../Views/auth/forgot_password.php';
    }

    public function sendResetLink() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        $db = \CMS\Core\Database::getInstance();
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Ochrona IP (zabezpieczenie przed floodowaniem zapytań o reset z automatu)
        $recentResets = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND username = '__reset_email__' AND attempt_time > DATE_SUB(NOW(), INTERVAL 2 MINUTE)", [$ip])->fetch()['c'];
        
        if ($recentResets > 0) {
            $expectedCaptcha = Session::get('reset_captcha');
            $providedCaptcha = strtolower(trim($_POST['captcha_answer'] ?? ''));
            if (!$expectedCaptcha || $providedCaptcha !== strtolower($expectedCaptcha)) {
                Session::setFlash('Nieprawidłowy kod z obrazka (Captcha). Odczekaj 2 minuty lub wpisz poprawny kod z obrazka.', 'error');
                header('Location: /forgot-password');
                exit;
            }
        }

        // Zapisanie faktu wysłania żądania resetu w logach anty-flood, co zablokuje ponowny request przez 2 min bez captchy
        $db->query("INSERT INTO pa_login_attempts (ip_address, username) VALUES (?, '__reset_email__')", [$ip]);
        Session::remove('reset_captcha');

        $email = trim($_POST['email'] ?? '');
        $this->ensureResetColumnsExist($db);

        $user = $db->query("SELECT id FROM pa_users WHERE email = :e", ['e' => $email])->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $db->query("UPDATE pa_users SET reset_token = :t, reset_expires = :d WHERE id = :id", [
                't' => $token,
                'd' => $expires,
                'id' => $user['id']
            ]);

            $domain = $_SERVER['HTTP_HOST'] ?? 'domena.pl';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $resetLink = "{$protocol}://{$domain}/reset-password?token={$token}";

            require_once __DIR__ . '/../Services/MailerService.php';
            $mailer = new \CMS\Services\MailerService();
            $body = "Witaj,<br><br>Otrzymaliśmy prośbę o reset hasła do Twojego konta.<br>Kliknij w poniższy link, aby ustawić nowe hasło (link wygasa za godzinę):<br><br><a href='{$resetLink}'>{$resetLink}</a><br><br>Jeśli to nie Ty prosiłeś o reset, zignoruj tę wiadomość.";
            $mailer->send($email, "Reset hasła - " . $domain, $body);
        }

        // Zawsze pokazujemy ten sam komunikat, aby nie zdradzać istnienia maili w bazie (bezpieczeństwo enumeracji kont)
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
            'p' => $hash,
            'id' => $user['id']
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