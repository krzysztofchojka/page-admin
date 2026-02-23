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

    public function backup() {
        Session::init();
        if (!Session::isLoggedIn()) die("Odmowa dostępu");

        $db = Database::getInstance()->getConnection();
        
        // Tabele do zrzutu
        $tables = ['pa_users', 'pa_data', 'pa_forms', 'pa_submissions', 'pa_galleries', 'pa_settings', 'pa_templates', 'pa_menu'];
        $sqlDump = "-- Automatyczny Backup CMS \n-- Wygenerowano: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            try {
                $rows = $db->query("SELECT * FROM $table")->fetchAll(\PDO::FETCH_ASSOC);
                if (count($rows) == 0) continue;
                
                foreach ($rows as $row) {
                    $keys = array_keys($row);
                    $values = array_map(function($v) use ($db) {
                        return $v === null ? 'NULL' : $db->quote($v);
                    }, array_values($row));
                    
                    $sqlDump .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
                }
                $sqlDump .= "\n";
            } catch (\Exception $e) {
                // Tabela może nie istnieć, idziemy dalej
            }
        }

        // Nagłówki wymuszające pobieranie pliku
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="cms_backup_'.date('Y-m-d_H-i').'.sql"');
        echo $sqlDump;
        exit;
    }
}