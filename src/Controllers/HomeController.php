<?php
namespace CMS\Controllers;

use CMS\Core\Database;

class HomeController {
    public function index() {
        echo "<h1>CMS 2.0 Foundation is Live!</h1>";
        echo "<p>MVC Architecture is working.</p>";
    }

    public function testDb() {
        try {
            $db = Database::getInstance();
            // Just a test query
            $stmt = $db->query("SELECT 1 as val");
            $res = $stmt->fetch();
            echo "Database Connection Successful! Value: " . $res['val'];
        } catch (\Exception $e) {
            echo "DB Error: " . $e->getMessage();
        }
    }
}

?>