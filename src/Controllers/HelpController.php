<?php
namespace CMS\Controllers;
use CMS\Core\Session;

class HelpController {
    public function index() {
        Session::init();
        if (!Session::isLoggedIn()) { header('Location: /login'); exit; }
        
        ob_start();
        require_once __DIR__ . '/../Views/admin/help/index.php';
        $content = ob_get_clean();
        require_once __DIR__ . '/../Views/admin/layout.php';
    }
}
?>