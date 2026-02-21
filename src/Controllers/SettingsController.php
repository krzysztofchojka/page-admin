<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class SettingsController {
    
    public function index() {
        Session::init();
        if (!Session::isLoggedIn()) header('Location: /login');
        
        $db = Database::getInstance();
        $rows = $db->query("SELECT * FROM pa_settings")->fetchAll();
        
        $settings = [];
        foreach($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];

        // Pobieramy strony do dropdownu stopki
        $pages = $db->query("SELECT id, title FROM pa_data WHERE field_type = 'page' ORDER BY title ASC")->fetchAll();

        ob_start();
        require_once __DIR__ . '/../Views/admin/settings/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function save() {
        $db = Database::getInstance();
        
        foreach ($_POST as $key => $value) {
            // Fix: Use REPLACE INTO to avoid parameter reuse issues
            // This acts as "Insert or Update" automatically
            $db->query("REPLACE INTO pa_settings (setting_key, setting_value) VALUES (:key, :val)", [
                'key' => $key, 
                'val' => $value
            ]);
        }
        
        header('Location: /admin/settings?success=1');
        exit;
    }
}