<?php
namespace CMS\Controllers;

use CMS\Core\Database;

class GalleryController {

    private function ensureSettingsColumnExists($db) {
        try {
            // Automatyczna migracja: dodaje kolumnę settings jeśli nie istnieje
            $db->query("ALTER TABLE pa_galleries ADD COLUMN settings TEXT NULL AFTER type");
        } catch (\Exception $e) {
            // Kolumna już istnieje, ignorujemy błąd
        }
    }

    public function index() {
        $db = Database::getInstance();
        $this->ensureSettingsColumnExists($db);
        $galleries = $db->query("SELECT * FROM pa_galleries ORDER BY id DESC")->fetchAll();

        ob_start();
        require_once __DIR__ . '/../Views/admin/galleries/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function create() {
        $db = Database::getInstance();
        $this->ensureSettingsColumnExists($db);
        $db->query("INSERT INTO pa_galleries (title, images_json, settings) VALUES ('Nowa Galeria', '[]', '{}')");
        $id = $db->getConnection()->lastInsertId();
        header("Location: /admin/galleries/edit?id=$id");
    }

    public function edit() {
        $id = $_GET['id'];
        $db = Database::getInstance();
        $this->ensureSettingsColumnExists($db);
        $gallery = $db->query("SELECT * FROM pa_galleries WHERE id = :id", ['id' => $id])->fetch();
        
        require_once __DIR__ . '/../Views/admin/galleries/edit.php';
    }

    public function delete() {
        \CMS\Core\Session::init();
        $id = $_GET['id'] ?? 0;
        $db = \CMS\Core\Database::getInstance();

        // Sprawdzamy czy galeria jest osadzona w JSON na jakiejś stronie lub w poście
        $inPages = $db->query("SELECT COUNT(*) as c FROM pa_data WHERE contents LIKE ?", ['%"type":"gallery","content":"'.$id.'"%'])->fetch()['c'];
        $inPosts = $db->query("SELECT COUNT(*) as c FROM pa_posts WHERE contents LIKE ?", ['%"type":"gallery","content":"'.$id.'"%'])->fetch()['c'];

        if ($inPages > 0 || $inPosts > 0) {
            \CMS\Core\Session::setFlash("Nie można usunąć: Galeria jest osadzona na stronach lub we wpisach.", "error");
            header('Location: /admin/galleries');
            exit;
        }

        $db->query("DELETE FROM pa_galleries WHERE id = ?", [$id]);
        \CMS\Core\Session::setFlash("Galeria została usunięta.", "success");
        header('Location: /admin/galleries');
        exit;
    }

    public function save() {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();
        $this->ensureSettingsColumnExists($db);

        $db->query("UPDATE pa_galleries SET title = :title, type = :type, settings = :settings, images_json = :json WHERE id = :id", [
            'title' => $data['title'],
            'type' => $data['type'],
            'settings' => json_encode($data['settings'] ?? []),
            'json' => json_encode($data['images']),
            'id' => $data['id']
        ]);

        echo json_encode(['status' => 'success']);
    }
}