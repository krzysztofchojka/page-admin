<?php
namespace CMS\Controllers;

use CMS\Core\Session;

class AdminController {
    
    public function index() {
        Session::init();
        
        // Gatekeeper: If not logged in, kick them out
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../Views/admin/index.php';
    }
}