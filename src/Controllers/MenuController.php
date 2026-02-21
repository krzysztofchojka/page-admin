<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class MenuController {

    public function index() {
        Session::init();
        if (!Session::isLoggedIn()) header('Location: /login');
        
        $db = Database::getInstance();
        
        // Fetch Menu Items
        $menuItems = $db->query("SELECT * FROM pa_menu ORDER BY sort_order ASC")->fetchAll();
        
        // Fetch Pages (for the dropdown selector)
        $pages = $db->query("SELECT id, title, slug FROM pa_data WHERE field_type = 'page'")->fetchAll();
        ob_start();
        require_once __DIR__ . '/../Views/admin/menu/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function save() {
        $data = json_decode(file_get_contents('php://input'), true);
        $db = Database::getInstance();

        // 1. Truncate Table (Simple approach: wipe and rewrite for menus)
        // For a huge menu, updates are better, but for <20 items, this is safest for ordering.
        $db->query("TRUNCATE TABLE pa_menu");

        // 2. Insert All
        $sql = "INSERT INTO pa_menu (label, url, sort_order) VALUES (:label, :url, :order)";
        $i = 0;
        foreach ($data['items'] as $item) {
            $db->query($sql, [
                'label' => $item['label'],
                'url' => $item['url'],
                'order' => $i++
            ]);
        }

        echo json_encode(['status' => 'success']);
    }
}