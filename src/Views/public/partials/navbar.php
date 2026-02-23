<nav class="bg-gray-900 text-white p-4">
    <div class="max-w-6xl mx-auto flex justify-between items-center relative">
        <a href="/" class="font-bold text-xl flex items-center gap-2">
            <?php if (!empty($settings['site_logo'])): ?>
                <img src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo" class="h-8 w-auto">
            <?php else: ?>
                <?= htmlspecialchars($settings['site_title'] ?? $page['title']) ?>
            <?php endif; ?>
        </a>
        
        <button id="burger-btn" class="md:hidden text-2xl px-2 focus:outline-none hover:text-gray-300"> ☰ </button>
        
        <div class="hidden md:flex gap-6 items-center">
            <?php 
            // Pobieranie menu bezpośrednio w widoku (akceptowalne w prostym MVC)
            $menuItems = \CMS\Core\Database::getInstance()->query("SELECT * FROM pa_menu ORDER BY sort_order ASC")->fetchAll(); 
            foreach ($menuItems as $item): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="hover:text-gray-300 font-medium"><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div id="mobile-menu" class="hidden md:hidden flex-col gap-4 mt-4 border-t border-gray-700 pt-4 px-2">
        <?php foreach ($menuItems as $item): ?>
            <a href="<?= htmlspecialchars($item['url']) ?>" class="block hover:text-gray-300 py-2 border-b border-gray-800"><?= htmlspecialchars($item['label']) ?></a>
        <?php endforeach; ?>
    </div>
</nav>