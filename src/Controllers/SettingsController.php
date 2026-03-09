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
        
        $pages = $db->query("SELECT id, title FROM pa_data WHERE field_type = 'page' ORDER BY title ASC")->fetchAll();
        
        ob_start();
        require_once __DIR__ . '/../Views/admin/settings/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function save() {
        // 1. Sprawdzamy token
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        // 2. Usuwamy token z POST, żeby nie zapisał się do bazy jako ustawienie!
        unset($_POST['csrf_token']); 

        $db = Database::getInstance();
        foreach ($_POST as $key => $value) {
            $db->query("REPLACE INTO pa_settings (setting_key, setting_value) VALUES (:key, :val)", [
                'key' => $key,
                'val' => $value
            ]);
        }

        // CZYSZCZENIE CACHE
        $cacheFiles = glob(__DIR__ . '/../../public/cache/*.html');
        if (is_array($cacheFiles)) {
            foreach ($cacheFiles as $file) {
                if(is_file($file)) unlink($file);
            }
        }

        header('Location: /admin/settings?success=1');
        exit;
    }

    // --- ULEPSZONY BACKUP (Dynamiczny) ---
    public function backup() {
        Session::init();
        if (!Session::isLoggedIn()) die("Odmowa dostępu");

        $db = Database::getInstance()->getConnection();
        
        // 1. Pobierz listę WSZYSTKICH tabel w bazie (automatycznie wykrywa nowe)
        $tables = [];
        $query = $db->query('SHOW TABLES');
        while($row = $query->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sqlDump = "-- CMS Auto Backup\n-- Data: " . date('Y-m-d H:i:s') . "\n";
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n"; // Wyłączamy sprawdzanie kluczy na czas importu

        foreach ($tables as $table) {
            // A. Zrzut struktury (DROP + CREATE)
            $row = $db->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_NUM);
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $row[1] . ";\n\n";

            // B. Zrzut danych (INSERT)
            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $keys = array_keys($row);
                $values = array_map(function($v) use ($db) {
                    if ($v === null) return "NULL";
                    return $db->quote($v);
                }, array_values($row));
                
                $sqlDump .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
            }
            $sqlDump .= "\n";
        }
        
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="cms_backup_'.date('Y-m-d_H-i').'.sql"');
        echo $sqlDump;
        exit;
    }

    public function restore() {
        Session::init();
        \CMS\Core\Session::verifyCsrfToken($_POST['csrf_token'] ?? '');
        if (!Session::isLoggedIn()) die("Odmowa dostępu");

        // 1. Sprawdź czy plik w ogóle dotarł
        if (empty($_FILES['backup_file']['tmp_name']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['backup_file']['error'] ?? 'Nieznany';
            Session::setFlash("Błąd uploadu pliku! Kod błędu: $errCode (Sprawdź upload_max_filesize w php.ini)", 'error');
            header('Location: /admin/settings');
            exit;
        }

        $fileContent = file_get_contents($_FILES['backup_file']['tmp_name']);
        if (!$fileContent) {
            Session::setFlash('Plik jest pusty lub nie można go odczytać.', 'error');
            header('Location: /admin/settings');
            exit;
        }

        $db = Database::getInstance()->getConnection();

        try {
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);

            // Wyłączamy sprawdzanie kluczy obcych
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Wykonujemy cały wsad SQL bez instrukcji beginTransaction() i commit(), 
            // ponieważ DROP TABLE i tak wymusza auto-commit w MySQL.
            $db->exec($fileContent);
            
            // Włączamy sprawdzanie z powrotem
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");

            Session::setFlash('Sukces! Baza danych została pomyślnie przywrócona.', 'success');
        } catch (\Exception $e) {
            // Rejestrujemy dokładny błąd SQL, z pominięciem rollBack()
            Session::setFlash('Błąd SQL podczas przywracania: ' . $e->getMessage(), 'error');
        }

        header('Location: /admin/settings');
        exit;
    }
}