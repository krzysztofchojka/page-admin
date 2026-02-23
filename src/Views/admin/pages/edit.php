<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Page Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <style>
        .ghost { opacity: 0.5; background: #e0e7ff; border: 2px dashed #4f46e5; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
        .drop-zone { min-height: 100px; padding-bottom: 20px; }
        
        /* Fix for Quill Toolbar z-index issue */
        .ql-toolbar { background: white; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
        .ql-container { background: white; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; font-size: 16px; }
        .ql-editor { min-height: 150px; }
        
        /* Styl dla paska bocznego przy przeciąganiu */
        .sidebar-ghost { opacity: 0.5; }
        
        /* Navigator drag styles */
        .nav-ghost { opacity: 0.5; background: #eff6ff; border: 1px dashed #3b82f6; }
        .drag-handle-nav { cursor: grab; }
        .drag-handle-nav:active { cursor: grabbing; }
        #navigator-tree li div{ padding:0 3px;}
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">

    <div class="bg-white shadow p-4 flex justify-between items-center z-50 shrink-0">
        <div class="flex items-center gap-4">
            <a href="/admin/pages" class="text-gray-500 hover:text-black font-bold">← Back</a>
            <input type="text" id="pageTitle" value="<?= htmlspecialchars($page['title']) ?>" class="text-2xl font-bold border-b border-transparent hover:border-gray-300 focus:outline-none px-2 text-gray-800" placeholder="Page Title" />
            <input type="text" id="pageSlug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" class="bg-gray-100 border-none text-sm px-3 py-1.5 rounded-full w-48 text-gray-500" placeholder="/slug-strony" />
        </div>
        <div class="flex gap-2 items-center">
            <span class="text-sm text-gray-400 mr-4 hidden md:inline">Zarządzaj układem po lewej, a blokami po prawej →</span>
            <button onclick="savePage()" class="ml-4 bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg shadow font-bold transition">Zapisz stronę</button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">

        <div class="w-64 bg-gray-50 border-r shadow-lg flex flex-col shrink-0 z-40 relative">
            <div class="p-4 bg-white border-b font-bold text-gray-700 uppercase tracking-wide text-sm flex justify-between items-center shadow-sm">
                <span>Nawigator</span>
                <span class="text-lg">🗺️</span>
            </div>
            <div class="p-3 bg-blue-50 border-b text-xs text-blue-800 font-medium">
                Przeciągaj elementy by zmienić ich kolejność. Kliknij, by przejść do edycji w podglądzie.
            </div>
            <div class="flex-1 overflow-y-auto p-3">
                <ul id="navigator-tree" class="nav-drop-zone min-h-[50px] pb-10" data-ref-zone="editor">
                    </ul>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 relative scroll-smooth" id="main-editor-scroll">
            <div id="editor" class="drop-zone max-w-5xl mx-auto min-h-[800px] bg-white shadow-xl rounded-lg p-8 grid grid-cols-1 gap-6 border-2 border-transparent" data-zone-uid="editor">
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

                <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Układ (Layout)</div>
                <div class="sidebar-block border bg-white hover:border-indigo-500 hover:bg-indigo-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="columns_2"><span class="text-lg text-indigo-400">◫</span>2 Kolumny</div>
                <div class="sidebar-block border bg-white hover:border-emerald-500 hover:bg-emerald-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="columns_3"><span class="text-lg text-emerald-400">☰</span>3 Kolumny</div>

                <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Zaawansowane</div>
                <div class="sidebar-block border bg-white hover:border-orange-500 hover:bg-orange-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="banner"><span class="text-lg text-orange-400">🏔</span>Baner (Hero)</div>
                <div class="sidebar-block border bg-white hover:border-orange-500 hover:bg-orange-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="image_cards"><span class="text-lg text-orange-500">🗂</span>Siatka Kart</div>
                <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="carousel"><span class="text-lg text-blue-400">🎠</span>Karuzela</div>
                <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="accordion"><span class="text-lg text-purple-500">⇕</span>Akordeon / FAQ</div>
                <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="linked_image"><span class="text-lg text-blue-500">🔗</span>Obraz + Link</div>

                <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Turystyka i Eventy</div>
                <div class="sidebar-block border bg-white hover:border-red-500 hover:bg-red-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="map"><span class="text-lg text-red-500">📍</span>Mapa</div>
                <div class="sidebar-block border bg-white hover:border-purple-500 hover:bg-purple-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="countdown"><span class="text-lg text-purple-500">⏳</span>Odliczanie</div>
                <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="table"><span class="text-lg text-blue-500">🗄️</span>Tabela</div>
                <div class="sidebar-block border bg-white hover:border-sky-500 hover:bg-sky-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="flight"><span class="text-lg text-sky-500">✈️</span>Loty</div>

                <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Integracje</div>
                <div class="sidebar-block border bg-white hover:border-teal-500 hover:bg-teal-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="form"><span class="text-lg text-teal-500">📝</span>Formularz</div>
                <div class="sidebar-block border bg-white hover:border-pink-500 hover:bg-pink-50 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="gallery"><span class="text-lg text-pink-500">📷</span>Galeria</div>
                <div class="sidebar-block border bg-white hover:border-gray-800 hover:bg-gray-100 p-3 rounded shadow-sm cursor-grab text-center text-sm font-bold transition flex flex-col items-center gap-1" data-type="raw_html"><span class="text-lg text-gray-800">&lt;/&gt;</span>HTML</div>
            </div>
            <div class="p-4 bg-blue-50 border-t text-xs text-blue-800 font-bold text-center">
                Wybierz blok, przytrzymaj lewy przycisk myszy i przeciągnij na środek ekranu.
            </div>
        </div>
    </div>

    <div id="mediaPickerModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[100] flex justify-center items-center p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                <h3 class="font-bold text-lg">Wybierz plik</h3>
                <button onclick="closeMediaPicker()" class="text-red-500 font-bold text-xl">&times;</button>
            </div>
            <div class="flex-1">
                <iframe id="mediaPickerFrame" class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </div>

    <script>
    // Iniekcja zmiennych z PHP do globalnego obiektu JS (lub zmiennych)
    window.CMS_CONFIG = {
        pageId: <?= $page['id'] ?>,
        savedContent: <?= $page['contents'] ? $page['contents'] : '[]' ?>,
        availableForms: <?= json_encode($forms ?? []) ?>,
        availableGalleries: <?= json_encode($galleries ?? []) ?>
    };
</script>
<script src="<?= \CMS\Helpers\Asset::url('/assets/js/admin/page-builder.js') ?>"></script>
</body>
</html>