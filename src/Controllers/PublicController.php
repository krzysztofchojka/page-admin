<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class PublicController {

    public function show($slug = null) {
    Session::init();
    \CMS\Helpers\Tracker::logVisit($_SERVER['REQUEST_URI'] ?? '/');

    $db = \CMS\Core\Database::getInstance();
    $settingsRows = $db->query("SELECT * FROM pa_settings")->fetchAll();
    $settings = [];
    foreach($settingsRows as $r) {
        $settings[$r['setting_key']] = $r['setting_value'];
    }

    // --- GATE 1: LOCKDOWN (Hasło globalne) ---
    if (($settings['lockdown_enabled'] ?? 0) == 1) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Auto-migracja na wypadek gdyby Lockdown był szybszy niż pierwsze logowanie admina
        try {
            $db->query("CREATE TABLE IF NOT EXISTS pa_login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                username VARCHAR(255) NOT NULL,
                attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        } catch (\Exception $e) {}

        if (!Session::get('site_unlocked')) {
            // Twarda blokada (30 błędów)
            $ipFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 60 MINUTE)", [$ip])->fetch()['c'];
            if ($ipFails >= 30) {
                http_response_code(429);
                die("Zbyt wiele prób wpisania hasła. Dostęp zablokowany na godzinę.");
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['site_pass'])) {
                // Miękka blokada (Sprawdzanie Captchy)
                $recentFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND username = '__lockdown__' AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetch()['c'];
                
                $captchaValid = true;
                if ($recentFails >= 3) {
                    $expected = Session::get('lockdown_captcha');
                    $provided = strtolower(trim($_POST['captcha_answer'] ?? ''));
                    if (!$expected || $provided !== strtolower($expected)) {
                        $captchaValid = false;
                    }
                }

                if ($captchaValid && $_POST['site_pass'] === ($settings['lockdown_password'] ?? '')) {
                    // Sukces - odblokowanie
                    $db->query("DELETE FROM pa_login_attempts WHERE ip_address = ? AND username = '__lockdown__'", [$ip]);
                    Session::remove('lockdown_captcha');
                    Session::set('site_unlocked', true);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                } else {
                    // Błąd
                    $db->query("INSERT INTO pa_login_attempts (ip_address, username) VALUES (?, '__lockdown__')", [$ip]);
                    Session::setFlash($captchaValid ? 'Nieprawidłowy kod dostępu.' : 'Nieprawidłowy kod z obrazka (Captcha).', 'error');
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }

            // Renderowanie bloku lub domyślnej strony lockdown
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
        $cacheDir = __DIR__ . '/../../public/cache/';
        $cacheFile = $cacheDir . $cacheKey . '.html';

        // 1. Sprawdzamy czy gość ma aktywne szkice w sesji lub komunikaty Flash
        $hasSessionData = isset($_SESSION['flash']);
        if (!$hasSessionData) {
            foreach ($_SESSION as $key => $val) {
                if (strpos($key, 'draft_') === 0) {
                    $hasSessionData = true;
                    break;
                }
            }
        }

        // 2. Serwuj cache tylko dla gości, bez aktywnych szkiców
        if (!$isAdmin && !$isPost && !$hasSessionData && file_exists($cacheFile)) {
            $cachedContent = file_get_contents($cacheFile);
            // Omijamy cache, jeśli strona ma formularz (aby nie zepsuć tokenów CSRF i szkiców)
            if (strpos($cachedContent, 'id="form-container-') === false) {
                echo $cachedContent;
                exit;
            }
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

        // 3. Zapisujemy cache tylko w idealnych warunkach i pomijamy strony z formularzami
        if (!$isAdmin && !$isPost && strpos($htmlOutput, 'id="form-container-') === false) {
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0775, true); 
                file_put_contents($cacheDir . '.htaccess', "Require all denied");
            }
            $htmlOutput .= "\n";
            try {
                file_put_contents($cacheFile, $htmlOutput);
            } catch (\Exception $e) {}
        }

        echo $htmlOutput;
    }

    public function showPost() {
        $slug = $_GET['slug'] ?? null;
        if (!$slug) die("Nie wybrano postu.");

        Session::init();
        \CMS\Helpers\Tracker::logVisit($_SERVER['REQUEST_URI'] ?? '/');
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

    public function favicon() {
        $db = \CMS\Core\Database::getInstance();
        $settingsRows = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'site_favicon'")->fetch();
        $faviconUrl = $settingsRows ? $settingsRows['setting_value'] : '';

        // Domyślny SVG
        $defaultSvg = '<svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <rect width="48" height="48" fill="white" fill-opacity="0.01"></rect> <path d="M24 44C35.0457 44 44 35.0457 44 24C44 12.9543 35.0457 4 24 4C12.9543 4 4 12.9543 4 24C4 35.0457 12.9543 44 24 44Z" stroke="#c1c1c1" stroke-width="4"></path> <path d="M4 20.8404C7.01485 19.4168 9.24466 19.2185 10.6894 20.2454C12.8566 21.7859 13.1283 28.064 18.0575 25.0635C22.9867 22.063 15.9467 20.8404 17.475 16.4939C19.0033 12.1474 24.0083 15.5237 24.5059 10.7627C24.8375 7.58862 21.0408 6.37413 13.1156 7.11921" stroke="#c1c1c1" stroke-width="4"></path> <path d="M36.0001 8C30.2857 12.9886 28.2899 16.0011 30.0127 17.0373C32.5968 18.5917 33.6933 16.4033 36.8467 17.0373C40.0001 17.6714 39.3173 21.9457 37.6587 21.9457C36.0001 21.9457 27.41 20.8518 27.8427 25.865C28.2753 30.8781 33.4422 31.6203 33.4422 34.4211C33.4422 36.2883 32.299 39.146 30.0127 42.9942" stroke="#c1c1c1" stroke-width="4" stroke-linecap="round"></path> <path d="M6.10461 32.9264C7.0161 32.5288 7.70127 32.2374 8.16011 32.052C12.0072 30.4978 14.8618 30.1314 16.7237 30.953C20.0162 32.4059 18.7504 35.3401 19.7817 36.4211C20.8129 37.5021 23.3881 37.1876 23.3881 39.244C23.3881 40.615 22.9276 42.1637 22.0066 43.8901" stroke="#c1c1c1" stroke-width="4" stroke-linecap="round"></path> </g></svg>';

        // Jeśli ustawiono w systemie własny plik
        if (!empty($faviconUrl)) {
            $filePath = __DIR__ . '/../../public' . $faviconUrl;
            if (file_exists($filePath)) {
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $mime = 'image/x-icon'; // domyślnie
                
                // Dynamiczne mapowanie na prawidłowy format
                if ($ext === 'png') $mime = 'image/png';
                elseif ($ext === 'svg') $mime = 'image/svg+xml';
                elseif (in_array($ext, ['jpg', 'jpeg'])) $mime = 'image/jpeg';
                elseif ($ext === 'gif') $mime = 'image/gif';
                elseif ($ext === 'webp') $mime = 'image/webp';

                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=86400'); // Cache na 24h
                readfile($filePath);
                exit;
            }
        }

        // Zwróć domyślny systemowy SVG
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=86400');
        echo $defaultSvg;
        exit;
    }
}

