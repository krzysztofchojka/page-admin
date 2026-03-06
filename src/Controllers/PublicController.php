<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class PublicController {

    public function show($slug = null) {
        Session::init();
        $db = \CMS\Core\Database::getInstance();
        $settingsRows = $db->query("SELECT * FROM pa_settings")->fetchAll();
        $settings = [];
        foreach($settingsRows as $r) { $settings[$r['setting_key']] = $r['setting_value']; }

        // --- GATE 1: LOCKDOWN (Hasło globalne) ---
        if (($settings['lockdown_enabled'] ?? 0) == 1) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_pass'])) {
                if ($_POST['site_pass'] === ($settings['lockdown_password'] ?? '')) {
                    Session::set('site_unlocked', true);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }
            if (!Session::get('site_unlocked')) {
                if (!empty($settings['lockdown_page_id'])) {
                    $page = $db->query("SELECT * FROM pa_data WHERE id = :id", ['id' => $settings['lockdown_page_id']])->fetch();
                    if ($page) {
                        $blocks = json_decode($page['contents'], true) ?? [];
                        require_once __DIR__ . '/../Views/public/page.php';
                        exit; 
                    }
                }
                require_once __DIR__ . '/../Views/public/lockdown.php';
                exit;
            }
        }

        // --- GATE 2: REQUIRE REGISTRATION ---
        if (($settings['require_registration'] ?? 0) == 1) {
            if (!Session::isLoggedIn()) {
                Session::setFlash('Zaloguj się, aby uzyskać dostęp do zawartości.', 'error');
                header('Location: /login');
                exit;
            }
        }

        // --- LOGIKA CACHE ---
        $isAdmin = (Session::get('is_admin') == 1);
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        
        // Tworzymy unikalny klucz dla KROK PO KROKU każdego URL (np. /page?id=18)
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        $fullUri = $uri . ($qs ? '?' . $qs : '');
        $cacheKey = ($fullUri === '/' || $fullUri === '') ? 'index' : md5($fullUri);
        
        // Zmieniona ścieżka na folder public, gdzie PHP na pewno ma uprawnienia zapisu
        $cacheDir = __DIR__ . '/../../public/cache/';
        $cacheFile = $cacheDir . $cacheKey . '.html';

        // Serwuj cache tylko dla gości i przy metodzie GET
        if (!$isAdmin && !$isPost && file_exists($cacheFile)) {
            readfile($cacheFile);
            exit;
        }

        // --- POBIERANIE STRONY ---
        $page = null;
        $id = $_GET['id'] ?? null;
        if ($id) {
            $page = $db->query("SELECT * FROM pa_data WHERE id = :id AND field_type = 'page'", ['id' => $id])->fetch();
        } else {
            if ($slug === '/' || $slug === null || $slug === '') {
                $homePageId = $settings['home_page_id'] ?? 1;
                $page = $db->query("SELECT * FROM pa_data WHERE id = :id AND field_type = 'page'", ['id' => $homePageId])->fetch();
            } else {
                $cleanSlug = ltrim($slug, '/');
                $page = $db->query("SELECT * FROM pa_data WHERE slug = :slug AND field_type = 'page'", ['slug' => $cleanSlug])->fetch();
            }
        }

        if (!$page) {
            http_response_code(404);
            echo "<h1>404 - Page Not Found</h1>";
            return;
        }

        $blocks = json_decode($page['contents'], true) ?? [];

        // --- FIX DLA LOGÓW: Inteligentne szukanie formularzy ---
        $flattenedBlocks = [];
        if (isset($blocks[0])) {
            $flattenedBlocks = $blocks;
        } else {
            foreach ($blocks as $zone) {
                if (is_array($zone)) $flattenedBlocks = array_merge($flattenedBlocks, $zone);
            }
        }

        $formsData = [];
        foreach ($flattenedBlocks as $block) {
            if (isset($block['type']) && $block['type'] === 'form') {
                $formId = $block['content'];
                $formDef = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
                if ($formDef) $formsData[$formId] = $formDef;
            }
        }

        // --- FOOTER INJECTION ---
        $footerBlocks = null;
        if (($settings['hide_footer'] ?? 0) != 1 && !empty($settings['footer_page_id'])) {
            $footerPage = $db->query("SELECT contents FROM pa_data WHERE id = :id", ['id' => $settings['footer_page_id']])->fetch();
            if ($footerPage && $footerPage['contents']) {
                $footerBlocks = json_decode($footerPage['contents'], true);
            }
        }

        // --- RENDEROWANIE I ZAPIS CACHE ---
        ob_start();
        require_once __DIR__ . '/../Views/public/page.php';
        $htmlOutput = ob_get_clean();

        // Zapisujemy cache tylko w idealnych warunkach dla gości
        if (!$isAdmin && !$isPost) {
            if (!is_dir($cacheDir)) { 
                mkdir($cacheDir, 0775, true); 
                // Chronimy ten folder przed odczytem bezpośrednim przez przeglądarkę
                file_put_contents($cacheDir . '.htaccess', "Require all denied");
            }
            $htmlOutput .= "\n";
            
            try {
                file_put_contents($cacheFile, $htmlOutput);
            } catch (\Exception $e) {
                // ignorujemy błąd zapisu żeby strona dalej działała
            }
        }

        echo $htmlOutput;
    }

    public function showPost() {
        $slug = $_GET['slug'] ?? null;
        if (!$slug) die("Nie wybrano postu.");

        Session::init();
        $db = Database::getInstance();
        $settingsRows = $db->query("SELECT * FROM pa_settings")->fetchAll();
        $settings = [];
        foreach($settingsRows as $r) $settings[$r['setting_key']] = $r['setting_value'];

        // Pobieramy post niezależnie od jego statusu
        $post = $db->query("SELECT p.*, c.name as category_name FROM pa_posts p LEFT JOIN pa_post_categories c ON p.category_id = c.id WHERE p.slug = :slug", ['slug' => $slug])->fetch();
        
        // Jeśli post nie istnieje w ogóle w bazie -> 404
        if (!$post) {
            http_response_code(404); echo "<h1>404 - Post nie istnieje.</h1>"; return;
        }

        // Sprawdzamy czy zalogowany użytkownik jest administratorem
        $isAdmin = (Session::get('is_admin') == 1);

        // Jeśli to szkic, a odwiedzający NIE JEST adminem -> blokujemy dostęp (404)
        if ($post['status'] !== 'published' && !$isAdmin) {
            http_response_code(404); echo "<h1>404 - Post nie istnieje lub jest szkicem.</h1>"; return;
        }

        // Trik: Udajemy stronę klasyczną by użyć starego `page.php`
        $page = [
            'title' => $post['title'],
            'contents' => $post['contents'],
            'template_id' => null // Posty domyślnie korzystają z klasycznego widoku
        ];

        $blocks = json_decode($post['contents'], true) ?? [];
        
        $footerBlocks = null;
        if (($settings['hide_footer'] ?? 0) != 1 && !empty($settings['footer_page_id'])) {
            $footerPage = $db->query("SELECT contents FROM pa_data WHERE id = :id", ['id' => $settings['footer_page_id']])->fetch();
            if ($footerPage && $footerPage['contents']) {
                $footerBlocks = json_decode($footerPage['contents'], true);
            }
        }

        // Przygotowujemy żółty pasek informacyjny, który pokaże się tylko przy szkicach
        $draftWarning = '';
        if ($post['status'] !== 'published') {
            $draftWarning = '
                <div class="bg-yellow-100 text-yellow-800 border-b-2 border-yellow-400 p-3 text-center font-bold text-sm shadow-sm">
                    ⚠️ UWAGA: Ten post jest obecnie SZKICEM. Widzą go tylko zalogowani administratorzy. Zwykli użytkownicy otrzymają błąd 404.
                </div>
            ';
        }

        // Wstrzyknięcie nagłówka blogowego (oraz ewentualnego ostrzeżenia) DO GŁÓWNEGO EDYTORA na pierwszą pozycję
        $headerBlock = [
            'type' => 'raw_html',
            'content' => $draftWarning . '
                <div class="mb-10 text-center max-w-3xl mx-auto pt-10">
                    '.($post['category_name'] ? '<span class="text-blue-600 font-bold uppercase tracking-widest text-xs block mb-2">'.htmlspecialchars($post['category_name']).'</span>' : '').'
                    <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-4 leading-tight">'.htmlspecialchars($post['title']).'</h1>
                    <p class="text-gray-500 font-medium">Opublikowano: '.date('d.m.Y', strtotime($post['created_at'])).'</p>
                </div>
            '
        ];
        
        // Zabezpiecz format bloków
        if (isset($blocks['editor'])) {
            array_unshift($blocks['editor'], $headerBlock);
        } else {
            array_unshift($blocks, $headerBlock);
        }
        $page['contents'] = json_encode($blocks); // Zapisujemy z powrotem

        require_once __DIR__ . '/../Views/public/page.php';
    }
}