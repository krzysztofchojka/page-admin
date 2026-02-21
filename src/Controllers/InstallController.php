<?php
namespace CMS\Controllers;

use CMS\Core\Database;

class InstallController {
    public function index() {
        $db = Database::getInstance();
        
        try {
            // 1. Create Users Table (Compatible with your old structure but cleaner)
            $sql = "CREATE TABLE IF NOT EXISTS pa_users (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uname VARCHAR(50) NOT NULL UNIQUE,
                pass VARCHAR(255) NOT NULL,
                email VARCHAR(100),
                admin TINYINT(1) DEFAULT 0,
                pass_expired TINYINT(1) DEFAULT 1,
                reg_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $db->query($sql);
            echo "Table 'pa_users' created.<br>";

            // 2. Create Pages/Data Table (For saving your drag & drop pages later)
            $sql = "CREATE TABLE IF NOT EXISTS pa_data (
                id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                field_type VARCHAR(50) NOT NULL, 
                contents LONGTEXT,
                editor VARCHAR(50),
                create_date DATETIME,
                edit_date DATETIME
            )";
            $db->query($sql);
            echo "Table 'pa_data' created.<br>";

            // 3. Create Default Admin User
            // Check if admin exists first
            $check = $db->query("SELECT id FROM pa_users WHERE uname = 'admin'");
            if (!$check->fetch()) {
                $password = password_hash('admin', PASSWORD_DEFAULT);
                $db->query("INSERT INTO pa_users (uname, pass, email, admin, pass_expired) VALUES (:uname, :pass, :email, 1, 1)", [
                    'uname' => 'admin',
                    'pass' => $password,
                    'email' => 'admin@localhost'
                ]);
                echo "Default user 'admin' created with password 'admin'.<br>";
            } else {
                echo "User 'admin' already exists.<br>";
            }

            echo "<hr><strong>Installation Complete!</strong> <a href='/login'>Go to Login</a>";

        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }
}