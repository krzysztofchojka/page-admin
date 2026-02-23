<?php 
// Załadowanie nagłówka
require __DIR__ . '/partials/header.php'; 

// Załadowanie menu
require __DIR__ . '/partials/navbar.php'; 
?>

<main class="max-w-6xl w-full mx-auto p-6 md:p-12 flex-1">
    <?php 
    // Główna logika renderowania bloków (przeniesiona do klasy Helpera)
    $db = \CMS\Core\Database::getInstance();
    $blocks = json_decode($page['contents'], true) ?? [];
    
    \CMS\Helpers\BlockRenderer::render($blocks, $db);
    ?>
</main>

<?php 
// Załadowanie stopki i skryptów
require __DIR__ . '/partials/footer.php'; 
?>