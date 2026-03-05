<?php
namespace CMS\Controllers;
use CMS\Core\Database;

class InstallController {
    public function index() {
        $db = Database::getInstance();
        try {
            // 1. Tabele Główne
            $db->query("CREATE TABLE IF NOT EXISTS pa_users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, uname VARCHAR(50) NOT NULL UNIQUE, pass VARCHAR(255) NOT NULL, email VARCHAR(100), admin TINYINT(1) DEFAULT 0, pass_expired TINYINT(1) DEFAULT 1, reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_data (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, slug VARCHAR(255), field_type VARCHAR(50) NOT NULL, contents LONGTEXT, template_id INT DEFAULT NULL, editor VARCHAR(50), create_date DATETIME, edit_date DATETIME)");
            
            // 2. Architektura Systemowa
            $db->query("CREATE TABLE IF NOT EXISTS pa_sessions (id VARCHAR(128) PRIMARY KEY, data TEXT, last_accessed INT)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_settings (setting_key VARCHAR(50) PRIMARY KEY, setting_value TEXT)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_menu (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, parent_id INT NULL, label VARCHAR(255), url VARCHAR(255), sort_order INT DEFAULT 0)");
            
            // 3. Formularze i Galerie
            $db->query("CREATE TABLE IF NOT EXISTS pa_forms (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), form_json LONGTEXT, settings TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_submissions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, form_id INT, user_id INT NULL, user_ip VARCHAR(50), status VARCHAR(20) DEFAULT 'submitted', data_json LONGTEXT, files_json LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_galleries (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), type VARCHAR(50), settings TEXT, images_json LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            
            // 4. Blog / Posty
            $db->query("CREATE TABLE IF NOT EXISTS pa_post_categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), slug VARCHAR(255))");
            $db->query("CREATE TABLE IF NOT EXISTS pa_posts (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT NULL, title VARCHAR(255), slug VARCHAR(255), excerpt TEXT, contents LONGTEXT, thumbnail VARCHAR(255), tags VARCHAR(255), status VARCHAR(20) DEFAULT 'published', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            
            // 5. System Mailingowy
            $db->query("CREATE TABLE IF NOT EXISTS pa_email_templates (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), subject VARCHAR(255), body LONGTEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_email_queue (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, template_id INT, list_id INT NULL, custom_emails TEXT, status VARCHAR(20) DEFAULT 'pending', scheduled_for DATETIME DEFAULT CURRENT_TIMESTAMP)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_email_logs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, queue_id INT, user_email VARCHAR(255), status VARCHAR(20), error_message TEXT, sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_mailing_lists (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), is_default TINYINT(1) DEFAULT 0)");
            $db->query("CREATE TABLE IF NOT EXISTS pa_mailing_subscribers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, list_id INT, email VARCHAR(255), name VARCHAR(255))");
            $db->query("CREATE TABLE IF NOT EXISTS pa_sent_emails (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, recipient VARCHAR(255), subject VARCHAR(255), body LONGTEXT, sent_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
            // Utworzenie domyślnej strony głównej
            $db->query("INSERT IGNORE INTO pa_data (id, title, slug, field_type, contents, create_date, edit_date) VALUES (1, 'Strona Główna', '/', 'page', '[]', NOW(), NOW())");

            // Dodaj domyślną listę systemową
            $db->query("INSERT IGNORE INTO pa_mailing_lists (id, name, is_default) VALUES (1, 'Użytkownicy Systemu', 1)");

            // Utworzenie Admina (jeśli nie istnieje)
            $check = $db->query("SELECT id FROM pa_users WHERE uname = 'admin'");
            if (!$check->fetch()) {
                $password = password_hash('admin', PASSWORD_DEFAULT);
                $db->query("INSERT INTO pa_users (uname, pass, email, admin, pass_expired) VALUES (:uname, :pass, :email, 1, 1)", [
                    'uname' => 'admin', 
                    'pass' => $password, 
                    'email' => 'admin@localhost'
                ]);
                echo "Użytkownik 'admin' utworzony.<br>";
            }

            $checkUser = $db->query("SELECT id FROM pa_users WHERE uname = 'user'");
            if (!$checkUser->fetch()) {
                $userPass = password_hash('user123', PASSWORD_DEFAULT);
                $db->query("INSERT INTO pa_users (uname, pass, email, admin, pass_expired) VALUES (:uname, :pass, :email, 0, 0)", [
                    'uname' => 'user', 
                    'pass' => $userPass, 
                    'email' => 'user@localhost'
                ]);
            }
            
            echo "<hr><strong style='color:green'>Instalacja bazy kompletna!</strong>";
        } catch (\Exception $e) {
            http_response_code(500);
            echo "Błąd instalacji: " . $e->getMessage();
        }
    }
}