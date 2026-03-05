<?php
namespace CMS\Middleware;
use CMS\Core\Session;

class AdminMiddleware {
    public function handle() {
        Session::init();
        if (!Session::isLoggedIn()) {
            header('Location: /login');
            exit;
        }
        if (Session::get('is_admin') != 1) {
            header('Location: /');
            exit;
        }
    }
}
?>