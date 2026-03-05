<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class PublicController {

    public function show($slug = null) {
        Session::init();
        $db = Database::getInstance();
        $settingsRows = $db->query("SELECT * FROM pa_settings")->fetchAll();
        $settings = [];
        foreach($settingsRows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

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
                        exit; // Renderujemy naszą stronę i przerywamy!
                    }
                }
                // Fallback
                require_once __DIR__ . '/../Views/public/lockdown.php';
                exit;
            }
        }

        // --- GATE 2: REQUIRE REGISTRATION (Wymóg logowania) ---
        if (($settings['require_registration'] ?? 0) == 1) {
            if (!Session::isLoggedIn()) {
                Session::setFlash('Zaloguj się, aby uzyskać dostęp do zawartości.', 'error');
                header('Location: /login');
                exit;
            }
        }

        // --- GATE 3: RENDER PAGE ---
        $page = null;
        $id = $_GET['id'] ?? null;

        // Priorytet 1: Sprawdzamy, czy wywołano stronę z parametrem id (np. z menu /page?id=5)
        if ($id) {
            $page = $db->query("SELECT * FROM pa_data WHERE id = :id AND field_type = 'page'", ['id' => $id])->fetch();
        } else {
            // Priorytet 2: Wyszukiwanie po przyjaznym URL (Slug) lub ładowanie strony głównej (z ustawień lub id=1)
            if ($slug === '/' || $slug === null || $slug === '') {
                // Pobieramy ID strony głównej z ustawień. Jeśli nie istnieje, awaryjnie ładujemy stronę o ID 1
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

        // --- FOOTER INJECTION ---
        $footerBlocks = null;
        if (($settings['hide_footer'] ?? 0) != 1 && !empty($settings['footer_page_id'])) {
            $footerPage = $db->query("SELECT contents FROM pa_data WHERE id = :id", ['id' => $settings['footer_page_id']])->fetch();
            if ($footerPage && $footerPage['contents']) {
                $footerBlocks = json_decode($footerPage['contents'], true);
            }
        }

        // Pre-fetch Forms
        $formsData = [];
        foreach ($blocks as $block) {
            if ($block['type'] === 'form') {
                $formId = $block['content'];
                $formDef = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $formId])->fetch();
                if ($formDef) $formsData[$formId] = $formDef;
            }
        }

        require_once __DIR__ . '/../Views/public/page.php';
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