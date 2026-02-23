<?php
namespace CMS\Controllers;

use CMS\Core\Session;
use CMS\Core\Database;

class PageController {

    public function __construct() {
        Session::init();
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    public function index() {
        $db = Database::getInstance();
        $pages = $db->query("SELECT * FROM pa_data WHERE field_type = 'page' ORDER BY id DESC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/pages/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        $db = Database::getInstance();
        // Pobieramy szablony do wyboru
        $templates = $db->query("SELECT id, title FROM pa_templates ORDER BY title ASC")->fetchAll();
        
        ob_start();
        require_once __DIR__ . '/../Views/admin/pages/create.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function store() {
        $db = Database::getInstance();
        $user = Session::get('user_name');
        
        $title = $_POST['title'] ?? 'Nowa strona';
        $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;

        $db->query("INSERT INTO pa_data (title, field_type, contents, template_id, editor, create_date, edit_date) 
                    VALUES (:title, 'page', '[]', :tid, :editor, NOW(), NOW())", [
            'title' => $title,
            'tid' => $templateId,
            'editor' => $user
        ]);
        
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/pages/edit?id=$id");
        exit;
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) die("ID Missing");

        $db = Database::getInstance();
        $page = $db->query("SELECT * FROM pa_data WHERE id = :id", ['id' => $id])->fetch();

        if (!$page) die("Page not found");

        // *** NEW: Fetch Lists for Dropdowns ***
        $forms = $db->query("SELECT id, title FROM pa_forms ORDER BY id DESC")->fetchAll();
        $galleries = $db->query("SELECT id, title FROM pa_galleries ORDER BY id DESC")->fetchAll();

        $templates = $db->query("SELECT id, title FROM pa_templates ORDER BY title ASC")->fetchAll();

        require_once __DIR__ . '/../Views/admin/pages/edit.php';
    }

    public function save() {
        // Odbieramy JSON z frontendu
        $data = json_decode(file_get_contents('php://input'), true);
    
        if (!isset($data['id']) || !isset($data['content'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid Data']);
            return;
        }
    
        $db = Database::getInstance();
        
        // Pobieramy template_id, jeśli istnieje (rzutujemy na int lub null)
        $templateId = !empty($data['template_id']) ? (int)$data['template_id'] : null;
    
        // Aktualizujemy rekord w bazie (dodano kolumnę template_id)
        $db->query("UPDATE pa_data SET title = :title, slug = :slug, contents = :content, template_id = :tid, edit_date = NOW() WHERE id = :id", [
            'title'   => $data['title'],
            'slug'    => $data['slug'],
            'content' => json_encode($data['content']),
            'tid'     => $templateId,
            'id'      => $data['id']
        ]);
    
        echo json_encode(['status' => 'success']);
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $db = Database::getInstance();
            $db->query("DELETE FROM pa_data WHERE id = :id", ['id' => $id]);
        }
        header("Location: /admin/pages");
        exit;
    }
}