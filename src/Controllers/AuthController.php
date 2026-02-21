<?php
namespace CMS\Controllers;

use CMS\Core\Session;
use CMS\Models\User;

class AuthController {
    
    public function loginForm() {
        Session::init();
        if (Session::isLoggedIn()) {
            header('Location: /admin');
            exit;
        }
        // Load the view
        require_once __DIR__ . '/../Views/auth/login.php';
    }

    public function login() {
        Session::init();
        
        // 1. Sanitize Input
        $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';

        if (!$login || !$password) {
            Session::setFlash('Please fill in all fields.');
            header('Location: /login');
            exit;
        }

        // 2. Find User (Check Email first, then Username)
        $userModel = new User();
        $user = $userModel->findByEmail($login);
        
        if (!$user) {
            $user = $userModel->findByUsername($login);
        }

        // 3. Verify Password
        if ($user && password_verify($password, $user['pass'])) {
            
            // SECURITY CHECK: Is password expired?
            if ($user['pass_expired'] == 1) {
                // Store user ID temporarily in session to allow password change
                Session::set('temp_user_id', $user['id']);
                header('Location: /change-password');
                exit;
            }

            // Success!
            session_regenerate_id(true); 
            Session::set('user_id', $user['id']);
            Session::set('user_name', $user['uname']);
            Session::set('is_admin', $user['admin']);

            header('Location: /admin');
            exit;
        }

        // Failure
        Session::setFlash('Invalid login credentials.');
        header('Location: /login');
        exit;
    }

    public function changePasswordForm() {
        Session::init();
        // Security: Only allow if we have a temporary user ID pending from login
        if (!Session::get('temp_user_id')) {
            header('Location: /login');
            exit;
        }
        require_once __DIR__ . '/../Views/auth/change_password.php';
    }

    public function registerForm() {
        require_once __DIR__ . '/../Views/auth/register.php';
    }

    public function register() {
        Session::init();
        $db = \CMS\Core\Database::getInstance();

        $email = $_POST['email'];
        $pass  = $_POST['password'];
        $pass2 = $_POST['confirm_password'];

        // Basic Validation
        if ($pass !== $pass2) {
            Session::setFlash('Passwords do not match');
            header('Location: /register');
            exit;
        }

        // Check if exists
        $exists = $db->query("SELECT id FROM pa_users WHERE email = :e", ['e' => $email])->fetch();
        if ($exists) {
            Session::setFlash('Email already registered');
            header('Location: /register');
            exit;
        }

        // Create User (Admin = 0)
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $db->query("INSERT INTO pa_users (uname, email, pass, admin, pass_expired) VALUES (:u, :e, :p, 0, 0)", [
            'u' => $email, // Use email as username for clients
            'e' => $email,
            'p' => $hash
        ]);

        // Auto Login
        $userId = $db->getConnection()->lastInsertId();
        Session::set('user_id', $userId);
        Session::set('user_name', $email);
        Session::set('is_admin', 0); // Client

        header('Location: /');
        exit;
    }

    public function changePassword() {
        Session::init();
        $userId = Session::get('temp_user_id');
        
        if (!$userId) {
            header('Location: /login');
            exit;
        }

        $pass1 = $_POST['pass1'] ?? '';
        $pass2 = $_POST['pass2'] ?? '';

        // Basic Validation
        if ($pass1 !== $pass2) {
            Session::setFlash('Passwords do not match.');
            header('Location: /change-password');
            exit;
        }
        if (strlen($pass1) < 6) {
            Session::setFlash('Password must be at least 6 characters.');
            header('Location: /change-password');
            exit;
        }

        // Update Database
        $db = \CMS\Core\Database::getInstance();
        $hash = password_hash($pass1, PASSWORD_DEFAULT);
        
        // Update pass AND set pass_expired to 0
        $db->query("UPDATE pa_users SET pass = :pass, pass_expired = 0 WHERE id = :id", [
            'pass' => $hash,
            'id' => $userId
        ]);

        // Clean up temp session and log them in for real
        Session::remove('temp_user_id');
        
        // Fetch user details to set the real session
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