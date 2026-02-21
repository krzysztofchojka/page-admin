<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? $page['title']) ?></title>
    
    <?php if (!empty($settings['meta_description'])): ?>
        <meta name="description" content="<?= htmlspecialchars($settings['meta_description']) ?>">
    <?php endif; ?>

    <?php if (!empty($settings['site_favicon'])): ?>
        <link rel="icon" href="<?= htmlspecialchars($settings['site_favicon']) ?>">
    <?php endif; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --color-primary: <?= htmlspecialchars($settings['color_primary'] ?? '#f97316') ?>;
            --color-secondary: <?= htmlspecialchars($settings['color_secondary'] ?? '#1e3a8a') ?>;
        }
        nav{
            position: sticky !important;
            top: 0 !important;
            z-index: 201 !important;
        }
        main{
            padding-top: 0 !important;
        }
        
        /* Przykład jak to wykorzystać w reszcie styli: */
        .text-primary { color: var(--color-primary); }
        .bg-primary { background-color: var(--color-primary); }
        .bg-secondary { background-color: var(--color-secondary); }
    </style>
    <style>
        .prose h1 { font-size: 2.25em; font-weight: 800; margin-bottom: 0.5em; }
        .prose h2 { font-size: 1.5em; font-weight: 700; margin-bottom: 0.5em; }
        .prose p { margin-bottom: 1em; line-height: 1.6; }
        .prose ul { list-style: disc; margin-left: 1.5em; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>

    <?= $settings['custom_head'] ?? '' ?>
</head>
<body class="bg-white text-gray-800 flex flex-col min-h-screen overflow-x-hidden">

<nav class="bg-gray-900 text-white p-4">
        <div class="max-w-6xl mx-auto flex justify-between items-center relative">
            <a href="/" class="font-bold text-xl flex items-center gap-2">
                <?php if (!empty($settings['site_logo'])): ?>
                    <img src="<?= htmlspecialchars($settings['site_logo']) ?>" alt="Logo" class="h-8 w-auto">
                <?php else: ?>
                    <?= htmlspecialchars($settings['site_title'] ?? $page['title']) ?>
                <?php endif; ?>
            </a>
            
            <button id="burger-btn" class="md:hidden text-2xl px-2 focus:outline-none hover:text-gray-300">
                ☰
            </button>

            <div class="hidden md:flex gap-6 items-center">
                <?php 
                $menu = \CMS\Core\Database::getInstance()->query("SELECT * FROM pa_menu ORDER BY sort_order ASC")->fetchAll();
                foreach ($menu as $item): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" class="hover:text-gray-300"><?= htmlspecialchars($item['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden flex-col gap-4 mt-4 border-t border-gray-700 pt-4 px-2">
            <?php foreach ($menu as $item): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="block hover:text-gray-300 py-2 border-b border-gray-800"><?= htmlspecialchars($item['label']) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>

    <main class="max-w-6xl w-full mx-auto p-6 md:p-12 flex-1">
        <?php
        // Helper function to render a list of blocks recursively
        function renderBlocks($blocks, $db) {
            if (empty($blocks)) return;

            foreach ($blocks as $block) {

                // Wyciąganie globalnych ustawień bloku
                $set = $block['settings'] ?? [];
                $idAttr = !empty($set['id']) ? ' id="'.htmlspecialchars($set['id']).'"' : '';
                $clsAttr = !empty($set['css']) ? ' ' . htmlspecialchars($set['css']) : '';
                $styleAttr = !empty($set['style']) ? ' style="'.htmlspecialchars($set['style']).'"' : '';

                // Otwieramy uniwersalny wrapper dla bloku
                echo "<div{$idAttr} class=\"block-wrapper mb-4{$clsAttr}\"{$styleAttr}>";
                
                // 1. COLUMNS 2
                if ($block['type'] === 'columns_2') {
                    echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">';
                    echo '<div>';
                    if(!empty($block['children']['left'])) renderBlocks($block['children']['left'], $db);
                    echo '</div><div>';
                    if(!empty($block['children']['right'])) renderBlocks($block['children']['right'], $db);
                    echo '</div></div>';
                }

                // 2. COLUMNS 3
                elseif ($block['type'] === 'columns_3') {
                    echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">';
                    echo '<div>';
                    if(!empty($block['children']['left'])) renderBlocks($block['children']['left'], $db);
                    echo '</div><div>';
                    if(!empty($block['children']['center'])) renderBlocks($block['children']['center'], $db);
                    echo '</div><div>';
                    if(!empty($block['children']['right'])) renderBlocks($block['children']['right'], $db);
                    echo '</div></div>';
                }

                // 3. TEXT
                elseif ($block['type'] === 'text') {
                    echo '<div class="prose max-w-none mb-6">' . $block['content'] . '</div>';
                }

                // 4. IMAGE
                elseif ($block['type'] === 'image') {
                    if (!empty($block['content'])) {
                        echo '<div class="mb-6"><img src="' . htmlspecialchars($block['content']) . '" class="w-full rounded shadow-lg"></div>';
                    }
                }

                // 5. FORM
                elseif ($block['type'] === 'form') {
                    $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $block['content']])->fetch();
                    if ($form) {
                        $settings = json_decode($form['settings'] ?? '{}', true);
                        $userId = \CMS\Core\Session::get('user_id');
                
                        echo '<div class="bg-gray-50 border p-6 rounded mb-8 shadow-sm" id="form-container-'.$form['id'].'">';
                        echo '<h3 class="text-xl font-bold mb-4">' . htmlspecialchars($form['title']) . '</h3>';
                
                        // Ochrona dostępem tylko dla zalogowanych
                        if (!empty($settings['reqLogin']) && !$userId) {
                            echo '<div class="bg-yellow-100 p-4 rounded text-yellow-800 border border-yellow-300">Zaloguj się, aby wyświetlić i wypełnić ten formularz. <br><a href="/login" class="font-bold underline text-yellow-900 mt-2 inline-block">Przejdź do logowania</a></div></div>';
                            echo '</div>'; // close block-wrapper
                            continue;
                        }
                
                        $isSubmittedNow = isset($_GET['submitted']) && $_GET['submitted'] == $form['id'];
        $isEditing = isset($_GET['edit']) && $_GET['edit'] == $form['id'];

        $existingSubmission = null;
        if ($userId) {
            $existingSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
        }

        // --- ZMIANA: Inteligentne sprawdzanie czy pokazać podziękowanie ---
        $showThankYou = false;
        if ($userId) {
            // Jeśli zalogowany, musi FAKTYCZNIE mieć wpis w bazie
            if ($existingSubmission && ($isSubmittedNow || (!empty($settings['fillOnce']) && !$isEditing))) {
                $showThankYou = true;
            }
        } else {
            // Gość polega tylko na zmiennej z URL
            if ($isSubmittedNow) {
                $showThankYou = true;
            }
        }

        if ($showThankYou) {
            
            echo '<div class="bg-green-100 border border-green-400 text-green-800 px-4 py-4 rounded mb-4 shadow-sm">';
            echo '<span class="text-2xl mb-2 block">🎉</span> <strong>Dziękujemy!</strong> Twój formularz został poprawnie zapisany na serwerze.';
            echo '</div>';

            echo '<div class="flex flex-wrap gap-4 mt-6">';
            if (!empty($settings['editable']) && $existingSubmission) {
                echo '<a href="?edit='.$form['id'].'#form-container-'.$form['id'].'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">Kliknij, by edytować</a>';
            }
            if (empty($settings['fillOnce'])) {
                echo '<a href="?#form-container-'.$form['id'].'" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded shadow transition">Wyślij ponownie</a>';
            }
            echo '</div>';

        } else {
            // Renderowanie formularza
            $prefill = [];
                            if ($isEditing && $existingSubmission) {
                                $vault = new \CMS\Core\Vault();
                                $prefill = json_decode($vault->decrypt($existingSubmission['data_json']), true) ?? [];
                            }
                
                            echo '<form action="/submit-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-5">';
                            echo '<input type="hidden" name="form_id" value="'.$form['id'].'">';
                            if ($isEditing && $existingSubmission) {
                                echo '<input type="hidden" name="submission_id" value="'.$existingSubmission['id'].'">';
                            }
                
                            $fields = json_decode($form['form_json'], true);
                            foreach ($fields as $field) {
                                $widthClass = ($field['width']??'full') === 'full' ? 'md:col-span-3' : (($field['width']??'full')==='half'?'md:col-span-2':'md:col-span-1');
                                
                                // Użycie wygenerowanego ID lub fallback z poprzedniej wersji
                                $fieldId = $field['id'] ?? md5($field['label']); 
                                $val = htmlspecialchars($prefill[$fieldId] ?? '');
                
                                echo "<div class='$widthClass'><label class='block text-xs font-bold text-gray-600 uppercase mb-2'>{$field['label']}</label>";
                                
                                if($field['type'] == 'file') {
                                    echo "<input type='file' name='files[{$fieldId}]' class='w-full border bg-white p-2 rounded focus:ring-2 focus:ring-blue-500'>";
                                    if ($isEditing && $existingSubmission) {
                                        echo "<p class='text-xs text-blue-500 mt-1'>Wgranie pliku zastąpi poprzedni wgrany w tym polu.</p>";
                                    }
                                } else {
                                    echo "<input type='{$field['type']}' name='data[{$fieldId}]' value='{$val}' class='w-full border p-2 rounded focus:ring-2 focus:ring-blue-500' required>";
                                }
                                echo "</div>";
                            }
                            
                            $btnText = $isEditing ? 'Zaktualizuj dane' : 'Wyślij';
                            echo '<button class="md:col-span-3 bg-blue-600 text-white font-bold py-3 rounded mt-2 hover:bg-blue-700 transition shadow">'.$btnText.'</button>';
                            if ($isEditing) {
                                echo '<a href="?" class="md:col-span-3 text-center text-sm text-gray-500 hover:text-gray-800">Anuluj edycję</a>';
                            }
                            echo '</form>';
                        }
                        echo '</div>';
                    }
                }

                // 6. LINKED IMAGE
                elseif ($block['type'] === 'linked_image') {
                    $img = $block['content']['url'] ?? '';
                    $link = $block['content']['link'] ?? '#';
                    if ($img) {
                        echo '<div class="mb-8"><a href="'.htmlspecialchars($link).'"><img src="'.htmlspecialchars($img).'" class="w-full rounded shadow-md hover:opacity-90 transition"></a></div>';
                    }
                }

                // 7. CAROUSEL
                elseif ($block['type'] === 'carousel') {
                    $data = is_array($block['content']) ? $block['content'] : [];
                    $tabs = $data['tabs'] ?? [];
                    $arrows = $data['arrows'] ?? false;
                    // Tworzymy unikalne, ale STAŁE ID na podstawie ID z ustawień bloku lub skrótu jego zawartości
                    $cid = 'c_' . (!empty($set['id']) ? htmlspecialchars($set['id']) : substr(md5(json_encode($tabs)), 0, 8));
                    
                    if (!empty($tabs)) {
                        echo '<div class="mb-10 carousel-wrapper" id="'.$cid.'">';
                        
                        // Nawigacja (Kafelki)
                        echo '<div class="flex flex-wrap gap-3 justify-center mb-8 items-center">';
                        
                        if ($arrows) {
                            echo '<button onclick="moveCarousel(\''.$cid.'\', -1)" class="bg-blue-900 text-white w-10 h-10 rounded-lg font-bold hover:bg-blue-800 transition shadow">&lt;</button>';
                        }
                        
                        foreach ($tabs as $idx => $tab) {
                            $activeClass = $idx === 0 ? 'bg-blue-900 scale-105' : 'bg-blue-700 hover:bg-blue-800';
                            
                            $tSet = $tab['settings'] ?? [];
                            $tId = !empty($tSet['id']) ? ' id="'.htmlspecialchars($tSet['id']).'"' : '';
                            $tCls = !empty($tSet['css']) ? ' ' . htmlspecialchars($tSet['css']) : '';
                            $tStyle = !empty($tSet['style']) ? ' style="'.htmlspecialchars($tSet['style']).'"' : '';
                            
                            echo '<button'.$tId.' onclick="showCarouselTab(\''.$cid.'\', '.$idx.')" data-index="'.$idx.'" class="c-btn-'.$cid.' flex flex-col items-center justify-center p-4 rounded-xl w-32 md:w-40 text-white shadow-lg transition-all duration-300 transform '.$activeClass.$tCls.'"'.$tStyle.'>';
                            
                            if (strpos($tab['icon'], 'http') === 0 || strpos($tab['icon'], '/') === 0) {
                                echo '<img src="'.htmlspecialchars($tab['icon']).'" class="h-8 w-8 mb-2 invert">';
                            } else {
                                echo '<span class="text-3xl mb-2 block">'.htmlspecialchars($tab['icon']).'</span>';
                            }
                            
                            echo '<span class="text-xs md:text-sm font-bold text-center leading-tight uppercase">'.htmlspecialchars($tab['label']).'</span>';
                            echo '</button>';
                        }

                        if ($arrows) {
                            echo '<button onclick="moveCarousel(\''.$cid.'\', 1)" class="bg-blue-900 text-white w-10 h-10 rounded-lg font-bold hover:bg-blue-800 transition shadow">&gt;</button>';
                        }
                        echo '</div>'; 
                        
                        // Kontener Treści (wspiera zagnieżdżanie!)
                        echo '<div class="bg-white p-6 md:p-10 rounded-xl shadow border-t-4 border-blue-900 relative">';
                        foreach ($tabs as $idx => $tab) {
                            $display = $idx === 0 ? 'block' : 'hidden';
                            echo '<div class="c-content-'.$cid.' animate-fade-in '.$display.'" data-index="'.$idx.'">';
                            
                            if (!empty($tab['children'])) {
                                renderBlocks($tab['children'], $db);
                            } else if (!empty($tab['content'])) {
                                echo '<div class="prose max-w-none">' . $tab['content'] . '</div>';
                            }
                            
                            echo '</div>';
                        }
                        echo '</div>';
                        
                        echo '</div>';
                    }
                }

                // 8. GALLERY
                elseif ($block['type'] === 'gallery') {
                    $gal = $db->query("SELECT * FROM pa_galleries WHERE id = :id", ['id' => $block['content']])->fetch();
                    if ($gal) {
                        $imgs = json_decode($gal['images_json'], true);
                        echo '<div class="mb-8"><h3 class="text-lg font-bold mb-2">'.htmlspecialchars($gal['title']).'</h3>';
                        if ($gal['type'] === 'grid') {
                            echo '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
                            foreach($imgs as $img) echo "<img src='$img' class='w-full h-32 object-cover rounded shadow hover:scale-105 transition'>";
                            echo '</div>';
                        } else {
                            echo '<div class="flex overflow-x-auto gap-4 pb-2 snap-x">';
                            foreach($imgs as $img) echo "<img src='$img' class='snap-center w-64 h-48 object-cover rounded shadow flex-shrink-0'>";
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                }

                // 9. IMAGE CARDS
                elseif ($block['type'] === 'image_cards') {
                    $data = is_array($block['content']) ? $block['content'] : [];
                    $cards = $data['cards'] ?? [];
                    
                    if (!empty($cards)) {
                        echo '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">';
                        foreach ($cards as $card) {
                            $link = !empty($card['link']) ? htmlspecialchars($card['link']) : '#';
                            
                            $cSet = $card['settings'] ?? [];
                            $cId = !empty($cSet['id']) ? ' id="'.htmlspecialchars($cSet['id']).'"' : '';
                            $cCls = !empty($cSet['css']) ? ' ' . htmlspecialchars($cSet['css']) : '';
                            $cStyle = !empty($cSet['style']) ? ' style="'.htmlspecialchars($cSet['style']).'"' : '';
                            
                            echo '<a href="'.$link.'"'.$cId.' class="block bg-white rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden border border-gray-100 group flex flex-col h-full'.$cCls.'"'.$cStyle.'>';
                            
                            echo '<div class="h-48 w-full overflow-hidden bg-gray-100">';
                            if (!empty($card['img'])) {
                                echo '<img src="'.htmlspecialchars($card['img']).'" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">';
                            }
                            echo '</div>';
                            
                            echo '<div class="p-5 flex-1 flex flex-col justify-center">';
                            echo '<h3 class="text-orange-500 font-bold text-lg mb-1 leading-tight">'.htmlspecialchars($card['title']).'</h3>';
                            if (!empty($card['subtitle'])) {
                                echo '<p class="text-gray-800 text-sm">'.htmlspecialchars($card['subtitle']).'</p>';
                            }
                            echo '</div>';
                            
                            echo '</a>';
                        }
                        echo '</div>';
                    }
                }

                // 10. BANNER
                elseif ($block['type'] === 'banner') {
                    $data = is_array($block['content']) ? $block['content'] : [];
                    $bg = $data['bg'] ?? '';
                    $title = $data['title'] ?? '';
                    $subtitle = $data['subtitle'] ?? '';
                    
                    echo '<div class="relative w-screen h-64 md:h-[500px] bg-cover bg-center mb-10" style="margin-left: calc(-50vw + 50%); background-image: url(\''.htmlspecialchars($bg).'\');">';
                    
                    if (!empty($title)) {
                        echo '  <div class="absolute bottom-8 left-0 w-11/12 md:w-2/3 bg-white/85 p-6 md:pl-16 backdrop-blur-sm shadow-lg rounded-r-xl">';
                        echo '    <h1 class="text-3xl md:text-5xl font-extrabold text-orange-500 tracking-wide">'.htmlspecialchars($title).'</h1>';
                        echo '  </div>';
                    }
                    echo '</div>';
                    
                    if (!empty($subtitle)) {
                        echo '<div class="text-gray-800 font-medium mb-10 text-lg max-w-4xl border-l-4 border-orange-500 pl-4">'.nl2br(htmlspecialchars($subtitle)).'</div>';
                    }
                }

                // 11. RAW HTML
                elseif ($block['type'] === 'raw_html') {
                    echo $block['content'];
                }

                // Zamknięcie uniwersalnego wrappera
                echo "</div>";
            }
        }

        // --- Start Rendering ---
        $db = \CMS\Core\Database::getInstance();
        $blocks = json_decode($page['contents'], true) ?? [];
        renderBlocks($blocks, $db);
        ?>
    </main>

    <?php if (($settings['hide_footer'] ?? 0) != 1): ?>
        <?php if (!empty($footerBlocks)): ?>
            <footer class="mt-auto border-gray-200">
                <div class="w-full mx-auto pb-4 pt-10">
                    <?php renderBlocks($footerBlocks, $db); ?>
                </div>
            </footer>
        <?php else: ?>
            <footer class="bg-gray-100 text-center p-6 text-gray-500 text-sm mt-auto">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['site_title'] ?? 'Footer Placeholder') ?>.
            </footer>
        <?php endif; ?>
    <?php endif; ?>

    <script>
    function showCarouselTab(cid, index) {
    document.querySelectorAll('.c-btn-' + cid).forEach(btn => {
        if (parseInt(btn.dataset.index) === index) {
            btn.classList.remove('bg-blue-700', 'hover:bg-blue-800');
            btn.classList.add('bg-blue-900', 'scale-105');
        } else {
            btn.classList.add('bg-blue-700', 'hover:bg-blue-800');
            btn.classList.remove('bg-blue-900', 'scale-105');
        }
    });
    
    document.querySelectorAll('.c-content-' + cid).forEach(content => {
        if (parseInt(content.dataset.index) === index) {
            content.classList.remove('hidden');
            content.classList.add('block');
        } else {
            content.classList.remove('block');
            content.classList.add('hidden');
        }
    });

    // ZAPIS DO LOCAL STORAGE
    localStorage.setItem('active_tab_' + cid, index);
}

function moveCarousel(cid, direction) {
    const contents = document.querySelectorAll('.c-content-' + cid);
    let currentIndex = 0;
    contents.forEach(el => {
        if (!el.classList.contains('hidden')) {
            currentIndex = parseInt(el.dataset.index);
        }
    });
    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = contents.length - 1;
    if (newIndex >= contents.length) newIndex = 0;
    showCarouselTab(cid, newIndex);
}

// ODCZYT Z LOCAL STORAGE PO ZAŁADOWANIU STRONY
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.carousel-wrapper').forEach(wrapper => {
        const cid = wrapper.id;
        const savedIndex = localStorage.getItem('active_tab_' + cid);
        
        if (savedIndex !== null) {
            // Odpalamy funkcję dla zapisanego indeksu
            showCarouselTab(cid, parseInt(savedIndex));
        }
    });
});

document.getElementById('burger-btn').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
            menu.classList.toggle('flex');
        });
    </script>
</body>
</html>