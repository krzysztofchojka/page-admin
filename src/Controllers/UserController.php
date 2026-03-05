<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class UserController {
    public function index() {
        $db = Database::getInstance();
        $admins = $db->query("SELECT * FROM pa_users WHERE admin = 1 ORDER BY id DESC")->fetchAll();
        $regularUsers = $db->query("SELECT * FROM pa_users WHERE admin = 0 ORDER BY id DESC")->fetchAll();

        ob_start();
        require_once __DIR__ . '/../Views/admin/users/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
    
        $u = trim($_POST['username']);
        $e = trim($_POST['email'] ?? '');
        $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        Database::getInstance()->query(
            "INSERT INTO pa_users (uname, email, pass, admin) VALUES (?, ?, ?, 1)",
            [$u, $e, $p]
        );
        
        Session::setFlash('Administrator został utworzony.', 'success');
        header('Location: /admin/users');
    }

    public function edit() {
        $id = $_GET['id'] ?? 0;
        $user = Database::getInstance()->query("SELECT * FROM pa_users WHERE id = ?", [$id])->fetch();
        if (!$user) {
            header('Location: /admin/users');
            exit;
        }

        ob_start();
        require_once __DIR__ . '/../Views/admin/users/edit.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function update() {
        $id = $_POST['id'];
        $uname = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? ''); 
        $pass = $_POST['password'] ?? '';
        $forceChange = isset($_POST['force_change']) ? 1 : 0;
        $isAdmin = isset($_POST['is_admin']) ? 1 : 0;

        $db = Database::getInstance();

        if (!empty($pass)) {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $db->query("UPDATE pa_users SET uname = ?, email = ?, pass = ?, admin = ?, pass_expired = ? WHERE id = ?", 
                [$uname, $email, $hash, $isAdmin, $forceChange, $id]);
        } else {
            $db->query("UPDATE pa_users SET uname = ?, email = ?, admin = ?, pass_expired = ? WHERE id = ?", 
                [$uname, $email, $isAdmin, $forceChange, $id]);
        }

        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        Session::setFlash('Użytkownik został zaktualizowany.', 'success');
        header('Location: /admin/users');
    }

    public function delete() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $id = $_GET['id'];
        
        if ($id != Session::get('user_id')) { // Zapobiega usunięciu samego siebie
            Database::getInstance()->query("DELETE FROM pa_users WHERE id = ?", [$id]);
        }
        header('Location: /admin/users');
    }
}