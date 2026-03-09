<?php

// 1. Załaduj zewnętrzne biblioteki z Composera
$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// 2. Prosty Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'CMS\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// 3. Sprawdzenie i ładowanie pliku .env
$envPath = __DIR__ . '/../.env';
$envExists = file_exists($envPath);

if ($envExists) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// KONTROLA INSTALACJI: Jeśli nie ma .env, wymuś przekierowanie na kreator instalacji
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!$envExists && $currentPath !== '/install') {
    header('Location: /install');
    exit;
}

// 4. Inicjalizacja Routera i import klas
use CMS\Core\Router;
use CMS\Middleware\AuthMiddleware;
use CMS\Middleware\AdminMiddleware;

use CMS\Controllers\HomeController;
use CMS\Controllers\PublicController;
use CMS\Controllers\AuthController;
use CMS\Controllers\AdminController;
use CMS\Controllers\InstallController;
use CMS\Controllers\PageController;
use CMS\Controllers\FormController;
use CMS\Controllers\TemplateController;
use CMS\Controllers\MenuController;
use CMS\Controllers\GalleryController;
use CMS\Controllers\SettingsController;
use CMS\Controllers\MediaController;
use CMS\Controllers\UserController;
use CMS\Controllers\EmailController;
use CMS\Controllers\PostController;
use CMS\Controllers\HelpController;
use CMS\Controllers\StatsController;

$router = new Router();

// =========================================================================
// TRASY PUBLICZNE (Dostępne dla każdego)
// =========================================================================
$router->get('/', [PublicController::class, 'show']);
$router->get('/page', [PublicController::class, 'show']);
$router->get('/post', [PublicController::class, 'showPost']);
$router->get('/test-db', [HomeController::class, 'testDb']);

// KREATOR INSTALACJI
$router->get('/install', [InstallController::class, 'index']); 
$router->post('/install', [InstallController::class, 'process']);

// Logowanie i Rejestracja
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->post('/register', [AuthController::class, 'register']);

// Formularze od strony klienta
$router->post('/submit-form', [FormController::class, 'submit']);
$router->post('/form-upload', [FormController::class, 'asyncUpload']);
$router->post('/form-autosave', [FormController::class, 'autosave']);

// =========================================================================
// TRASY ZALOGOWANEGO UŻYTKOWNIKA (Wymagają AuthMiddleware)
// =========================================================================
$router->get('/change-password', [AuthController::class, 'changePasswordForm']);
$router->post('/change-password', [AuthController::class, 'changePassword']);

// =========================================================================
// TRASY ADMINISTRATORA (Wymagają AdminMiddleware)
// =========================================================================
$router->get('/admin', [AdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/help', [HelpController::class, 'index'], [AdminMiddleware::class]);

// Strony (Pages)
$router->get('/admin/pages', [PageController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/pages/create', [PageController::class, 'create'], [AdminMiddleware::class]);
$router->post('/admin/pages/create', [PageController::class, 'store'], [AdminMiddleware::class]);
$router->get('/admin/pages/edit', [PageController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/pages/save', [PageController::class, 'save'], [AdminMiddleware::class]);
$router->get('/admin/pages/delete', [PageController::class, 'delete'], [AdminMiddleware::class]);

// Szablony (Templates)
$router->get('/admin/templates', [TemplateController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/templates/create', [TemplateController::class, 'create'], [AdminMiddleware::class]);
$router->get('/admin/templates/edit', [TemplateController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/templates/save', [TemplateController::class, 'save'], [AdminMiddleware::class]);
$router->post('/admin/templates/delete', [TemplateController::class, 'delete'], [AdminMiddleware::class]);
$router->post('/admin/templates/toggleActive', [TemplateController::class, 'toggleActive'], [AdminMiddleware::class]);

// Formularze
$router->get('/admin/forms', [FormController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/forms/create', [FormController::class, 'create'], [AdminMiddleware::class]);
$router->get('/admin/forms/builder', [FormController::class, 'builder'], [AdminMiddleware::class]);
$router->post('/admin/forms/save', [FormController::class, 'save'], [AdminMiddleware::class]);
$router->get('/admin/forms/delete', [FormController::class, 'delete'], [AdminMiddleware::class]);
$router->get('/admin/forms/submissions', [FormController::class, 'submissions'], [AdminMiddleware::class]);
$router->get('/admin/forms/submissions/delete', [FormController::class, 'deleteSubmission'], [AdminMiddleware::class]);
$router->post('/admin/forms/submissions/export', [FormController::class, 'exportSubmissions'], [AdminMiddleware::class]);
$router->post('/admin/forms/submissions/export-files', [FormController::class, 'exportFiles'], [AdminMiddleware::class]);
$router->get('/form-download', [FormController::class, 'downloadFile']);

// Galerie
$router->get('/admin/galleries', [GalleryController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/galleries/create', [GalleryController::class, 'create'], [AdminMiddleware::class]);
$router->get('/admin/galleries/edit', [GalleryController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/galleries/save', [GalleryController::class, 'save'], [AdminMiddleware::class]);
$router->get('/admin/galleries/delete', [GalleryController::class, 'delete'], [AdminMiddleware::class]);

// Wpisy (Posty/Blog)
$router->get('/admin/categories', [PostController::class, 'categories'], [AdminMiddleware::class]);
$router->post('/admin/categories/save', [PostController::class, 'saveCategory'], [AdminMiddleware::class]);
$router->get('/admin/categories/delete', [PostController::class, 'deleteCategory'], [AdminMiddleware::class]);
$router->get('/admin/posts', [PostController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/posts/create', [PostController::class, 'create'], [AdminMiddleware::class]);
$router->post('/admin/posts/store', [PostController::class, 'store'], [AdminMiddleware::class]);
$router->get('/admin/posts/edit', [PostController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/posts/save', [PostController::class, 'save'], [AdminMiddleware::class]);
$router->get('/admin/posts/delete', [PostController::class, 'delete'], [AdminMiddleware::class]);

// Menu
$router->get('/admin/menu', [MenuController::class, 'index'], [AdminMiddleware::class]);
$router->post('/admin/menu/save', [MenuController::class, 'save'], [AdminMiddleware::class]);

// Pliki (Media Manager)
$router->get('/admin/media', [MediaController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/media/downloadZip', [MediaController::class, 'downloadZip'], [AdminMiddleware::class]);
$router->post('/admin/media/upload', [MediaController::class, 'upload'], [AdminMiddleware::class]);
$router->post('/admin/media/delete', [MediaController::class, 'delete'], [AdminMiddleware::class]);
$router->post('/admin/media/createFolder', [MediaController::class, 'createFolder'], [AdminMiddleware::class]);
$router->post('/admin/media/rename', [MediaController::class, 'rename'], [AdminMiddleware::class]);
$router->post('/admin/media/move', [MediaController::class, 'move'], [AdminMiddleware::class]);

// Użytkownicy
$router->get('/admin/users', [UserController::class, 'index'], [AdminMiddleware::class]);
$router->post('/admin/users/create', [UserController::class, 'create'], [AdminMiddleware::class]);
$router->get('/admin/users/delete', [UserController::class, 'delete'], [AdminMiddleware::class]);
$router->get('/admin/users/edit', [UserController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/users/update', [UserController::class, 'update'], [AdminMiddleware::class]);

// Ustawienia & Backup
$router->get('/admin/settings', [SettingsController::class, 'index'], [AdminMiddleware::class]);
$router->post('/admin/settings/save', [SettingsController::class, 'save'], [AdminMiddleware::class]);
$router->get('/admin/settings/backup', [SettingsController::class, 'backup'], [AdminMiddleware::class]);
$router->post('/admin/settings/restore', [SettingsController::class, 'restore'], [AdminMiddleware::class]);

// System Email / IMAP / SMTP
$router->get('/admin/email', [EmailController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/email/queue', [EmailController::class, 'queue'], [AdminMiddleware::class]);
$router->post('/admin/email/schedule', [EmailController::class, 'schedule'], [AdminMiddleware::class]);
$router->get('/admin/email/trigger', [EmailController::class, 'triggerJob'], [AdminMiddleware::class]);
$router->get('/admin/email/fetch-imap', [EmailController::class, 'fetchEmails'], [AdminMiddleware::class]);
$router->get('/admin/email/read', [EmailController::class, 'readEmail'], [AdminMiddleware::class]);
$router->post('/admin/email/send-direct', [EmailController::class, 'sendDirect'], [AdminMiddleware::class]);
$router->get('/admin/email/fetch-sent', [EmailController::class, 'fetchSentEmails'], [AdminMiddleware::class]);
$router->get('/admin/email/templates', [EmailController::class, 'templates'], [AdminMiddleware::class]);
$router->get('/admin/email/templates/create', [EmailController::class, 'createTemplate'], [AdminMiddleware::class]);
$router->get('/admin/email/templates/edit', [EmailController::class, 'editTemplate'], [AdminMiddleware::class]);
$router->post('/admin/email/templates/save', [EmailController::class, 'saveTemplate'], [AdminMiddleware::class]);
$router->get('/admin/email/templates/delete', [EmailController::class, 'deleteTemplate'], [AdminMiddleware::class]);
$router->get('/admin/email/lists', [EmailController::class, 'lists'], [AdminMiddleware::class]);
$router->post('/admin/email/lists/create', [EmailController::class, 'createList'], [AdminMiddleware::class]);
$router->get('/admin/email/lists/manage', [EmailController::class, 'manageList'], [AdminMiddleware::class]);
$router->post('/admin/email/lists/add-subscriber', [EmailController::class, 'addSubscriber'], [AdminMiddleware::class]);
$router->get('/admin/email/lists/remove-subscriber', [EmailController::class, 'removeSubscriber'], [AdminMiddleware::class]);

// Statystyki
$router->get('/admin/stats', [StatsController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/stats/data', [StatsController::class, 'getData'], [AdminMiddleware::class]);

// Odzyskliwanie hasła
$router->get('/forgot-password', [AuthController::class, 'forgotPasswordForm']);
$router->post('/forgot-password', [AuthController::class, 'sendResetLink']);
$router->get('/reset-password', [AuthController::class, 'resetPasswordForm']);
$router->post('/reset-password', [AuthController::class, 'updatePassword']);

// =========================================================================
// FALLBACK (Błąd 404 lub aliasy z bazy)
// =========================================================================
$router->setNotFoundHandler([PublicController::class, 'show']);

// Rozwiązanie Requestu
$router->resolve();