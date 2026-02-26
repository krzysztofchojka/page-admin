<?php
// Załadowanie nagłówka (sekcja <head>)
require __DIR__ . '/partials/header.php';

// ZBUDUJ STANDARDOWY NAVIGATOR W TLE
ob_start();
require __DIR__ . '/partials/navbar.php';
$navigatorHtml = ob_get_clean();

// ZBUDUJ CUSTOMOWE MENU (tylko linki)
$menuItems = \CMS\Core\Database::getInstance()->query("SELECT * FROM pa_menu ORDER BY sort_order ASC")->fetchAll();
$menuHtml = '';
foreach ($menuItems as $item) {
    $menuHtml .= '<a href="'.htmlspecialchars($item['url']).'" class="menu-item-link">'.htmlspecialchars($item['label']).'</a>';
}
?>
<main class="w-full flex-1">
    <?php
    $db = \CMS\Core\Database::getInstance();
    $pageContents = json_decode($page['contents'], true) ?? [];

    // 1. ZBUDUJ GLOBALNĄ STOPKĘ W TLE (Jako String HTML)
    ob_start();
    if (($settings['hide_footer'] ?? 0) != 1) {
        if (!empty($footerBlocks)) {
            echo '<footer class="mt-auto border-gray-200"><div class="w-full mx-auto pb-4 pt-10">';
            \CMS\Helpers\BlockRenderer::render($footerBlocks, $db);
            echo '</div></footer>';
        } else {
            echo '<footer class="bg-gray-100 text-center p-6 text-gray-500 text-sm mt-auto border-t">&copy; ' . date('Y') . ' ' . htmlspecialchars($settings['site_title'] ?? 'Strona') . '.</footer>';
        }
    }
    $globalFooterHtml = ob_get_clean();

    // 2. ZBUDUJ ADMIN NAVIGATOR (WordPress style)
    $adminNavHtml = '';
    \CMS\Core\Session::init();
    if (\CMS\Core\Session::get('is_admin') == 1) {
        $editLink = isset($page['id']) ? '/admin/pages/edit?id=' . $page['id'] : '/admin/pages';
        $userName = htmlspecialchars(\CMS\Core\Session::get('user_name'));
        $adminNavHtml = '
        <div style="background:#111827; color:#f3f4f6; font-size:13px; padding:8px 16px; display:flex; justify-content:space-between; align-items:center; position:relative; z-index:99999; border-bottom: 2px solid #3b82f6;">
            <div style="display:flex; gap: 15px; align-items:center;">
                <span style="font-weight:bold; color:#60a5fa;">CMS Admin</span>
                <span>Witaj, <b>'.$userName.'</b></span>
            </div>
            <div>
                <a href="'.$editLink.'" style="background:#3b82f6; color:#fff; padding:4px 12px; border-radius:4px; text-decoration:none; font-weight:bold; transition: background 0.2s;" onmouseover="this.style.background=\'#2563eb\'" onmouseout="this.style.background=\'#3b82f6\'">✏️ Edytuj tę stronę</a>
                <a href="/admin" style="margin-left:15px; color:#9ca3af; text-decoration:none; font-weight:bold;" onmouseover="this.style.color=\'#fff\'" onmouseout="this.style.color=\'#9ca3af\'">⚙️ Panel CMS</a>
            </div>
        </div>';
    }

    // 3. RENDEROWANIE ZAWARTOŚCI STRONY
    if (!empty($page['template_id'])) {
        // --- TRYB SZABLONU ---
        $tpl = $db->query("SELECT html_content FROM pa_templates WHERE id = ?", [$page['template_id']])->fetch();
        if ($tpl) {
            $html = $tpl['html_content'];
            
            // Podmiana specjalnych znaczników
            $html = str_replace('{{global_footer}}', $globalFooterHtml, $html);
            $html = str_replace('{{navigator}}', $navigatorHtml, $html);
            $html = str_replace('{{admin_navigator}}', $adminNavHtml, $html);
            $html = str_replace('{{menu}}', $menuHtml, $html);
            
            // Nowe znaczniki danych
            $html = str_replace('{{site_title}}', htmlspecialchars($settings['site_title'] ?? ''), $html);
            $html = str_replace('{{site_logo}}', htmlspecialchars($settings['site_logo'] ?? ''), $html);
            $html = str_replace('{{page_title}}', htmlspecialchars($page['title'] ?? ''), $html);
            $html = str_replace('{{user_name}}', htmlspecialchars(\CMS\Core\Session::get('user_name') ?? ''), $html);
            $html = str_replace('{{current_year}}', date('Y'), $html);
            $html = str_replace('{{color_primary}}', htmlspecialchars($settings['color_primary'] ?? '#f97316'), $html);
            $html = str_replace('{{color_secondary}}', htmlspecialchars($settings['color_secondary'] ?? '#1e3a8a'), $html);

            // Podmieniamy poszczególne strefy zrzutu (drop-zones) na ich bloki
            $html = preg_replace_callback('/\{\{zone:([a-zA-Z0-9_]+)\}\}/', function($matches) use ($pageContents, $db) {
                $zoneId = $matches[1];
                $blocksForZone = $pageContents[$zoneId] ?? [];
                
                // Fallback dla starych stron: Jeśli mamy płaską tablicę, wrzućmy ją do 'main'
                if (empty($blocksForZone) && array_keys($pageContents) === range(0, count($pageContents) - 1) && count($pageContents) > 0) {
                    if ($zoneId === 'editor' || $zoneId === 'main') {
                        $blocksForZone = $pageContents;
                    }
                }
                
                ob_start();
                \CMS\Helpers\BlockRenderer::render($blocksForZone, $db);
                return ob_get_clean();
            }, $html);

            echo $html;
        } else {
            echo '<div class="p-10 text-center text-red-500">Błąd: Przypisany szablon nie istnieje.</div>';
        }
    } else {
        // --- TRYB KLASYCZNY (Bez Szablonu) ---
        echo $adminNavHtml; 
        echo $navigatorHtml; 
        
        echo '<div class="max-w-6xl w-full mx-auto p-6 md:p-12">';
        $blocksToRender = $pageContents['editor'] ?? (isset($pageContents[0]) ? $pageContents : []);
        \CMS\Helpers\BlockRenderer::render($blocksToRender, $db);
        echo '</div>';
        
        echo $globalFooterHtml;
    }
    ?>
</main>

<?php
require __DIR__ . '/partials/footer.php';
?>