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

        // Kompatybilność MariaDB <-> MySQL: kolacje uca1400 (MariaDB 10.10+) nie istnieją w MySQL.
        $isMaria = stripos((string)$db->getAttribute(\PDO::ATTR_SERVER_VERSION), 'mariadb') !== false;
        if (!$isMaria) {
            $fileContent = preg_replace('/\butf8mb4_uca1400_[a-z_]+\b/i', 'utf8mb4_unicode_ci', $fileContent);
            $fileContent = preg_replace('/\butf8mb3_uca1400_[a-z_]+\b/i', 'utf8mb3_unicode_ci', $fileContent);
        }

        $statements = self::splitSql($fileContent);
        $done = 0;

        try {
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");

            // Każde zapytanie osobno – PDO::exec() z wieloma zapytaniami naraz
            // zgłasza błąd tylko pierwszego, a resztę po cichu porzuca.
            foreach ($statements as $stmt) {
                $db->exec($stmt);
                $done++;
            }

            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            Session::setFlash("Sukces! Baza danych została przywrócona ($done zapytań).", 'success');
        } catch (\Exception $e) {
            $failed = mb_substr($statements[$done] ?? '', 0, 120);
            Session::setFlash('Błąd SQL podczas przywracania (zapytanie #' . ($done + 1) . ': ' . $failed . '…): ' . $e->getMessage(), 'error');
        }

        $cacheFiles = glob(__DIR__ . '/../../public/cache/*.html');
        foreach ($cacheFiles ?: [] as $file) {
            if (is_file($file)) unlink($file);
        }

        header('Location: /admin/settings');
        exit;
    }

    /**
     * Dzieli zrzut SQL na pojedyncze zapytania z uwzględnieniem
     * stringów ('...', "...", `...`) i komentarzy (--, #, /* *\/).
     */
    private static function splitSql(string $sql): array {
        $out = [];
        $buf = '';
        $len = strlen($sql);
        $quote = null;

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];

            if ($quote !== null) {
                $buf .= $c;
                if ($c === '\\' && $quote !== '`' && $i + 1 < $len) {
                    $buf .= $sql[++$i];
                } elseif ($c === $quote) {
                    if ($i + 1 < $len && $sql[$i + 1] === $quote) { $buf .= $sql[++$i]; }
                    else { $quote = null; }
                }
                continue;
            }

            if ($c === "'" || $c === '"' || $c === '`') { $quote = $c; $buf .= $c; continue; }

            if (($c === '-' && substr($sql, $i, 3) === '-- ') || $c === '#' || ($c === '-' && substr($sql, $i, 3) === "--\n")) {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                $buf .= "\n";
                continue;
            }
            if ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $i = $end === false ? $len : $end + 1;
                continue;
            }

            if ($c === ';') {
                if (trim($buf) !== '') $out[] = trim($buf);
                $buf = '';
                continue;
            }
            $buf .= $c;
        }
        if (trim($buf) !== '') $out[] = trim($buf);
        return $out;
    }
}