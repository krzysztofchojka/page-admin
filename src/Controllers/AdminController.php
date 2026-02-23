<?php
namespace CMS\Controllers;
use CMS\Core\Session;
use CMS\Core\Database;

class AdminController {
    public function index() {
        Session::init();
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $db = Database::getInstance();
        
        $stats = [
            'pages' => $db->query("SELECT COUNT(*) as c FROM pa_data WHERE field_type='page'")->fetch()['c'],
            'forms' => $db->query("SELECT COUNT(*) as c FROM pa_forms")->fetch()['c'],
            'users' => $db->query("SELECT COUNT(*) as c FROM pa_users")->fetch()['c']
        ];

        // DODANO: s.form_id do zapytania SELECT
        $recentSubmissions = $db->query("
            SELECT s.id, s.form_id, s.created_at, f.title as form_title, u.email as user_email
            FROM pa_submissions s
            JOIN pa_forms f ON s.form_id = f.id
            LEFT JOIN pa_users u ON s.user_id = u.id
            ORDER BY s.id DESC LIMIT 5
        ")->fetchAll();

        require_once __DIR__ . '/../Views/admin/index.php';
    }
}