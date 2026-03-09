<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class MenuController
{
    public function index()
    {
        Session::init();
        if (!Session::isLoggedIn()) header('Location: /login');

        $db = Database::getInstance();

        // Automigracja - dodajemy kolumnę parent_id (jeśli nie istnieje)
        try {
            $db->query("ALTER TABLE pa_menu ADD COLUMN parent_id INT(11) NULL DEFAULT NULL AFTER id");
        } catch (\Exception $e) {
            // Kolumna prawdopodobnie już istnieje, ignorujemy błąd
        }

        // Pobieramy wszystkie pozycje menu
        $all = $db->query("SELECT * FROM pa_menu ORDER BY sort_order ASC")->fetchAll();
        
        // Budujemy strukturę drzewa do zagnieżdżania w panelu
        $menuItems = [];
        $menuById = [];
        foreach ($all as $item) {
            $item['children'] = [];
            $menuById[$item['id']] = $item;
        }
        foreach ($menuById as $id => &$item) {
            if (!empty($item['parent_id']) && isset($menuById[$item['parent_id']])) {
                $menuById[$item['parent_id']]['children'][] = &$item;
            } else {
                $menuItems[] = &$item;
            }
        }

        // Fetch Pages (for the dropdown selector)
        $pages = $db->query("SELECT id, title, slug FROM pa_data WHERE field_type = 'page'")->fetchAll();

        ob_start();
        require_once __DIR__ . '/../Views/admin/menu/index.php';
        $content = ob_get_clean();

        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function save()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        // Upewniamy się, że tabela obsługuje zagnieżdżenia
        try {
            $db->query("ALTER TABLE pa_menu ADD COLUMN parent_id INT(11) NULL DEFAULT NULL AFTER id");
        } catch (\Exception $e) {}

        // Wyczyść starą tabelę i zbuduj na nowo (najprostsze wyjście przy zarządzaniu całym menu naraz)
        $db->query("TRUNCATE TABLE pa_menu");

        $order = 0;
        
        // Funkcja rekurencyjna do zapisywania zagnieżdżonych elementów
        $insertItem = function($item, $parentId) use ($db, &$order, &$insertItem) {
            $db->query("INSERT INTO pa_menu (label, url, sort_order, parent_id) VALUES (:label, :url, :order, :parent)", [
                'label' => $item['label'],
                'url' => $item['url'],
                'order' => $order++,
                'parent' => $parentId
            ]);
            $newId = $db->getConnection()->lastInsertId();

            if (!empty($item['children'])) {
                foreach ($item['children'] as $child) {
                    $insertItem($child, $newId);
                }
            }
        };

        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $insertItem($item, null);
            }
        }

        // CZYSZCZENIE CACHE
        $cacheFiles = glob(__DIR__ . '/../../public/cache/*.html');
        if (is_array($cacheFiles)) {
            foreach ($cacheFiles as $file) {
                if(is_file($file)) unlink($file);
            }
        }

        echo json_encode(['status' => 'success']);
    }
}