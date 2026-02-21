<?php
namespace CMS\Controllers;

use CMS\Core\Database;

class GalleryController {

    public function index() {
        $db = Database::getInstance();
        $galleries = $db->query("SELECT * FROM pa_galleries ORDER BY id DESC")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/galleries/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        $db = Database::getInstance();
        $db->query("INSERT INTO pa_galleries (title, images_json) VALUES ('New Gallery', '[]')");
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/galleries/edit?id=$id");
    }

    public function edit() {
        $id = $_GET['id'];
        $db = Database::getInstance();
        $gallery = $db->query("SELECT * FROM pa_galleries WHERE id = :id", ['id' => $id])->fetch();
        require_once __DIR__ . '/../Views/admin/galleries/edit.php';
    }

    public function save() {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $db->query("UPDATE pa_galleries SET title = :title, type = :type, images_json = :json WHERE id = :id", [
            'title' => $data['title'],
            'type' => $data['type'],
            'json' => json_encode($data['images']),
            'id' => $data['id']
        ]);
        echo json_encode(['status' => 'success']);
    }
}