<?php
// Pobranie układu menu uwzględniającego zagnieżdżenia (drzewo kaskadowe)
$rawMenu = \CMS\Core\Database::getInstance()->query("SELECT * FROM pa_menu ORDER BY sort_order ASC")->fetchAll();
$menuTree = [];
$menuById = [];

foreach ($rawMenu as $item) {
    $item['children'] = [];
    $menuById[$item['id']] = $item;
}

foreach ($menuById as $id => &$item) {
    if (!empty($item['parent_id']) && isset($menuById[$item['parent_id']])) {
        $menuById[$item['parent_id']]['children'][] = &$item;
    } else {
        $menuTree[] = &$item;
    }
}

// Funkcja renderująca zagnieżdżone elementy w Desktop UI
if (!function_exists('renderNavMenuDesktop')) {
    function renderNavMenuDesktop($items) {
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                echo '<div class="relative group">';
                echo '  <a href="'.htmlspecialchars($item['url']).'" class="flex items-center gap-1 hover:text-gray-300 font-medium py-2 transition">';
                echo        htmlspecialchars($item['label']) . ' <span class="text-[10px]">▼</span>';
                echo '  </a>';
                echo '  <div class="absolute left-0 top-full mt-0 hidden group-hover:flex flex-col bg-gray-800 text-white min-w-[220px] shadow-xl rounded-b-lg border border-gray-700 z-[202] overflow-hidden">';
                foreach ($item['children'] as $child) {
                    echo '<a href="'.htmlspecialchars($child['url']).'" class="block px-5 py-3 hover:bg-gray-700 transition">'.htmlspecialchars($child['label']).'</a>';
                }
                echo '  </div>';
                echo '</div>';
            } else {
                echo '<a href="'.htmlspecialchars($item['url']).'" class="hover:text-gray-300 font-medium py-2 transition">'.htmlspecialchars($item['label']).'</a>';
            }
        }
    }
}

// Funkcja renderująca elementy mobilnego menu (z wcięciami z lewej strony)
if (!function_exists('renderNavMenuMobile')) {
    function renderNavMenuMobile($items) {
        foreach ($items as $item) {
            if (!empty($item['children'])) {
                echo '<div class="block">';
                echo '  <a href="'.htmlspecialchars($item['url']).'" class="block hover:text-gray-300 py-3 border-b border-gray-800 font-bold text-gray-200">';
                echo        htmlspecialchars($item['label']) . ' <span class="text-[10px] ml-1 text-gray-500">▼</span>';
                echo '  </a>';
                echo '  <div class="pl-4 border-l border-gray-700 bg-gray-800/50">';
                foreach ($item['children'] as $child) {
                    echo '<a href="'.htmlspecialchars($child['url']).'" class="block hover:text-white py-3 border-b border-gray-800/50 text-gray-300">'.htmlspecialchars($child['label']).'</a>';
                }
                echo '  </div>';
                echo '</div>';
            } else {
                echo '<a href="'.htmlspecialchars($item['url']).'" class="block hover:text-gray-300 py-3 border-b border-gray-800 font-bold text-gray-200">'.htmlspecialchars($item['label']).'</a>';
            }
        }
    }
}
?>

<nav class="bg-gray-900 text-white p-4 relative z-50 shadow">
    <div class="max-w-6xl mx-auto flex justify-between items-center relative z-50">
        <a href="/" class="font-bold text-xl flex items-center gap-2">
            <?php if (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo" class="h-8 w-auto">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_title'] ?? $page['title'] ?? 'CMS') ?>
            <?php endif; ?>
        </a>
        
        <button id="burger-btn" class="md:hidden text-3xl px-2 focus:outline-none hover:text-gray-300">
            ☰
        </button>
        
        <div class="hidden md:flex gap-8 items-center">
            <?php renderNavMenuDesktop($menuTree); ?>
        </div>
    </div>
    
    <div id="mobile-menu" class="hidden md:hidden flex-col gap-0 mt-4 border-t border-gray-700 pt-2 px-2">
        <?php renderNavMenuMobile($menuTree); ?>
    </div>
</nav>