<?php
namespace CMS\Controllers;
use CMS\Core\Database;

class UserController {
    public function index() {
        $users = Database::getInstance()->query("SELECT * FROM pa_users")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/users/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }
    
    public function create() {
        $u = $_POST['username'];
        $p = password_hash($_POST['password'], PASSWORD_DEFAULT);
        Database::getInstance()->query("INSERT INTO pa_users (uname, pass, admin) VALUES (?, ?, 1)", [$u, $p]);
        header('Location: /admin/users');
    }

    public function delete() {
        $id = $_GET['id'];
        if ($id != $_SESSION['user_id']) { // Don't delete self
            Database::getInstance()->query("DELETE FROM pa_users WHERE id = ?", [$id]);
        }
        header('Location: /admin/users');
    }
}