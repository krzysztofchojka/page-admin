<?php
namespace CMS\Controllers;

use PDO;
use CMS\Core\Database;

class InstallController {

    // Zmieniona metoda index - teraz przyjmuje opcjonalne parametry do zachowania stanu
    public function index($errorMsg = '', $host = 'localhost', $name = '', $user = '', $pass = '') {
        $lockFile = __DIR__ . '/../../install.lock';
        $envPath = __DIR__ . '/../../.env';

        // 1. Ochrona: Jeśli lock-file istnieje, zablokuj
        if (file_exists($lockFile)) {
            http_response_code(403);
            die("<h1>Odmowa dostępu</h1><p>System został już pomyślnie zainstalowany. Aby zainstalować go ponownie, usuń plik install.lock.</p>");
        }

        // 2. Automatyzacja dla CI/CD: Jeśli .env istnieje, ale nie ma lock-file, odpal tabele z automatu
        if (file_exists($envPath) && empty($errorMsg)) {
            try {
                $db = Database::getInstance();
                $this->runInstallation($db->getConnection());
                echo "<hr><strong style='color:green'>Instalacja bazy w trybie automatycznym (CI/CD) powiodła się!</strong>";
            } catch (\Exception $e) {
                echo "<strong style='color:red'>Błąd autoinstalacji: </strong> " . $e->getMessage();
            }
            return;
        }

        // 3. Obsługa komunikatów o błędach (także tych z Get dla starych przekierowań)
        $error = !empty($errorMsg) ? $errorMsg : ($_GET['error'] ?? '');
        $errorHtml = '';
        if (!empty($error)) {
            $errorHtml = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm font-bold shadow-sm">⚠️ ' . htmlspecialchars($error) . '</div>';
        }

        // 4. Zabezpieczenie danych z formularza przed atakami XSS
        $safeHost = htmlspecialchars($host);
        $safeName = htmlspecialchars($name);
        $safeUser = htmlspecialchars($user);
        $safePass = htmlspecialchars($pass);

        echo <<<HTML
        <!DOCTYPE html>
        <html lang="pl">
        <head>
            <meta charset="UTF-8">
            <title>Kreator Instalacji CMS</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
            <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl max-w-lg w-full border-t-4 border-blue-600">
                <div class="text-center mb-8">
                    <div class="text-5xl mb-3">🚀</div>
                    <h1 class="text-3xl font-extrabold text-gray-800 tracking-tight">Kreator CMS</h1>
                    <p class="text-sm text-gray-500 mt-2 leading-relaxed">Połączmy Twój nowy system z bazą danych. System automatycznie wygeneruje klucze bezpieczeństwa.</p>
                </div>
                
                $errorHtml
                
                <form action="/install" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Serwer Bazy Danych (Host)</label>
                        <input type="text" name="db_host" value="$safeHost" required class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white text-gray-800">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Nazwa Bazy Danych</label>
                        <input type="text" name="db_name" value="$safeName" required class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white text-gray-800" placeholder="np. strona_db">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Użytkownik MySQL</label>
                        <input type="text" name="db_user" value="$safeUser" required class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white text-gray-800" placeholder="np. root">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Hasło</label>
                        <input type="password" name="db_pass" value="$safePass" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition bg-gray-50 focus:bg-white text-gray-800" placeholder="Wpisz hasło...">
                    </div>
                    <div class="pt-4 mt-6 border-t border-gray-100">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 text-lg">
                            Zainstaluj System
                        </button>
                    </div>
                </form>
            </div>
        </body>
        </html>
        HTML;
    }

    public function process() {
        $lockFile = __DIR__ . '/../../install.lock';
        if (file_exists($lockFile)) die("Zainstalowano.");

        $host = $_POST['db_host'] ?? 'localhost';
        $name = $_POST['db_name'] ?? '';
        $user = $_POST['db_user'] ?? '';
        $pass = $_POST['db_pass'] ?? '';

        // 1. Sprawdzamy czy wpisane dane są poprawne (bez redirectu przy błędzie!)
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
        } catch (\Exception $e) {
            // Zamiast redirect, odpalamy widok bezpośrednio z zachowanymi danymi
            return $this->index("Błąd połączenia z bazą: " . $e->getMessage(), $host, $name, $user, $pass);
        }

        // 2. Generowanie 256-bitowego klucza i zapis pliku .env
        $key = 'base64:' . base64_encode(random_bytes(32));
        $envContent = "DB_HOST={$host}\nDB_NAME={$name}\nDB_USER={$user}\nDB_PASS={$pass}\nAPP_ENV=production\nAPP_KEY=\"{$key}\"\n";
        
        $envPath = __DIR__ . '/../../.env';
        if (@file_put_contents($envPath, $envContent) === false) {
            return $this->index("Błąd zapisu! Brak uprawnień do utworzenia pliku .env. Zmień uprawnienia CHMOD na 775.", $host, $name, $user, $pass);
        }

        // 3. Budowa architektury bazy
        try {
            $this->runInstallation($pdo);
            header("Location: /login");
            exit;
        } catch (\Exception $e) {
            return $this->index("Błąd tworzenia tabel w bazie: " . $e->getMessage(), $host, $name, $user, $pass);
        }
    }

    private function runInstallation(PDO $pdo) {
        // Tabele Główne
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uname VARCHAR(50) NOT NULL UNIQUE, pass VARCHAR(255) NOT NULL, email VARCHAR(100), admin TINYINT(1) DEFAULT 0, pass_expired TINYINT(1) DEFAULT 1, reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_data (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255), field_type VARCHAR(50) NOT NULL, contents LONGTEXT, template_id INT DEFAULT NULL, editor VARCHAR(50), create_date DATETIME, edit_date DATETIME)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_templates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), html_content LONGTEXT, is_active TINYINT(1) DEFAULT 1)");

        // Architektura Systemowa
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_sessions (id VARCHAR(128) PRIMARY KEY, data TEXT, last_accessed INT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_menu (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, parent_id INT NULL, label VARCHAR(255), url VARCHAR(255), sort_order INT DEFAULT 0)");

        // Formularze i Galerie
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_forms (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), form_json LONGTEXT, settings TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_submissions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, form_id INT, user_id INT NULL, user_ip VARCHAR(50), status VARCHAR(20) DEFAULT 'submitted', data_json LONGTEXT, files_json LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_galleries (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), type VARCHAR(50), settings TEXT, images_json LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

        // Blog / Posty
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_post_categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_posts (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT NULL, title VARCHAR(255), slug VARCHAR(255), excerpt TEXT, contents LONGTEXT, thumbnail VARCHAR(255), tags VARCHAR(255), status VARCHAR(20) DEFAULT 'published', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

        // System Mailingowy
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_email_templates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), subject VARCHAR(255), body LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_email_queue (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_id INT, list_id INT NULL, custom_emails TEXT, status VARCHAR(20) DEFAULT 'pending', scheduled_for DATETIME DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_email_logs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, queue_id INT, user_email VARCHAR(255), status VARCHAR(20), error_message TEXT, sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_mailing_lists (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), is_default TINYINT(1) DEFAULT 0)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_mailing_subscribers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, list_id INT, email VARCHAR(255), name VARCHAR(255))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_sent_emails (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, recipient VARCHAR(255), subject VARCHAR(255), body LONGTEXT, sent_at DATETIME DEFAULT CURRENT_TIMESTAMP)");

        // Statystyki
        $pdo->exec("CREATE TABLE IF NOT EXISTS pa_statistics (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            visit_date DATE NOT NULL,
            visitor_hash VARCHAR(64) NOT NULL,
            page_url VARCHAR(255) NOT NULL,
            country_code VARCHAR(2) DEFAULT 'XX',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_visit (visit_date, visitor_hash, page_url)
        );");

        // Rekordy domyślne
        $pdo->exec("INSERT IGNORE INTO pa_data (id, title, slug, field_type, contents, create_date, edit_date) VALUES (1, 'Strona Główna', '/', 'page', '[]', NOW(), NOW())");
        $pdo->exec("INSERT IGNORE INTO pa_mailing_lists (id, name, is_default) VALUES (1, 'Użytkownicy Systemu', 1)");

        // Dodanie domyślnego użytkownika ADMIN (tylko jeśli nie istnieje)
        $checkAdmin = $pdo->query("SELECT id FROM pa_users WHERE uname = 'admin'");
        if (!$checkAdmin->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO pa_users (uname, pass, email, admin, pass_expired) VALUES (:uname, :pass, :email, 1, 0)");
            $stmt->execute([
                'uname' => 'admin',
                'pass' => password_hash('admin', PASSWORD_DEFAULT),
                'email' => 'admin@localhost'
            ]);
        }

        // Dodanie domyślnego użytkownika USER (potrzebny do testów E2E i uprawnień)
        $checkUser = $pdo->query("SELECT id FROM pa_users WHERE uname = 'user'");
        if (!$checkUser->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO pa_users (uname, pass, email, admin, pass_expired) VALUES (:uname, :pass, :email, 0, 0)");
            $stmt->execute([
                'uname' => 'user',
                'pass' => password_hash('user123', PASSWORD_DEFAULT),
                'email' => 'user@localhost'
            ]);
        }

        // Zamknięcie procesu w postaci pliku blokującego instalator
        file_put_contents(__DIR__ . '/../../install.lock', "Zainstalowano: " . date('Y-m-d H:i:s'));
    }
}