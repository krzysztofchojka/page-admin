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
        $settings = $this->getSettings();

        // 1. Oczyszczanie wejścia
        $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';

        Session::set('old_login', $login);

        if (!$login || !$password) {
            Session::setFlash('Wypełnij wszystkie pola.');
            header('Location: /login');
            exit;
        }

        // --- OBSŁUGA UKRYTEJ REJESTRACJI ---
        $regMode = $settings['reg_mode'] ?? 'disabled';
        $secretLogin = trim($settings['reg_secret_login'] ?? '');
        $secretPass = $settings['reg_secret_pass'] ?? '';

        // Sprawdzamy, czy tryb to secret i czy dane logowania nie są puste w ustawieniach
        if ($regMode === 'secret' && $secretLogin !== '') {
            if ($login === $secretLogin && $password === $secretPass) {
                // Sukces: Odblokowujemy rejestrację i kierujemy na /register
                Session::set('secret_reg_unlocked', true);
                header('Location: /register');
                exit;
            } else {
                // Dodatkowe zabezpieczenie: Jeśli ktoś próbuje się logować (i błędnie),
                // upewniamy się, że blokada zostaje nałożona na nowo
                Session::remove('secret_reg_unlocked');
            }
        }

        // -------------------------------------
        // 2. Szukamy zwykłego użytkownika
        $userModel = new User();
        $user = $userModel->findByEmail($login);
        if (!$user) {
            $user = $userModel->findByUsername($login);
        }

        // 3. Weryfikacja hasła
        if ($user && password_verify($password, $user['pass'])) {
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

        // Błędne dane (zarówno dla trybu secret, jak i normalnego logowania)
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

    public function logout() {
        Session::init();
        Session::destroy();
        header('Location: /login');
        exit;
    }
}