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
        /* Dynamiczne klasy w oparciu o zmienne CSS */
        .text-primary { color: var(--color-primary); }
        .bg-primary { background-color: var(--color-primary); }
        .bg-secondary { background-color: var(--color-secondary); }
        .border-primary { border-color: var(--color-primary); }
        .hover\:bg-primary:hover { background-color: var(--color-primary); }
        .hover\:text-white:hover { color: #ffffff; }

        /* Typografia dla edytora tekstu */
        .prose h1 { font-size: 2.25em; font-weight: 800; margin-bottom: 0.5em; }
        .prose h2 { font-size: 1.5em; font-weight: 700; margin-bottom: 0.5em; }
        .prose p { margin-bottom: 1em; line-height: 1.6; }
        .prose ul { list-style: disc; margin-left: 1.5em; }
        /* Przeloty CSS (Zoptymalizowane) */
        .przelot-widget { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 24px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .przelot-widget .header { display: flex; align-items: center; gap: 12px; font-weight: bold; margin-bottom: 16px; border-bottom: 1px solid #f3f4f6; padding-bottom: 12px; }
        .przelot-widget .header img.logo { height: 24px; object-fit: contain; }
        .przelot-widget .body { display: flex; align-items: stretch; gap: 16px; margin-bottom: 16px; }
        .przelot-widget .duration { 
            font-size: 0.875rem; 
            color: #4b5563; 
            min-width: 60px; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; /* Wyśrodkowanie w pionie względem linii */
            align-items: center;     /* Wyśrodkowanie w poziomie (tekst na środku swojej kolumny) */
            text-align: center; 
        }
        .przelot-widget .en-translation { display: block; font-size: 0.75rem; color: #9ca3af; font-weight: normal; margin-top: 2px; }
        .przelot-widget .graphic { display: flex; flex-direction: column; align-items: center; padding-top: 6px; }
        .przelot-widget .circle { width: 10px; height: 10px; border-radius: 50%; border: 2px solid #3b82f6; background: white; }
        .przelot-widget .line { width: 2px; background: #bfdbfe; flex-grow: 1; margin: 4px 0; }
        .przelot-widget .stops { font-weight: bold; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .przelot-widget .infobubble { background: #eff6ff; color: #1e40af; padding: 8px 12px; border-radius: 6px; text-align: center; font-size: 0.875rem; font-weight: bold; margin: 16px 0; border: 1px dashed #bfdbfe; }
        .przelot-widget .footer { border-top: 1px solid #f3f4f6; padding-top: 12px; font-size: 0.875rem; color: #374151; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
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
                <a href="<?= htmlspecialchars($item['url']) ?>" class="hover:text-gray-300 font-medium"><?= htmlspecialchars($item['label']) ?></a>
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
    // GŁÓWNY SILNIK RENDERUJĄCY BLOKI (REKURENCYJNY)
    function renderBlocks($blocks, $db) {
        if (empty($blocks)) return;

        foreach ($blocks as $block) {
            
            // Wyciąganie globalnych ustawień bloku (ID HTML, Klasy CSS, Style Inline)
            $set = $block['settings'] ?? [];
            $idAttr = !empty($set['id']) ? ' id="'.htmlspecialchars($set['id']).'"' : '';
            $clsAttr = !empty($set['css']) ? ' ' . htmlspecialchars($set['css']) : '';
            $styleAttr = !empty($set['style']) ? ' style="'.htmlspecialchars($set['style']).'"' : '';

            // Otwieramy uniwersalny wrapper dla bloku
            echo "<div{$idAttr} class=\"block-wrapper mb-4{$clsAttr}\"{$styleAttr}>";

            // 1. COLUMNS 2
            if ($block['type'] === 'columns_2') {
                echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">';
                echo '<div>'; if(!empty($block['children']['left'])) renderBlocks($block['children']['left'], $db); echo '</div>';
                echo '<div>'; if(!empty($block['children']['right'])) renderBlocks($block['children']['right'], $db); echo '</div>';
                echo '</div>';
            } 
            // 2. COLUMNS 3
            elseif ($block['type'] === 'columns_3') {
                echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">';
                echo '<div>'; if(!empty($block['children']['left'])) renderBlocks($block['children']['left'], $db); echo '</div>';
                echo '<div>'; if(!empty($block['children']['center'])) renderBlocks($block['children']['center'], $db); echo '</div>';
                echo '<div>'; if(!empty($block['children']['right'])) renderBlocks($block['children']['right'], $db); echo '</div>';
                echo '</div>';
            } 
            // 3. TEXT
            elseif ($block['type'] === 'text') {
                echo '<div class="prose max-w-none mb-6">' . $block['content'] . '</div>';
            } 
            // 4. IMAGE
            elseif ($block['type'] === 'image') {
                if (!empty($block['content'])) {
                    echo '<div class="mb-6"><img src="' . htmlspecialchars($block['content']) . '" class="w-full rounded-xl shadow-lg"></div>';
                }
            } 
            // 5. WIDEO (NOWE)
            elseif ($block['type'] === 'video') {
                $url = $block['content'] ?? '';
                if ($url) {
                    echo '<div class="w-full aspect-video mb-8 overflow-hidden rounded-xl shadow-lg bg-black">';
                    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
                        $embedUrl = preg_replace('/watch\?v=([a-zA-Z0-9_-]+)/', 'embed/$1', $url);
                        $embedUrl = str_replace('youtu.be/', 'youtube.com/embed/', $embedUrl);
                        echo '<iframe src="'.htmlspecialchars($embedUrl).'" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                    } elseif (strpos($url, 'vimeo.com') !== false) {
                        $vimeoId = substr(parse_url($url, PHP_URL_PATH), 1);
                        echo '<iframe src="https://player.vimeo.com/video/'.htmlspecialchars($vimeoId).'" class="w-full h-full border-0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                    } else {
                        echo '<video controls class="w-full h-full outline-none"><source src="'.htmlspecialchars($url).'" type="video/mp4">Twoja przeglądarka nie obsługuje tagu video.</video>';
                    }
                    echo '</div>';
                }
            }
            // 6. PRZYCISK (NOWE)
            elseif ($block['type'] === 'button') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $label = $data['label'] ?? 'Kliknij tutaj';
                $url = $data['url'] ?? '#';
                $style = $data['style'] ?? 'primary';
                
                $btnClass = 'inline-block px-8 py-3 rounded-lg font-bold transition shadow hover:shadow-lg text-center ';
                if ($style === 'primary') {
                    $btnClass .= 'bg-primary text-white hover:opacity-90';
                } else {
                    $btnClass .= 'border-2 border-primary text-primary hover:bg-primary hover:text-white';
                }
                
                echo '<div class="mb-8">';
                echo '<a href="'.htmlspecialchars($url).'" class="'.$btnClass.'">'.htmlspecialchars($label).'</a>';
                echo '</div>';
            }
            // 7. SEPARATOR (NOWE)
            elseif ($block['type'] === 'divider') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $height = $data['height'] ?? '8'; // e.g., '4', '8', '16', '24'
                echo '<hr class="border-t border-gray-200 my-'.htmlspecialchars($height).' w-full">';
            }
            // 8. CYTAT (NOWE)
            elseif ($block['type'] === 'quote') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $text = $data['text'] ?? '';
                $author = $data['author'] ?? '';

                echo '<blockquote class="border-l-4 border-yellow-500 bg-yellow-50 p-6 rounded-r-xl mb-8 shadow-sm">';
                echo '<p class="text-xl md:text-2xl italic font-serif text-gray-800 mb-4 leading-relaxed">"'.nl2br(htmlspecialchars($text)).'"</p>';
                if (!empty($author)) {
                    echo '<footer class="text-gray-600 font-bold tracking-wide text-sm uppercase">— '.htmlspecialchars($author).'</footer>';
                }
                echo '</blockquote>';
            }
            // 9. AKORDEON (NOWE)
            elseif ($block['type'] === 'accordion') {
                $title = $block['content']['title'] ?? 'Kliknij, aby rozwinąć';
                echo '<details class="group mb-4 bg-white border border-gray-200 rounded-xl shadow-sm cursor-pointer overflow-hidden transition-all">';
                echo '<summary class="p-5 font-bold text-lg text-purple-800 bg-purple-50 list-none flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-purple-300">';
                echo '<span>'.htmlspecialchars($title).'</span>';
                echo '<span class="transition-transform duration-300 group-open:rotate-180 text-xl font-normal">▼</span>';
                echo '</summary>';
                echo '<div class="p-6 border-t border-purple-100 bg-white">';
                if (!empty($block['children'])) {
                    renderBlocks($block['children'], $db);
                }
                echo '</div>';
                echo '</details>';
            }
            // 10. FORMULARZ
            elseif ($block['type'] === 'form') {
                $form = $db->query("SELECT * FROM pa_forms WHERE id = :id", ['id' => $block['content']])->fetch();
                if ($form) {
                    $formSettings = json_decode($form['settings'] ?? '{}', true);
                    $userId = \CMS\Core\Session::get('user_id');

                    echo '<div class="bg-gray-50 border border-gray-200 p-6 md:p-8 rounded-xl mb-8 shadow-sm" id="form-container-'.$form['id'].'">';
                    echo '<h3 class="text-2xl font-bold mb-6 text-gray-800">' . htmlspecialchars($form['title']) . '</h3>';

                    if (!empty($formSettings['reqLogin']) && !$userId) {
                        echo '<div class="bg-yellow-100 p-5 rounded-lg text-yellow-800 border border-yellow-300">Zaloguj się, aby wyświetlić i wypełnić ten formularz. <br><a href="/login" class="font-bold underline text-yellow-900 mt-2 inline-block">Przejdź do logowania</a></div></div>';
                        echo '</div>';
                        continue;
                    }

                    $isSubmittedNow = isset($_GET['submitted']) && $_GET['submitted'] == $form['id'];
                    $isEditing = isset($_GET['edit']) && $_GET['edit'] == $form['id'];
                    $existingSubmission = null;

                    if ($userId) {
                        $existingSubmission = $db->query("SELECT * FROM pa_submissions WHERE form_id = ? AND user_id = ? ORDER BY id DESC LIMIT 1", [$form['id'], $userId])->fetch();
                    }

                    $showThankYou = false;
                    if ($userId) {
                        if ($existingSubmission && ($isSubmittedNow || (!empty($formSettings['fillOnce']) && !$isEditing))) {
                            $showThankYou = true;
                        }
                    } else {
                        if ($isSubmittedNow) {
                            $showThankYou = true;
                        }
                    }

                    if ($showThankYou) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-800 px-5 py-5 rounded-lg mb-4 shadow-sm">';
                        echo '<span class="text-3xl mb-3 block">🎉</span> <strong class="text-lg">Dziękujemy!</strong><br> Twój formularz został poprawnie zapisany na serwerze.';
                        echo '</div>';
                        echo '<div class="flex flex-wrap gap-4 mt-6">';
                        if (!empty($formSettings['editable']) && $existingSubmission) {
                            echo '<a href="?edit='.$form['id'].'#form-container-'.$form['id'].'" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow transition">Kliknij, by edytować</a>';
                        }
                        if (empty($formSettings['fillOnce'])) {
                            echo '<a href="?#form-container-'.$form['id'].'" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded shadow transition">Wyślij ponownie</a>';
                        }
                        echo '</div>';
                    } else {
                        $prefill = [];
                        if ($isEditing && $existingSubmission) {
                            $vault = new \CMS\Core\Vault();
                            $prefill = json_decode($vault->decrypt($existingSubmission['data_json']), true) ?? [];
                        }

                        echo '<form action="/submit-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-6">';
                        echo '<input type="hidden" name="form_id" value="'.$form['id'].'">';
                        if ($isEditing && $existingSubmission) {
                            echo '<input type="hidden" name="submission_id" value="'.$existingSubmission['id'].'">';
                        }

                        $fields = json_decode($form['form_json'], true);
                        foreach ($fields as $field) {
                            $widthClass = ($field['width']??'full') === 'full' ? 'md:col-span-3' : (($field['width']??'full')==='half'?'md:col-span-2':'md:col-span-1');
                            $fieldId = $field['id'] ?? md5($field['label']);
                            $val = htmlspecialchars($prefill[$fieldId] ?? '');

                            echo "<div class='$widthClass'><label class='block text-sm font-bold text-gray-700 mb-2'>{$field['label']}</label>";
                            if($field['type'] == 'file') {
                                echo "<input type='file' name='files[{$fieldId}]' class='w-full border bg-white p-2.5 rounded shadow-sm focus:ring-2 focus:ring-blue-500'>";
                                if ($isEditing && $existingSubmission) {
                                    echo "<p class='text-xs text-blue-500 mt-1 font-bold'>Wgranie pliku zastąpi poprzedni wgrany w tym polu.</p>";
                                }
                            } else {
                                echo "<input type='{$field['type']}' name='data[{$fieldId}]' value='{$val}' class='w-full border p-2.5 rounded shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none' required>";
                            }
                            echo "</div>";
                        }
                        $btnText = $isEditing ? 'Zaktualizuj dane' : 'Wyślij';
                        echo '<button class="md:col-span-3 bg-primary hover:opacity-90 text-white font-bold py-3.5 rounded-lg mt-2 transition shadow-md">'.$btnText.'</button>';
                        if ($isEditing) {
                            echo '<a href="?" class="md:col-span-3 text-center text-sm font-bold text-gray-500 hover:text-gray-800">Anuluj edycję</a>';
                        }
                        echo '</form>';
                    }
                    echo '</div>';
                }
            } 
            // 11. LINKED IMAGE
            elseif ($block['type'] === 'linked_image') {
                $img = $block['content']['url'] ?? '';
                $link = $block['content']['link'] ?? '#';
                if ($img) {
                    echo '<div class="mb-8"><a href="'.htmlspecialchars($link).'"><img src="'.htmlspecialchars($img).'" class="w-full rounded-xl shadow-md hover:opacity-90 transition transform hover:scale-[1.01]"></a></div>';
                }
            } 
            // 12. CAROUSEL
            elseif ($block['type'] === 'carousel') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $tabs = $data['tabs'] ?? [];
                $arrows = $data['arrows'] ?? false;
                $cid = 'c_' . (!empty($set['id']) ? htmlspecialchars($set['id']) : substr(md5(json_encode($tabs)), 0, 8));

                if (!empty($tabs)) {
                    echo '<div class="mb-10 carousel-wrapper" id="'.$cid.'">';
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
            // 13. GALLERY
            elseif ($block['type'] === 'gallery') {
                $gal = $db->query("SELECT * FROM pa_galleries WHERE id = :id", ['id' => $block['content']])->fetch();
                if ($gal) {
                    $imgs = json_decode($gal['images_json'], true);
                    echo '<div class="mb-8"><h3 class="text-xl font-bold mb-4">'.htmlspecialchars($gal['title']).'</h3>';
                    if ($gal['type'] === 'grid') {
                        echo '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">';
                        foreach($imgs as $img) echo "<img src='$img' class='w-full h-40 object-cover rounded-lg shadow-sm hover:scale-105 transition duration-300'>";
                        echo '</div>';
                    } else {
                        echo '<div class="flex overflow-x-auto gap-4 pb-4 snap-x">';
                        foreach($imgs as $img) echo "<img src='$img' class='snap-center w-72 h-48 object-cover rounded-lg shadow-sm flex-shrink-0 hover:opacity-90 transition'>";
                        echo '</div>';
                    }
                    echo '</div>';
                }
            } 
            // 14. IMAGE CARDS (Siatka Kart)
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
                        
                        echo '<a href="'.$link.'"'.$cId.' class="block bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 group flex flex-col h-full'.$cCls.'"'.$cStyle.'>';
                        echo '<div class="h-48 w-full overflow-hidden bg-gray-100">';
                        if (!empty($card['img'])) {
                            echo '<img src="'.htmlspecialchars($card['img']).'" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">';
                        }
                        echo '</div>';
                        echo '<div class="p-6 flex-1 flex flex-col justify-center">';
                        echo '<h3 class="text-orange-500 font-bold text-xl mb-2 leading-tight group-hover:text-orange-600 transition">'.htmlspecialchars($card['title']).'</h3>';
                        if (!empty($card['subtitle'])) {
                            echo '<p class="text-gray-600 text-sm">'.htmlspecialchars($card['subtitle']).'</p>';
                        }
                        echo '</div>';
                        echo '</a>';
                    }
                    echo '</div>';
                }
            } 
            // 15. BANNER
            elseif ($block['type'] === 'banner') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $bg = $data['bg'] ?? '';
                $title = $data['title'] ?? '';
                $subtitle = $data['subtitle'] ?? '';
                
                echo '<div class="relative w-screen h-64 md:h-[500px] bg-cover bg-center mb-10" style="margin-left: calc(-50vw + 50%); background-image: url(\''.htmlspecialchars($bg).'\');">';
                if (!empty($title)) {
                    echo ' <div class="absolute bottom-8 left-0 w-11/12 md:w-2/3 bg-white/90 p-6 md:pl-16 backdrop-blur-sm shadow-xl rounded-r-2xl">';
                    echo ' <h1 class="text-3xl md:text-5xl font-extrabold text-orange-500 tracking-wide drop-shadow-sm">'.htmlspecialchars($title).'</h1>';
                    echo ' </div>';
                }
                echo '</div>';
                
                if (!empty($subtitle)) {
                    echo '<div class="text-gray-800 font-medium mb-10 text-lg md:text-xl max-w-4xl border-l-4 border-orange-500 pl-5 leading-relaxed">'.nl2br(htmlspecialchars($subtitle)).'</div>';
                }
            } 
            // 16. RAW HTML
            elseif ($block['type'] === 'raw_html') {
                echo $block['content'];
            }
            // 17. MAPA LEAFLET
            elseif ($block['type'] === 'map') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $lat = $data['lat'] ?? '52.2297';
                $lng = $data['lng'] ?? '21.0122';
                $zoom = $data['zoom'] ?? '13';
                $tooltip = htmlspecialchars($data['tooltip'] ?? '');
                $mapId = 'map_' . uniqid();
                
                echo '<div class="mb-8 w-full h-[400px] rounded-xl shadow-lg border border-gray-200 z-10" id="'.$mapId.'"></div>';
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        var map = L.map('{$mapId}').setView([{$lat}, {$lng}], {$zoom});
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(map);
                        L.marker([{$lat}, {$lng}]).addTo(map).bindPopup('{$tooltip}').openPopup();
                    });
                </script>";
            }
            // 18. ODLICZANIE
            elseif ($block['type'] === 'countdown') {
                $data = is_array($block['content']) ? $block['content'] : [];
                $targetDate = $data['date'] ?? '';
                $title = htmlspecialchars($data['title'] ?? '');
                $cdId = 'cd_' . uniqid();
                
                if ($targetDate) {
                    echo '<div class="mb-8 bg-gray-900 text-white p-8 rounded-2xl shadow-xl text-center">';
                    echo '<h3 class="text-xl md:text-2xl font-bold mb-6 text-gray-300">'.$title.'</h3>';
                    echo '<div id="'.$cdId.'" class="flex justify-center gap-4 md:gap-8 text-3xl md:text-5xl font-extrabold text-orange-500">';
                    echo '<div><span class="days block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Dni</span></div>';
                    echo '<div class="text-gray-600">:</div>';
                    echo '<div><span class="hours block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Godzin</span></div>';
                    echo '<div class="text-gray-600">:</div>';
                    echo '<div><span class="minutes block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Minut</span></div>';
                    echo '<div class="text-gray-600">:</div>';
                    echo '<div><span class="seconds block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Sekund</span></div>';
                    echo '</div></div>';
                    
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            var target = new Date('{$targetDate}').getTime();
                            var el = document.getElementById('{$cdId}');
                            var interval = setInterval(function() {
                                var now = new Date().getTime();
                                var distance = target - now;
                                if (distance < 0) { clearInterval(interval); return; }
                                el.querySelector('.days').innerText = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
                                el.querySelector('.hours').innerText = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
                                el.querySelector('.minutes').innerText = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
                                el.querySelector('.seconds').innerText = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');
                            }, 1000);
                        });
                    </script>";
                }
            }
            // 19. TABELA
            elseif ($block['type'] === 'table') {
                $raw = $block['content'] ?? '';
                if ($raw) {
                    $rows = explode("\n", trim($raw));
                    echo '<div class="overflow-x-auto mb-8 bg-white rounded-xl shadow-sm border border-gray-200">';
                    echo '<table class="min-w-full text-left text-sm">';
                    foreach ($rows as $index => $row) {
                        $cells = explode("\t", $row);
                        if ($index === 0) {
                            echo '<thead class="bg-gray-50 border-b"><tr>';
                            foreach ($cells as $cell) echo '<th class="px-6 py-4 font-bold text-gray-700">'.htmlspecialchars($cell).'</th>';
                            echo '</tr></thead><tbody class="divide-y divide-gray-100">';
                        } else {
                            echo '<tr class="hover:bg-gray-50 transition">';
                            foreach ($cells as $cell) echo '<td class="px-6 py-4 text-gray-600">'.htmlspecialchars($cell).'</td>';
                            echo '</tr>';
                        }
                    }
                    echo '</tbody></table></div>';
                }
            }
            // 20. PRZELOT
            elseif ($block['type'] === 'flight') {
                echo '<div class="flight-container mb-8">';
                // Obsługa starszych wpisów (string) oraz nowych hybrydowych (array)
                if (is_array($block['content']) && isset($block['content']['html'])) {
                    echo $block['content']['html'];
                } else {
                    echo $block['content'] ?? '';
                }
                echo '</div>';
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
        <footer class="bg-gray-100 text-center p-6 text-gray-500 text-sm mt-auto border-t">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['site_title'] ?? 'Footer Placeholder') ?>.
        </footer>
    <?php endif; ?>
<?php endif; ?>

<script>
    // Karuzela JS
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

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.carousel-wrapper').forEach(wrapper => {
            const cid = wrapper.id;
            const savedIndex = localStorage.getItem('active_tab_' + cid);
            if (savedIndex !== null) {
                showCarouselTab(cid, parseInt(savedIndex));
            }
        });
    });

    // Mobilne Menu Burger
    document.getElementById('burger-btn').addEventListener('click', function() {
        const menu = document.getElementById('mobile-menu');
        menu.classList.toggle('hidden');
        menu.classList.toggle('flex');
    });
</script>
</body>
</html>