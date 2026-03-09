<?php
namespace CMS\Controllers;

use CMS\Core\Session;
use CMS\Core\Database;

class PostController {

    public function __construct() {
        Session::init();
        if (!Session::isLoggedIn() || Session::get('is_admin') != 1) {
            header('Location: /login');
            exit;
        }
    }

    // --- KATEGORIE ---
    public function categories() {
        $db = Database::getInstance();
        $categories = $db->query("SELECT * FROM pa_post_categories ORDER BY name ASC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/posts/categories.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function saveCategory() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        $db = Database::getInstance();
        $name = trim($_POST['name'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        
        if (!empty($_POST['id'])) {
            $db->query("UPDATE pa_post_categories SET name = ?, slug = ? WHERE id = ?", [$name, $slug, $_POST['id']]);
        } else {
            $db->query("INSERT INTO pa_post_categories (name, slug) VALUES (?, ?)", [$name, $slug]);
        }
        header("Location: /admin/categories");
    }

    public function deleteCategory() {
        $id = $_GET['id'] ?? 0;
        Database::getInstance()->query("DELETE FROM pa_post_categories WHERE id = ?", [$id]);
        header("Location: /admin/categories");
    }

    // --- POSTY ---
    public function index() {
        $db = Database::getInstance();
        $posts = $db->query("SELECT p.*, c.name as category_name FROM pa_posts p LEFT JOIN pa_post_categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/posts/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        $db = Database::getInstance();
        $db->query("INSERT INTO pa_posts (title, slug, contents) VALUES ('Nowy Wpis', 'nowy-wpis-" . time() . "', '[]')");
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/posts/edit?id=$id");
        exit;
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) die("ID Missing");
        
        $db = Database::getInstance();
        $post = $db->query("SELECT * FROM pa_posts WHERE id = :id", ['id' => $id])->fetch();
        if (!$post) die("Post not found");

        $categories = $db->query("SELECT * FROM pa_post_categories ORDER BY name ASC")->fetchAll();
        
        // Zmienne dla buildera
        $forms = $db->query("SELECT id, title FROM pa_forms ORDER BY id DESC")->fetchAll();
        $galleries = $db->query("SELECT id, title FROM pa_galleries ORDER BY id DESC")->fetchAll();

        require_once __DIR__ . '/../Views/admin/posts/edit.php';
    }

    public function save() {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!isset($data['id'])) {
            http_response_code(400); echo json_encode(['status' => 'error']); return;
        }
        $db = Database::getInstance();
        
        // Tworzenie sluga z tytułu jeśli pusty
        $slug = !empty($data['slug']) ? $data['slug'] : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $data['title']), '-'));

        $db->query("UPDATE pa_posts SET title = :title, slug = :slug, contents = :content, excerpt = :excerpt, thumbnail = :thumbnail, tags = :tags, category_id = :category_id, status = :status WHERE id = :id", [
            'title' => $data['title'],
            'slug' => $slug,
            'content' => json_encode($data['content']),
            'excerpt' => $data['excerpt'] ?? '',
            'thumbnail' => $data['thumbnail'] ?? '',
            'tags' => $data['tags'] ?? '',
            'category_id' => !empty($data['category_id']) ? (int)$data['category_id'] : null,
            'status' => $data['status'] ?? 'published',
            'id' => $data['id']
        ]);

        // CZYSZCZENIE CACHE
        $cacheFiles = glob(__DIR__ . '/../../public/cache/*.html');
        if (is_array($cacheFiles)) {
            foreach ($cacheFiles as $file) {
                if(is_file($file)) unlink($file);
            }
        }

        echo json_encode(['status' => 'success']);
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            Database::getInstance()->query("DELETE FROM pa_posts WHERE id = ?", [$id]);
        }
        header("Location: /admin/posts");
        exit;
    }
}