<?php
namespace CMS\Controllers;

use CMS\Core\Database;
use CMS\Core\Session;

class PublicController {

    public function show($slug = null) {
        Session::init();
        $db = Database::getInstance();

        // Zawsze pobieraj ustawienia dla całej strony i footera
        $settingsRows = $db->query("SELECT * FROM pa_settings")->fetchAll();
        $settings = [];
        foreach($settingsRows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }

        // --- GATE 1: LOCKDOWN ---
        if (($settings['lockdown_enabled'] ?? 0) == 1) {
            if (!Session::get('site_unlocked')) {
                if (isset($_POST['site_pass'])) {
                    if ($_POST['site_pass'] === ($settings['lockdown_password'] ?? '')) {
                        Session::set('site_unlocked', true);
                        header("Location: " . $_SERVER['REQUEST_URI']);
                        exit;
                    }
                }
                require_once __DIR__ . '/../Views/public/lockdown.php';
                return;
            }
        }

        // --- GATE 2: REGISTRATION ---
        if (($settings['require_registration'] ?? 0) == 1) {
            if (!Session::isLoggedIn()) {
                $path = $_SERVER['REQUEST_URI'];
                if (strpos($path, '/register') === false && strpos($path, '/login') === false) {
                    header("Location: /register");
                    exit;
                }
            }
        }

        // --- GATE 3: RENDER PAGE ---
        $page = null;
        if ($slug === '/' || $slug === null || $slug === '') {
            $page = $db->query("SELECT * FROM pa_data WHERE id = 1")->fetch(); 
        } else {
            $cleanSlug = ltrim($slug, '/');
            $page = $db->query("SELECT * FROM pa_data WHERE slug = :slug AND field_type = 'page'", ['slug' => $cleanSlug])->fetch();
        }

        if (!$page) {
            $id = $_GET['id'] ?? null;
            if ($id) $page = $db->query("SELECT * FROM pa_data WHERE id = :id", ['id' => $id])->fetch();
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
}