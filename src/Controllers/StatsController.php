<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class StatsController {
    public function __construct() {
        Session::init();
        if (!Session::isLoggedIn() || Session::get('is_admin') != 1) {
            header('Location: /login');
            exit;
        }
    }

    public function index() {
        ob_start();
        require_once __DIR__ . '/../Views/admin/stats/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }

    public function getData() {
        header('Content-Type: application/json');
        $db = Database::getInstance();
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        
        // 1. Odwiedziny w czasie
        $visits = $db->query("
            SELECT visit_date as date, COUNT(*) as count 
            FROM pa_statistics 
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
            GROUP BY visit_date 
            ORDER BY visit_date ASC
        ", [$days])->fetchAll();

        // 2. Najpopularniejsze kraje
        $countries = $db->query("
            SELECT country_code, COUNT(*) as count 
            FROM pa_statistics 
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
            GROUP BY country_code 
            ORDER BY count DESC LIMIT 10
        ", [$days])->fetchAll();

        // 3. Najpopularniejsze podstrony
        $pages = $db->query("
            SELECT page_url, COUNT(*) as count 
            FROM pa_statistics 
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
            GROUP BY page_url 
            ORDER BY count DESC LIMIT 10
        ", [$days])->fetchAll();

        echo json_encode([
            'visits' => $visits,
            'countries' => $countries,
            'pages' => $pages
        ]);
        exit;
    }
}