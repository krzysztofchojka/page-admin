<?php 
// Załadowanie nagłówka i menu
require __DIR__ . '/partials/header.php'; 
require __DIR__ . '/partials/navbar.php'; 
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

    // 2. RENDEROWANIE ZAWARTOŚCI STRONY
    if (!empty($page['template_id'])) {
        // --- TRYB SZABLONU ---
        $tpl = $db->query("SELECT html_content FROM pa_templates WHERE id = ?", [$page['template_id']])->fetch();
        if ($tpl) {
            $html = $tpl['html_content'];
            
            // Podmieniamy znacznik {{global_footer}} na gotowy kod wygenerowanej stopki
            $html = str_replace('{{global_footer}}', $globalFooterHtml, $html);
            
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
        echo '<div class="max-w-6xl w-full mx-auto p-6 md:p-12">';
        $blocksToRender = $pageContents['editor'] ?? (isset($pageContents[0]) ? $pageContents : []);
        \CMS\Helpers\BlockRenderer::render($blocksToRender, $db);
        echo '</div>';
        
        // Zawsze dorzucamy domyślną stopkę na sam dół (bo nie ma szablonu, który by ją kontrolował)
        echo $globalFooterHtml;
    }
    ?>
</main>

<?php 
// Załadowanie samych skryptów JS
require __DIR__ . '/partials/footer.php'; 
?>