<?php
// 1. Simple Autoloader (Loads classes automatically)
spl_autoload_register(function ($class) {
    $prefix = 'CMS\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0) return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) require $file;
});

//Ładowanie zmiennych środowiskowych do globalnej tablicy $_ENV
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// 2. Initialize Router
use CMS\Core\Router;
use CMS\Controllers\HomeController;

$router = new CMS\Core\Router();

// Public Routes
//$router->get('/', [CMS\Controllers\HomeController::class, 'index']);
$router->get('/', [CMS\Controllers\PublicController::class, 'show']);
$router->get('/test-db', [CMS\Controllers\HomeController::class, 'testDb']);

// Auth Routes
$router->get('/login', [CMS\Controllers\AuthController::class, 'loginForm']); // Show Form
$router->post('/login', [CMS\Controllers\AuthController::class, 'login']);    // Process Login
$router->get('/logout', [CMS\Controllers\AuthController::class, 'logout']);

// Protected Admin Routes
$router->get('/admin', [CMS\Controllers\AdminController::class, 'index']);
$router->get('/install', [CMS\Controllers\InstallController::class, 'index']);
$router->get('/change-password', [CMS\Controllers\AuthController::class, 'changePasswordForm']);
$router->post('/change-password', [CMS\Controllers\AuthController::class, 'changePassword']);

$router->get('/admin/pages', [CMS\Controllers\PageController::class, 'index']);       // List pages
$router->get('/admin/pages/create', [CMS\Controllers\PageController::class, 'create']); // Create new
$router->get('/admin/pages/edit', [CMS\Controllers\PageController::class, 'edit']);     // Edit specific page
$router->post('/admin/pages/save', [CMS\Controllers\PageController::class, 'save']);    // AJAX Save
$router->get('/admin/pages/delete', [CMS\Controllers\PageController::class, 'delete']); // Delete

$router->get('/admin/forms', [CMS\Controllers\FormController::class, 'index']);
$router->get('/admin/forms/create', [CMS\Controllers\FormController::class, 'create']);
$router->get('/admin/forms/builder', [CMS\Controllers\FormController::class, 'builder']);
$router->post('/admin/forms/save', [CMS\Controllers\FormController::class, 'save']);
$router->post('/submit-form', [CMS\Controllers\FormController::class, 'submit']); // Public submission
$router->get('/admin/forms/submissions/delete', [CMS\Controllers\FormController::class, 'deleteSubmission']);

$router->get('/admin/forms/submissions', [CMS\Controllers\FormController::class, 'submissions']);
$router->get('/admin/forms/download', [CMS\Controllers\FormController::class, 'downloadFile']);

// Add a specific route for fetching pages by ID explicitly
$router->get('/page', [CMS\Controllers\PublicController::class, 'show']);

$router->post('/admin/media/upload', [CMS\Controllers\MediaController::class, 'upload']);
$router->get('/admin/menu', [CMS\Controllers\MenuController::class, 'index']);
$router->post('/admin/menu/save', [CMS\Controllers\MenuController::class, 'save']);

$router->get('/admin/galleries', [CMS\Controllers\GalleryController::class, 'index']);
$router->get('/admin/galleries/create', [CMS\Controllers\GalleryController::class, 'create']);
$router->get('/admin/galleries/edit', [CMS\Controllers\GalleryController::class, 'edit']);
$router->post('/admin/galleries/save', [CMS\Controllers\GalleryController::class, 'save']);

// Settings
$router->get('/admin/settings', [CMS\Controllers\SettingsController::class, 'index']);
$router->post('/admin/settings/save', [CMS\Controllers\SettingsController::class, 'save']);

// Media Manager
$router->get('/admin/media', [CMS\Controllers\MediaController::class, 'index']);
$router->post('/admin/media/delete', [CMS\Controllers\MediaController::class, 'delete']);
$router->post('/admin/media/createFolder', [CMS\Controllers\MediaController::class, 'createFolder']);
$router->post('/admin/media/rename', [CMS\Controllers\MediaController::class, 'rename']);
$router->post('/admin/media/move', [CMS\Controllers\MediaController::class, 'move']);

// User Manager
$router->get('/admin/users', [CMS\Controllers\UserController::class, 'index']);
$router->post('/admin/users/create', [CMS\Controllers\UserController::class, 'create']);
$router->get('/admin/users/delete', [CMS\Controllers\UserController::class, 'delete']);

$router->get('/register', [CMS\Controllers\AuthController::class, 'registerForm']);
$router->post('/register', [CMS\Controllers\AuthController::class, 'register']);

$router->get('/admin/media/downloadZip', [CMS\Controllers\MediaController::class, 'downloadZip']);

$router->setNotFoundHandler([CMS\Controllers\PublicController::class, 'show']);

$router->resolve();