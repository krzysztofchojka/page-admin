<?php
namespace CMS\Middleware;
use CMS\Core\Session;

class AuthMiddleware {
    public function handle() {
        Session::init();
        if (!Session::isLoggedIn()) {
            Session::setFlash('Zaloguj się, aby uzyskać dostęp.', 'error');
            header('Location: /login');
            exit;
        }
    }
}
?>