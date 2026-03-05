<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Builder</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>
    <style>
        .ghost { opacity: 0.5; background: #e0e7ff; border: 2px dashed #4f46e5; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
        .drop-zone { min-height: 100px; padding-bottom: 20px; padding: 0 !important; border-radius:14px !important;}
        .block-item, .block-item *, .ql-editor, .ql-editor * {margin-bottom:0 !important;}
        .ql-toolbar { background: white; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
        .ql-container { background: white; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; font-size: 16px; }
        .ql-editor { min-height: 150px; }
        .sidebar-ghost { opacity: 0.5; }
        .nav-ghost { opacity: 0.5; background: #eff6ff; border: 1px dashed #3b82f6; }
        .drag-handle-nav { cursor: grab; }
        .drag-handle-nav:active { cursor: grabbing; }
        #navigator-tree li div{ padding:0 3px;}
        .ql-snow .ql-picker.ql-size .ql-picker-label::before,
.ql-snow .ql-picker.ql-size .ql-picker-item::before {
  content: attr(data-value) !important;
}
.quill-source-area {
  width: 100%;
  height: 300px;
  background: #ffffff;
  padding: 10px;
  font-family: monospace;
  box-sizing: border-box;
  border: 1px solid #ccc;
}
/* Wymuszenie czarnego koloru, ale z BEZWZGLĘDNYM WYKLUCZENIEM edytora Ace */
.block-item *:not(.ace_editor):not(.ace_editor *), 
.ql-editor, 
.ql-editor * { 
    color: #1a1a1a !important; 
}
.block-item span, .block-item label { color: inherit !important; }

/* Naprawa koloru ikon i małych napisów technicznych w edytorze, 
   które mogły zniknąć */
.block-item span, 
.block-item label {
    color: inherit !important;
}
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">
    
    <div class="bg-white shadow p-4 flex justify-between items-center z-50 shrink-0">
        <div class="flex items-center gap-4">
            <a href="/admin/pages" class="text-gray-500 hover:text-black font-bold">← Back</a>
            <input type="text" id="pageTitle" value="<?= htmlspecialchars($page['title']) ?>" class="text-2xl font-bold border-b border-transparent hover:border-gray-300 focus:outline-none px-2 text-gray-800" placeholder="Page Title" />
            <input type="text" id="pageSlug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" class="bg-gray-100 border-none text-sm px-3 py-1.5 rounded-full w-48 text-gray-500" placeholder="/slug-strony" />
            
            <input type="hidden" id="pageTemplate" value="<?= $page['template_id'] ?? '' ?>">
        </div>
        <div class="flex gap-3 items-center">
    <?php if (!empty($page['template_id'])): ?>
        <span class="bg-blue-100 text-blue-800 text-xs font-bold px-3 py-1.5 rounded-full border border-blue-200 shadow-sm pointer-events-none">
            Szablon: <?= htmlspecialchars($templateName) ?>
        </span>
    <?php endif; ?>
    
    <select id="pageRole" class="text-sm border border-gray-300 rounded-lg px-3 py-2 text-gray-700 font-bold bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-sm">
        <option value="standard" <?= $currentRole == 'standard' ? 'selected' : '' ?>>📄 Zwykła strona</option>
        <option value="home_page_id" <?= $currentRole == 'home_page_id' ? 'selected' : '' ?>>🏠 Strona Główna</option>
        <option value="footer_page_id" <?= $currentRole == 'footer_page_id' ? 'selected' : '' ?>>🦶 Globalna Stopka</option>
        <option value="login_page_id" <?= $currentRole == 'login_page_id' ? 'selected' : '' ?>>🔐 Logowanie</option>
        <option value="register_page_id" <?= $currentRole == 'register_page_id' ? 'selected' : '' ?>>📝 Rejestracja</option>
        <option value="change_password_page_id" <?= $currentRole == 'change_password_page_id' ? 'selected' : '' ?>>🔑 Zmiana Hasła</option>
        <option value="lockdown_page_id" <?= $currentRole == 'lockdown_page_id' ? 'selected' : '' ?>>🚧 Ekran Lockdown</option>
    </select>

    <button onclick="savePage()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg shadow font-bold transition">Zapisz stronę</button>
</div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        <div class="w-64 bg-gray-50 border-r shadow-lg flex flex-col shrink-0 z-40 relative">
            <div class="p-4 bg-white border-b font-bold text-gray-700 uppercase tracking-wide text-sm flex justify-between items-center shadow-sm">
                <span>Nawigator</span>
                <span class="text-lg">🗺️</span>
            </div>
            <div class="p-3 bg-blue-50 border-b text-xs text-blue-800 font-medium">
                Przeciągaj elementy by zmienić ich kolejność.
            </div>
            <div class="flex-1 overflow-y-auto p-3">
                <ul id="navigator-tree" class="nav-drop-zone min-h-[50px] pb-10" data-ref-zone="editor"></ul>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 relative scroll-smooth" id="main-editor-scroll">
            <div id="editor-wrapper" class="max-w-7xl mx-auto">
                <?php
                if (!empty($page['template_id'])) {
                    $tpl = $db->query("SELECT html_content FROM pa_templates WHERE id = ?", [$page['template_id']])->fetch();
                    $html = $tpl['html_content'] ?? '';
                    
                    // Usuwamy znacznik global_footer z widoku edytora, żeby nie zaśmiecał kodu CSS/HTML
                    $html = str_replace('{{global_footer}}', '<div class="bg-gray-200 text-gray-500 text-center p-4 rounded-lg my-4 text-xs font-bold uppercase border-2 border-dashed border-gray-300 pointer-events-none">W tym miejscu wyrenderuje się globalna stopka</div>', $html);

                    $html = preg_replace_callback('/\{\{zone:([a-zA-Z0-9_]+)\}\}/', function($matches) {
                        $zoneId = $matches[1];
                        return '<div class="drop-zone min-h-[100px] border-2 border-dashed border-blue-400 bg-blue-50/50 p-4 rounded-lg relative" data-zone-uid="'.$zoneId.'">
                                    <span class="absolute top-0 right-0 bg-blue-400 text-white text-[10px] font-bold px-2 py-1 rounded-bl-lg rounded-tr-lg">STREFA: '.$zoneId.'</span>
                                </div>';
                    }, $html);
                    echo $html;
                } else {
                    echo '<div id="editor" class="drop-zone max-w-5xl mx-auto min-h-[800px] bg-white shadow-xl rounded-lg p-8 grid grid-cols-1 gap-6 border-2 border-transparent" data-zone-uid="editor"></div>';
                }
                ?>
            </div>
        </div>

        <div class="w-80 bg-white border-l shadow-lg flex flex-col shrink-0 z-40">
            <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 uppercase tracking-wide text-sm flex justify-between items-center">
                <span>Dostępne Bloki</span>
                <span class="text-xl">🧩</span>
            </div>
            <div id="block-sidebar" class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3 content-start">
    
    <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-2 mb-1 border-b pb-1">Podstawowe</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="text"><span class="text-lg text-blue-500">T</span>Tekst</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="image"><span class="text-lg text-green-500">🖼</span>Obrazek</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="video"><span class="text-lg text-red-500">▶️</span>Wideo</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="button"><span class="text-lg text-indigo-500">🔘</span>Przycisk</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="divider"><span class="text-lg text-gray-400">➖</span>Odstęp</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="quote"><span class="text-lg text-yellow-500">❝</span>Cytat</div>

    <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Układ</div>
    <div class="sidebar-block border bg-white hover:border-indigo-500 hover:bg-indigo-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="columns_2"><span class="text-lg text-indigo-400">◫</span>2 Kolumny</div>
    <div class="sidebar-block border bg-white hover:border-emerald-500 hover:bg-emerald-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="columns_3"><span class="text-lg text-emerald-400">☰</span>3 Kolumny</div>
    <div class="sidebar-block border bg-white hover:border-orange-500 hover:bg-orange-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="banner"><span class="text-lg text-orange-400">🏔</span>Baner (Hero)</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="accordion"><span class="text-lg text-purple-500">⇕</span>Akordeon</div>

    <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Złożone Treści</div>
    <div class="sidebar-block border bg-white hover:border-pink-500 hover:bg-pink-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="gallery"><span class="text-lg text-pink-500">📷</span>Galeria</div>
    <div class="sidebar-block border bg-white hover:border-purple-500 p-3 rounded cursor-grab text-center text-sm font-bold flex flex-col items-center gap-1" data-type="posts_grid"><span class="text-lg text-purple-500">📰</span>Posty / Blog</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="carousel"><span class="text-lg text-blue-400">🎠</span>Karuzela</div>
    <div class="sidebar-block border bg-white hover:border-orange-500 hover:bg-orange-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="image_cards"><span class="text-lg text-orange-500">🗂</span>Siatka Kart</div>
    <div class="sidebar-block border bg-white hover:border-teal-500 hover:bg-teal-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="form"><span class="text-lg text-teal-500">📝</span>Formularz</div>
    <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="table"><span class="text-lg text-blue-500">🗄️</span>Tabela</div>

    <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Dodatki</div>
    <div class="sidebar-block border bg-white hover:border-red-500 hover:bg-red-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="map"><span class="text-lg text-red-500">📍</span>Mapa</div>
    <div class="sidebar-block border bg-white hover:border-purple-500 hover:bg-purple-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="countdown"><span class="text-lg text-purple-500">⏳</span>Odliczanie</div>
    <div class="sidebar-block border bg-white hover:border-sky-500 hover:bg-sky-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="flight"><span class="text-lg text-sky-500">✈️</span>Loty</div>
    <div class="sidebar-block border bg-white hover:border-gray-800 hover:bg-gray-100 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="raw_html"><span class="text-lg text-gray-800">&lt;/&gt;</span>HTML</div>

    <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Systemowe</div>
    <div class="sidebar-block border bg-white hover:border-red-500 hover:bg-red-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="system_login"><span class="text-lg text-red-500">🔐</span>Logowanie</div>
    <div class="sidebar-block border bg-white hover:border-red-500 hover:bg-red-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="system_register"><span class="text-lg text-red-500">📝</span>Rejestracja</div>

    <div class="sidebar-block border bg-white hover:border-red-500 hover:bg-red-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="system_change_password"><span class="text-lg text-red-500">🔑</span>Zmień Hasło</div>
<div class="sidebar-block border bg-white hover:border-red-500 hover:bg-red-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="system_lockdown"><span class="text-lg text-red-500">🚧</span>Lockdown</div>
</div>
            <div class="p-4 bg-blue-50 border-t text-xs text-blue-800 font-bold text-center">
                Wybierz blok, przytrzymaj lewy przycisk myszy i przeciągnij.
            </div>
        </div>
    </div>

    <div id="mediaPickerModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[100] flex justify-center items-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                <h3 class="font-bold text-lg">Wybierz plik</h3>
                <button onclick="closeMediaPicker()" class="text-red-500 font-bold text-xl">&times;</button>
            </div>
            <div class="flex-1"><iframe id="mediaPickerFrame" class="w-full h-full border-0"></iframe></div>
        </div>
    </div>

    <script>
        // BEZPIECZNE INIEKTOWANIE ZAWARTOSCI - Uodpornione na błędy parsowania
        window.CMS_CONFIG = {
            pageId: <?= $page['id'] ?>,
            savedContent: JSON.parse(decodeURIComponent('<?= rawurlencode($page['contents'] ?: '[]') ?>')),
            availableForms: <?= json_encode($forms ?? []) ?>,
            availableGalleries: <?= json_encode($galleries ?? []) ?>,
            availableCategories: <?= json_encode($postCategories ?? []) ?> // <-- DODANA ZMIENNA
        };
    </script>
    <script src="<?= \CMS\Helpers\Asset::url('/assets/js/admin/page-builder.js') ?>"></script>
</body>
</html>