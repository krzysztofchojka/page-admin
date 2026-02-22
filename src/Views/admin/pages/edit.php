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
            <span class="text-sm text-gray-400 mr-4 hidden md:inline">Przeciągnij bloki z paska po prawej →</span>
            <button onclick="savePage()" class="ml-4 bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg shadow font-bold transition">Zapisz stronę</button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        
        <div class="flex-1 overflow-y-auto p-8 relative">
            <div id="editor" class="drop-zone max-w-5xl mx-auto min-h-[800px] bg-white shadow-xl rounded-lg p-8 grid grid-cols-1 gap-6 border-2 border-transparent">
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
    // 1. DATA INJECTION
    const pageId = <?= $page['id'] ?>;
    const savedContent = <?= $page['contents'] ? $page['contents'] : '[]' ?>;
    const availableForms = <?= json_encode($forms ?? []) ?>;
    const availableGalleries = <?= json_encode($galleries ?? []) ?>;
    
    // Global Registry for Quill Instances
    const quillRegistry = {};

    // 2. INITIALIZATION
    let activeContainer = document.getElementById('editor');

    // Setup Sidebar Drag & Drop
    Sortable.create(document.getElementById('block-sidebar'), {
        group: {
            name: 'shared',
            pull: 'clone', // Klonuj zamiast przenosić
            put: false     // Nie pozwalaj odkładać na pasek boczny
        },
        animation: 150,
        sort: false,       // Nie sortuj paska bocznego
        ghostClass: 'sidebar-ghost'
    });

    // Initialize the main editor
    initSortable(activeContainer);

    // Load Saved Data
    renderRecursive(savedContent, activeContainer);


    // 3. CORE FUNCTIONS

    function initSortable(el) {
        Sortable.create(el, {
            group: 'shared',
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'ghost',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            filter: '.no-drag',
            preventOnFilter: false,
            onAdd: function (evt) {
                const itemEl = evt.item;
                
                // Jeśli element przyszedł z paska bocznego (ma naszą klasę)
                if (itemEl.classList.contains('sidebar-block')) {
                    const type = itemEl.dataset.type;
                    
                    // Wyrenderuj pełny blok
                    const newBlock = renderBlock(type);
                    
                    // Zastąp "pustą" kopię z paska bocznego wygenerowanym blokiem HTML
                    itemEl.parentNode.insertBefore(newBlock, itemEl);
                    itemEl.parentNode.removeChild(itemEl);

                    // Jeśli dodano blok tekstowy (lub inny wymagający inita), zainicjalizuj go
                    if (type === 'text') {
                        initQuill(newBlock.querySelector('.quill-editor'), '');
                    }
                    if (type === 'accordion') {
                        initSortable(newBlock.querySelector('.tab-content'));
                    }
                }
            }
        });

        el.addEventListener('click', (e) => {
            if(e.target === el) {
                activeContainer = el;
                highlightContainer(el);
                e.stopPropagation();
            }
        });
    }

    function highlightContainer(el) {
        document.querySelectorAll('.ring-2').forEach(d => d.classList.remove('ring-2', 'ring-blue-300'));
        if(el.id !== 'editor') el.classList.add('ring-2', 'ring-blue-300');
    }

    function renderRecursive(blocks, container) {
        blocks.forEach(block => {
            const el = renderBlock(block.type, block.content, block.settings);
            container.appendChild(el);

            if (block.type === 'text') {
                initQuill(el.querySelector('.quill-editor'), block.content);
            }
            if (block.type === 'columns_2' && block.children) {
                if(block.children.left) renderRecursive(block.children.left, el.querySelector('.col-left'));
                if(block.children.right) renderRecursive(block.children.right, el.querySelector('.col-right'));
            }
            if (block.type === 'columns_3' && block.children) {
                if(block.children.left) renderRecursive(block.children.left, el.querySelector('.col-left'));
                if(block.children.center) renderRecursive(block.children.center, el.querySelector('.col-center'));
                if(block.children.right) renderRecursive(block.children.right, el.querySelector('.col-right'));
            }
            if (block.type === 'carousel' && block.content && block.content.tabs) {
                const tabZones = el.querySelectorAll('.tab-content');
                block.content.tabs.forEach((tab, idx) => {
                    if (tab.children && tabZones[idx]) {
                        renderRecursive(tab.children, tabZones[idx]);
                    }
                });
            }
            if (block.type === 'accordion' && block.children) {
                renderRecursive(block.children, el.querySelector('.tab-content'));
            }
        });
    }

    function initQuill(element, content) {
        const id = 'quill_' + Math.random().toString(36).substr(2, 9);
        element.id = id;
        const quill = new Quill('#' + id, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });
        if(content) quill.root.innerHTML = content;
        quillRegistry[id] = quill;
    }

    // Block Factory
    function renderBlock(type, content = '', blockSettings = null) {
        const div = document.createElement('div');
        div.className = "group relative border border-gray-200 hover:border-blue-400 rounded-xl p-5 mb-4 bg-white transition-all shadow-sm block-item";
        div.dataset.type = type;

        const settings = blockSettings || { id: '', css: '', style: '' };

        const controls = `
        <div class="absolute -top-3 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-50">
            <span class="drag-handle text-gray-600 hover:text-blue-600 p-1.5 bg-gray-100 border border-gray-300 rounded shadow cursor-move" title="Przeciągnij (Drag & Drop)">✥ Przesuń</span>
            <button onclick="this.parentElement.nextElementSibling.classList.toggle('hidden')" class="text-gray-600 hover:text-gray-800 p-1.5 bg-gray-100 border border-gray-300 rounded shadow" title="Ustawienia (ID, Klasy CSS)">⚙️</button>
            <button onclick="if(confirm('Na pewno usunąć ten blok?')) this.closest('.block-item').remove()" class="text-red-500 hover:text-red-700 p-1.5 bg-gray-100 border border-gray-300 rounded shadow" title="Usuń blok">🗑</button>
        </div>
        <div class="block-settings-panel hidden bg-blue-50 p-4 mt-6 mb-4 border border-blue-200 rounded text-sm shadow-inner">
            <div class="grid grid-cols-3 gap-4">
                <div><label class="block font-bold text-gray-600 mb-1">ID HTML</label><input type="text" class="set-id w-full border p-2 rounded" placeholder="np. sekcja-1" value="${settings.id || ''}"></div>
                <div><label class="block font-bold text-gray-600 mb-1">Klasy CSS (Tailwind)</label><input type="text" class="set-css w-full border p-2 rounded" placeholder="np. mt-10 text-center" value="${settings.css || ''}"></div>
                <div><label class="block font-bold text-gray-600 mb-1">Style wew. (Inline)</label><input type="text" class="set-style w-full border p-2 rounded" placeholder="np. background: #000;" value="${settings.style || ''}"></div>
            </div>
        </div>`;

        let innerHTML = '';

        // --- OLD BLOCKS (Text, Columns, Forms etc...) ---
        if (type === 'text') {
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-blue-500 uppercase">T Tekst / Edytor Wizualny</span></div>
            <div class="bg-gray-50 border rounded-lg no-drag"><div class="quill-editor"></div></div>`;
        } 
        else if (type === 'raw_html') {
            innerHTML = `
            <div class="bg-gray-900 rounded p-3 no-drag border border-gray-700">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-xs font-mono text-gray-400">&lt;/&gt; KOD HTML</label>
                </div>
                <textarea class="w-full h-32 bg-gray-800 text-green-400 font-mono text-sm p-3 rounded focus:outline-none block-content placeholder-gray-600">${content}</textarea>
            </div>`;
        } 
        else if (type === 'columns_2') {
            div.className += " border-2 border-dashed border-indigo-200 bg-indigo-50/20";
            innerHTML = `
            <div class="flex gap-2 mb-3"><span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">◫ Układ: 2 Kolumny</span></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-left drop-zone bg-white border border-indigo-100 rounded-lg p-4 min-h-[120px]" onclick="setActive(this, event)"></div>
                <div class="col-right drop-zone bg-white border border-indigo-100 rounded-lg p-4 min-h-[120px]" onclick="setActive(this, event)"></div>
            </div>`;
            setTimeout(() => { initSortable(div.querySelector('.col-left')); initSortable(div.querySelector('.col-right')); }, 0);
        }
        else if (type === 'columns_3') {
            div.className += " border-2 border-dashed border-emerald-200 bg-emerald-50/20";
            innerHTML = `
            <div class="flex gap-2 mb-3"><span class="text-xs font-bold text-emerald-500 uppercase tracking-wider">☰ Układ: 3 Kolumny</span></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="col-left drop-zone bg-white border border-emerald-100 rounded-lg p-4 min-h-[120px]" onclick="setActive(this, event)"></div>
                <div class="col-center drop-zone bg-white border border-emerald-100 rounded-lg p-4 min-h-[120px]" onclick="setActive(this, event)"></div>
                <div class="col-right drop-zone bg-white border border-emerald-100 rounded-lg p-4 min-h-[120px]" onclick="setActive(this, event)"></div>
            </div>`;
            setTimeout(() => { initSortable(div.querySelector('.col-left')); initSortable(div.querySelector('.col-center')); initSortable(div.querySelector('.col-right')); }, 0);
        }
        else if (type === 'image') {
            const hasImg = content && content.length > 5;
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-green-500 uppercase">🖼 Pojedynczy Obrazek</span></div>
            <div class="${hasImg ? 'relative' : 'p-10 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 text-center cursor-pointer transition'}" onclick="triggerUpload(this)">
                ${hasImg ? `<img src="${content}" class="w-full rounded shadow"><input type="hidden" class="block-content" value="${content}">` 
                         : `<div class="pointer-events-none"><span class="text-3xl block mb-2">📸</span><span class="text-gray-500 font-bold">Kliknij, aby wybrać lub wgrać obraz</span></div><input type="hidden" class="block-content" value="">`}
            </div>`;
        }
        else if (type === 'linked_image') {
            const data = typeof content === 'object' ? content : { url: '', link: '' };
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-indigo-600 uppercase">🔗 Podlinkowany Obrazek</span></div>
            <label class="block text-xs font-bold text-gray-500 mb-1">URL Obrazka (Wklej link)</label>
            ${imgInputTpl('block-img-url', 'URL Obrazka', data.url)}
            <label class="block text-xs font-bold text-gray-500 mt-3 mb-1">Link docelowy po kliknięciu</label>
            <input type="text" class="block-link-url w-full border p-2 text-sm rounded bg-gray-50 focus:bg-white" placeholder="https://..." value="${data.link || ''}">`;
        }
        else if (type === 'form') {
            innerHTML = `
            <div class="bg-teal-50 border border-teal-200 p-4 rounded-lg text-center">
                <label class="block text-sm font-bold text-teal-800 mb-2">📝 Wybierz Formularz</label>
                <select onchange="this.parentElement.dataset.val = this.value" class="block-select border rounded p-2 w-full text-center font-bold bg-white shadow-sm focus:ring-2 focus:ring-teal-500">
                    ${buildOptions(availableForms, content)}
                </select>
            </div>`;
        }
        else if (type === 'gallery') {
            innerHTML = `
            <div class="bg-pink-50 border border-pink-200 p-4 rounded-lg text-center">
                <label class="block text-sm font-bold text-pink-800 mb-2">📷 Wybierz Galerię</label>
                <select onchange="this.parentElement.dataset.val = this.value" class="block-select border rounded p-2 w-full text-center font-bold bg-white shadow-sm focus:ring-2 focus:ring-pink-500">
                    ${buildOptions(availableGalleries, content)}
                </select>
            </div>`;
        }
        else if (type === 'banner') {
            const data = typeof content === 'object' ? content : { bg: '', title: '', subtitle: '' };
            innerHTML = `
            <div class="flex items-center gap-2 mb-4"><span class="text-xs font-bold text-orange-600 uppercase">🏔 Baner (Hero Section)</span></div>
            <label class="block text-xs font-bold text-gray-500 mb-1">Zdjęcie w Tle</label>
            ${imgInputTpl('ban-bg', 'URL Zdjęcia tła', data.bg)}
            <label class="block text-xs font-bold text-gray-500 mt-3 mb-1">Główny Tytuł (Duży tekst)</label>
            <input type="text" class="ban-title w-full border p-2 text-lg rounded font-bold text-gray-800 focus:ring-2 focus:ring-orange-500" placeholder="Tytuł Baneru" value="${data.title || ''}">
            <label class="block text-xs font-bold text-gray-500 mt-3 mb-1">Podtytuł / Opis</label>
            <textarea class="ban-subtitle w-full border p-2 text-sm rounded h-20 focus:ring-2 focus:ring-orange-500" placeholder="Krótki opis...">${data.subtitle || ''}</textarea>`;
        }
        // --- MAPA LEAFLET ---
        else if (type === 'map') {
            const data = typeof content === 'object' ? content : { lat: '52.2297', lng: '21.0122', zoom: '13', tooltip: 'Warszawa' };
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase">📍 Mapa Leaflet</span></div>
            <div class="grid grid-cols-3 gap-4 mb-2">
                <div><label class="block text-xs text-gray-500 mb-1">Szerokość (Lat)</label><input type="text" class="map-lat w-full border p-2 rounded" value="${data.lat}"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Długość (Lng)</label><input type="text" class="map-lng w-full border p-2 rounded" value="${data.lng}"></div>
                <div><label class="block text-xs text-gray-500 mb-1">Zoom (1-18)</label><input type="number" class="map-zoom w-full border p-2 rounded" value="${data.zoom}"></div>
            </div>
            <label class="block text-xs text-gray-500 mb-1">Tekst na pinezce</label>
            <input type="text" class="map-tooltip w-full border p-2 rounded" placeholder="Np. Hotel Nazwa" value="${data.tooltip}">`;
        }
        // --- ODLICZANIE ---
        else if (type === 'countdown') {
            const data = typeof content === 'object' ? content : { date: '', title: 'Do startu wydarzenia:' };
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-purple-500 uppercase">⏳ Odliczanie</span></div>
            <label class="block text-xs text-gray-500 mb-1">Tytuł nad licznikiem</label>
            <input type="text" class="cd-title w-full border p-2 rounded mb-3" placeholder="Do startu wydarzenia:" value="${data.title}">
            <label class="block text-xs text-gray-500 mb-1">Data i godzina zakończenia</label>
            <input type="datetime-local" class="cd-date w-full border p-2 rounded" value="${data.date}">`;
        }
        // --- TABELA (Wklejanie z Excela) ---
        else if (type === 'table') {
            innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-blue-500 uppercase">🗄️ Tabela Danych</span>
                <div class="flex gap-2">
                    <button type="button" onclick="toggleEditor(this, 'visual')" class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded font-bold">Wizualny</button>
                    <button type="button" onclick="toggleEditor(this, 'raw')" class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded font-bold">Surowy (Excel)</button>
                </div>
            </div>
            
            <div class="editor-visual overflow-x-auto bg-gray-50 p-2 rounded border">
                <table class="w-full text-left bg-white border">
                    <tbody class="visual-table-body"></tbody>
                </table>
                <div class="mt-2 flex gap-2">
                    <button type="button" onclick="addTableRow(this)" class="text-xs bg-white border px-3 py-1 rounded shadow-sm font-bold">+ Wiersz</button>
                    <button type="button" onclick="addTableCol(this)" class="text-xs bg-white border px-3 py-1 rounded shadow-sm font-bold">+ Kolumna</button>
                </div>
            </div>
            
            <div class="editor-raw hidden">
                <label class="block text-xs text-gray-500 mb-1">Wklej tabele prosto z Excela (wartości oddzielone tabulatorem)</label>
                <textarea class="table-data w-full h-32 border p-2 text-sm rounded whitespace-pre font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" oninput="syncTableRawToVisual(this.closest('.block-item'))" placeholder="Kolumna1\\tKolumna2\\nWartość1\\tWartość2">${content || 'Nagłówek 1\\tNagłówek 2\\nWartość A\\tWartość B'}</textarea>
            </div>`;
            
            // Inicjalizacja wizualnej tabeli z surowego tekstu po wyrenderowaniu
            setTimeout(() => syncTableRawToVisual(div), 0);
        }
        else if (type === 'flight') {
            // content może być obiektem { html: '...', state: {...} } lub zwykłym stringiem (legacy)
            const data = typeof content === 'object' ? content : { html: content, state: [] };
            const rawHtml = data.html || '';
            const stateJson = encodeURIComponent(JSON.stringify(data.state || []));

            innerHTML = `
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-sky-500 uppercase">✈️ Przeloty</span>
                <div class="flex gap-2">
                    <button type="button" onclick="toggleEditor(this, 'visual')" class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded font-bold">Kreator</button>
                    <button type="button" onclick="toggleEditor(this, 'raw')" class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded font-bold">KOD HTML</button>
                </div>
            </div>

            <input type="hidden" class="flight-state" value="${stateJson}">

            <div class="editor-visual bg-sky-50 p-4 rounded-lg border border-sky-100">
                <div class="flight-segments-container flex flex-col gap-3 mb-4"></div>
                
                <div class="bg-white p-3 rounded border shadow-sm mt-4">
                    <span class="text-xs font-bold text-gray-500 block mb-2">Stopka widżetu (Podsumowanie)</span>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="text" class="flight-f-arr w-full border p-1.5 text-xs rounded" placeholder="Przylot: wt., 30 wrz 2025" oninput="syncFlightVisualToRaw(this.closest('.block-item'))">
                        <input type="text" class="flight-f-time w-full border p-1.5 text-xs rounded" placeholder="Czas trwania: 5 godz. 40 min" oninput="syncFlightVisualToRaw(this.closest('.block-item'))">
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button type="button" onclick="addFlightSegment(this, 'flight')" class="text-xs bg-white border border-sky-300 text-sky-700 px-4 py-2 rounded shadow-sm font-bold hover:bg-sky-100">✈️ Dodaj Lot</button>
                    <button type="button" onclick="addFlightSegment(this, 'connection')" class="text-xs bg-white border border-indigo-300 text-indigo-700 px-4 py-2 rounded shadow-sm font-bold hover:bg-indigo-100">⏱ Dodaj Przesiadkę</button>
                </div>
            </div>

            <div class="editor-raw hidden">
                <label class="block text-xs text-gray-500 mb-1">Wygenerowany kod HTML (Możesz edytować go ręcznie)</label>
                <textarea class="flight-html w-full h-64 border p-3 rounded font-mono text-xs bg-gray-900 text-sky-300 focus:outline-none">${rawHtml}</textarea>
            </div>`;
            
            setTimeout(() => initFlightVisual(div), 0);
        }
        // --- BLOK: SIATKA KART (IMAGE CARDS) ---
        else if (type === 'image_cards') {
            div.className += " border-2 border-dashed border-orange-300 bg-orange-50/20";
            const data = typeof content === 'object' ? content : { cards: [] };
            let cardsHTML = '';
            
            if (data.cards && data.cards.length > 0) {
                data.cards.forEach((card) => {
                    cardsHTML += `
                    <div class="image-card border border-gray-300 bg-white p-3 mb-2 rounded shadow-sm relative group grid grid-cols-2 gap-2">
                        <button type="button" onclick="this.closest('.image-card').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10" title="Ustawienia kafelka">⚙️</button>
                        <button type="button" onclick="this.closest('.image-card').remove()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">X</button>
                        <div class="col-span-2">${imgInputTpl('card-img', 'URL Zdjęcia', card.img)}</div>
                        <input type="text" class="card-title w-full border p-2 text-sm rounded font-bold text-orange-600" placeholder="Tytuł" value="${card.title || ''}">
                        <input type="text" class="card-subtitle w-full border p-2 text-sm rounded" placeholder="Podtytuł" value="${card.subtitle || ''}">
                        <div class="col-span-2"><input type="text" class="card-link w-full border p-2 text-sm rounded" placeholder="Link docelowy" value="${card.link || ''}"></div>
                        ${itemSettingsTpl(card.settings)}
                    </div>`;
                });
            }
            innerHTML = `
            <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-orange-500 uppercase">🗂 Siatka Kart (Obrazek + Tekst)</span></div>
            <div class="cards-container min-h-[5px] mb-3 grid grid-cols-1 md:grid-cols-2 gap-4">
                ${cardsHTML}
            </div>
            <button type="button" onclick="addImageCard(this)" class="bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj Kartę</button>`;
        }
        else if (type === 'carousel') {
             // Wymaga dużej modyfikacji HTMLa pod nowe panele, tutaj zostawiamy uproszczony kod dla czytelności (struktura jak wcześniej)
            div.className += " border-2 border-dashed border-blue-300 bg-blue-50/30";
            const data = typeof content === 'object' ? content : { arrows: true, tabs: [] };
            let tabsHTML = '';
            if(data.tabs) {
                data.tabs.forEach((tab) => {
                    tabsHTML += `
                    <div class="carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded-lg shadow-sm relative group">
                        <button type="button" onclick="this.closest('.carousel-tab').remove()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10 shadow">X</button>
                        <div class="flex gap-2 mb-2 w-full">
                            <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/Emoji', tab.icon)}</div>
                            <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł zakładki" value="${tab.label || ''}">
                        </div>
                        <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
                    </div>`;
                });
            }
            innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">🎠 Karuzela / Zakładki</span>
                <label class="text-sm font-bold text-gray-700 flex items-center gap-2"><input type="checkbox" class="carousel-arrows" ${data.arrows ? 'checked' : ''}> Pokaż strzałki</label>
            </div>
            <div class="carousel-tabs-container min-h-[5px] mb-3">${tabsHTML}</div>
            <button type="button" onclick="addCarouselTab(this)" class="bg-white border-2 border-blue-500 text-blue-600 hover:bg-blue-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj Zakładkę / Kafelek</button>`;
            
            setTimeout(() => { div.querySelectorAll('.tab-content').forEach(el => initSortable(el)); }, 0);
        }

        // --- NEW CMS BLOCKS ---

        else if (type === 'video') {
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase">▶️ Wideo / Embed</span></div>
            <label class="block text-xs font-bold text-gray-500 mb-1">URL (YouTube / Vimeo / MP4)</label>
            <input type="text" class="block-video w-full border p-2 rounded bg-gray-50 focus:bg-white" placeholder="https://www.youtube.com/watch?v=..." value="${content || ''}">
            <p class="text-xs text-gray-400 mt-1">Wklej pełny link do filmu lub ścieżkę do wgranego pliku .mp4</p>
            `;
        }
        else if (type === 'button') {
            const data = typeof content === 'object' ? content : { label: 'Kliknij tutaj', url: '', style: 'primary' };
            innerHTML = `
            <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-indigo-500 uppercase">🔘 Przycisk (CTA)</span></div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Tekst na Przycisku</label>
                    <input type="text" class="btn-label w-full border p-2 rounded" placeholder="Dowiedz się więcej" value="${data.label}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Adres URL (Link)</label>
                    <input type="text" class="btn-url w-full border p-2 rounded" placeholder="https://..." value="${data.url}">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-500 mb-1">Styl (Wizualizacja)</label>
                    <select class="btn-style w-full border p-2 rounded">
                        <option value="primary" ${data.style === 'primary' ? 'selected' : ''}>Główny Akcent (Pełny kolor)</option>
                        <option value="secondary" ${data.style === 'secondary' ? 'selected' : ''}>Drugorzędny (Outlined)</option>
                    </select>
                </div>
            </div>`;
        }
        else if (type === 'divider') {
            const data = typeof content === 'object' ? content : { height: '8' };
            innerHTML = `
            <div class="flex justify-between items-center"><span class="text-xs font-bold text-gray-400 uppercase">➖ Separator / Odstęp Pionowy</span></div>
            <div class="mt-2 flex items-center gap-4 bg-gray-50 p-3 border rounded">
                <label class="text-sm font-bold text-gray-600">Wielkość Odstępu:</label>
                <select class="div-height border p-1 rounded">
                    <option value="4" ${data.height === '4' ? 'selected' : ''}>Mały (16px)</option>
                    <option value="8" ${data.height === '8' ? 'selected' : ''}>Średni (32px)</option>
                    <option value="16" ${data.height === '16' ? 'selected' : ''}>Duży (64px)</option>
                    <option value="24" ${data.height === '24' ? 'selected' : ''}>Bardzo Duży (96px)</option>
                </select>
            </div>
            `;
        }
        else if (type === 'quote') {
            const data = typeof content === 'object' ? content : { text: '', author: '' };
            innerHTML = `
            <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-yellow-500 uppercase">❝ Cytat / Referencje</span></div>
            <div class="border-l-4 border-yellow-500 bg-yellow-50 p-4 rounded-r-lg">
                <textarea class="quote-text w-full bg-transparent border-none font-serif italic text-lg resize-none focus:outline-none" placeholder="Tutaj wpisz treść cytatu lub referencji..." rows="3">${data.text}</textarea>
                <div class="flex items-center gap-2 mt-2 border-t border-yellow-200 pt-2">
                    <span class="text-yellow-600 font-bold">—</span>
                    <input type="text" class="quote-author w-full bg-transparent border-none font-bold text-sm text-gray-700 focus:outline-none" placeholder="Imię i Nazwisko / Autor" value="${data.author}">
                </div>
            </div>`;
        }
        else if (type === 'accordion') {
            div.className += " border-2 border-dashed border-purple-300 bg-purple-50/30";
            const data = typeof content === 'object' ? content : { title: '' };
            innerHTML = `
            <div class="flex gap-2 mb-2"><span class="text-xs font-bold text-purple-600 uppercase">⇕ Rozwijany Akordeon (FAQ)</span></div>
            <div class="bg-white border rounded shadow-sm">
                <div class="border-b p-3 bg-purple-100 flex items-center gap-3">
                    <span class="text-purple-600 font-bold text-xl">Q:</span>
                    <input type="text" class="acc-title w-full bg-transparent border-none font-bold text-lg focus:outline-none" placeholder="Pytanie / Tytuł nagłówka" value="${data.title}">
                </div>
                <div class="p-4 bg-white">
                    <span class="text-xs font-bold text-gray-400 block mb-2">Zagnieżdżona Treść (Przeciągnij tu Bloki):</span>
                    <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded min-h-[100px] p-4" onclick="setActive(this, event)"></div>
                </div>
            </div>`;
            setTimeout(() => { initSortable(div.querySelector('.tab-content')); }, 0);
        }
        // Fallback or missed definitions handled here
        else {
            innerHTML = `<div class="p-4 bg-gray-200 text-center">Brak definicji bloku: ${type}</div>`;
        }

        div.innerHTML = controls + innerHTML;
        
        // Restore dropdown values
        if(type === 'form' || type === 'gallery') {
            const sel = div.querySelector('select');
            if(sel) sel.value = content || '';
        }

        return div;
    }

    // Dodawanie elementów (np. kafelka karuzeli)
    function addCarouselTab(btn) {
        const container = btn.previousElementSibling;
        const div = document.createElement('div');
        div.className = "carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded shadow-sm relative group";
        div.innerHTML = `
            <button type="button" onclick="this.closest('.carousel-tab').remove()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10 shadow">X</button>
            <div class="flex gap-2 mb-2 w-full">
                <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/URL')}</div>
                <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł Zakładki">
            </div>
            <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
        `;
        container.appendChild(div);
        initSortable(div.querySelector('.tab-content'));
    }

    // 4. HELPER FUNCTIONS
    function setActive(el, e) {
        activeContainer = el;
        highlightContainer(el);
        e.stopPropagation();
    }

    function buildOptions(list, selected) {
        let html = '<option value="">-- Wybierz z listy --</option>';
        list.forEach(i => html += `<option value="${i.id}" ${i.id==selected?'selected':''}>${i.title}</option>`);
        return html;
    }

    // Dodawanie nowej karty z obrazkiem
    function addImageCard(btn) {
        const container = btn.previousElementSibling;
        const div = document.createElement('div');
        div.className = "image-card border border-gray-300 bg-white p-3 mb-2 rounded shadow-sm relative group grid grid-cols-2 gap-2";
        div.innerHTML = `
            <button type="button" onclick="this.closest('.image-card').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10" title="Ustawienia kafelka">⚙️</button>
            <button type="button" onclick="this.closest('.image-card').remove()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">X</button>
            <div class="col-span-2">${imgInputTpl('card-img', 'URL Zdjęcia')}</div>
            <input type="text" class="card-title w-full border p-2 text-sm rounded font-bold text-orange-600" placeholder="Tytuł (np. Agenda)">
            <input type="text" class="card-subtitle w-full border p-2 text-sm rounded" placeholder="Podtytuł (np. Harmonogram)">
            <div class="col-span-2"><input type="text" class="card-link w-full border p-2 text-sm rounded" placeholder="Link docelowy"></div>
            ${itemSettingsTpl()}
        `;
        container.appendChild(div);
    }

    // Generator struktury HTML dla małego panelu ustawień (ID, CSS) wew. zagnieżdżonych elementów
    function itemSettingsTpl(settings = {}) {
        return `
        <div class="item-settings-panel hidden bg-gray-50 p-2 mt-2 mb-2 border border-gray-200 rounded text-xs shadow-inner col-span-full w-full">
            <div class="grid grid-cols-3 gap-2">
                <div><label class="block font-bold text-gray-400 mb-1">ID elementu</label><input type="text" class="set-id w-full border p-1 rounded" value="${settings.id || ''}"></div>
                <div><label class="block font-bold text-gray-400 mb-1">Klasy CSS</label><input type="text" class="set-css w-full border p-1 rounded" value="${settings.css || ''}"></div>
                <div><label class="block font-bold text-gray-400 mb-1">Style wew.</label><input type="text" class="set-style w-full border p-1 rounded" value="${settings.style || ''}"></div>
            </div>
        </div>`;
    }

    // 5. SAVING LOGIC (Z AKTUALIZACJĄ NOWYCH BLOKÓW)
    function getBlocksFromContainer(container) {
        const blocks = [];
        Array.from(container.children).forEach(el => {
            if(!el.dataset.type) return;
            const type = el.dataset.type;
            let content = '';
            let children = {};

            // Zbieranie ustawień
            const settingsPanel = el.querySelector('.block-settings-panel');
            let settings = {};
            if (settingsPanel) {
                settings = {
                    id: settingsPanel.querySelector('.set-id').value,
                    css: settingsPanel.querySelector('.set-css').value,
                    style: settingsPanel.querySelector('.set-style').value
                };
            }

            // Zawartość zależna od typu bloku
            if(type === 'columns_2') {
                children = {
                    left: getBlocksFromContainer(el.querySelector('.col-left')),
                    right: getBlocksFromContainer(el.querySelector('.col-right'))
                };
            } else if(type === 'columns_3') {
                children = {
                    left: getBlocksFromContainer(el.querySelector('.col-left')),
                    center: getBlocksFromContainer(el.querySelector('.col-center')),
                    right: getBlocksFromContainer(el.querySelector('.col-right'))
                };
            } else if (type === 'accordion') {
                content = { title: el.querySelector('.acc-title').value };
                children = getBlocksFromContainer(el.querySelector('.tab-content'));
            } else if (type === 'text') {
                const editorDiv = el.querySelector('.quill-editor');
                if(editorDiv && quillRegistry[editorDiv.id]) {
                    content = quillRegistry[editorDiv.id].root.innerHTML;
                }
            } else if (type === 'raw_html') {
                content = el.querySelector('textarea').value;
            } else if (type === 'image') {
                content = el.querySelector('.block-content').value;
            } else if (type === 'form' || type === 'gallery') {
                content = el.querySelector('select').value;
            } else if (type === 'linked_image') {
                content = {
                    url: el.querySelector('.block-img-url').value,
                    link: el.querySelector('.block-link-url').value
                };
            } else if (type === 'banner') {
                content = {
                    bg: el.querySelector('.ban-bg').value,
                    title: el.querySelector('.ban-title').value,
                    subtitle: el.querySelector('.ban-subtitle').value
                };
            } else if (type === 'carousel') {
                const tabs = [];
                el.querySelectorAll('.carousel-tab').forEach(tabEl => {
                    tabs.push({
                        icon: tabEl.querySelector('.tab-icon').value,
                        label: tabEl.querySelector('.tab-label').value,
                        children: getBlocksFromContainer(tabEl.querySelector('.tab-content'))
                    });
                });
                content = { arrows: el.querySelector('.carousel-arrows').checked, tabs: tabs };
            }
            else if (type === 'map') {
                content = {
                    lat: el.querySelector('.map-lat').value,
                    lng: el.querySelector('.map-lng').value,
                    zoom: el.querySelector('.map-zoom').value,
                    tooltip: el.querySelector('.map-tooltip').value
                };
            } else if (type === 'countdown') {
                content = {
                    title: el.querySelector('.cd-title').value,
                    date: el.querySelector('.cd-date').value
                };
            } else if (type === 'table') {
                // Zawsze pobieramy z textarea, bo wizualny edytor uaktualnia ją w czasie rzeczywistym
                content = el.querySelector('.table-data').value;
            } else if (type === 'flight') {
                content = {
                    html: el.querySelector('.flight-html').value,
                    state: JSON.parse(decodeURIComponent(el.querySelector('.flight-state').value || '%5B%5D'))
                };
            }
            // -- LOGIKA ZAPISU: SIATKA KART --
            else if (type === 'image_cards') {
                const cards = [];
                el.querySelectorAll('.image-card').forEach(cardEl => {
                    const panel = cardEl.querySelector('.item-settings-panel');
                    cards.push({
                        img: cardEl.querySelector('.card-img').value,
                        title: cardEl.querySelector('.card-title').value,
                        subtitle: cardEl.querySelector('.card-subtitle').value,
                        link: cardEl.querySelector('.card-link').value,
                        settings: panel ? {
                            id: panel.querySelector('.set-id').value,
                            css: panel.querySelector('.set-css').value,
                            style: panel.querySelector('.set-style').value
                        } : {}
                    });
                });
                content = { cards: cards };
            }
            // -- NOWE BLOKI --
            else if (type === 'video') {
                content = el.querySelector('.block-video').value;
            } else if (type === 'button') {
                content = {
                    label: el.querySelector('.btn-label').value,
                    url: el.querySelector('.btn-url').value,
                    style: el.querySelector('.btn-style').value
                };
            } else if (type === 'divider') {
                content = { height: el.querySelector('.div-height').value };
            } else if (type === 'quote') {
                content = {
                    text: el.querySelector('.quote-text').value,
                    author: el.querySelector('.quote-author').value
                };
            }

            blocks.push({ type, content, children, settings });
        });
        return blocks;
    }

    // --- WSPÓLNE DLA HYBRYDOWYCH EDYTORÓW ---
    function toggleEditor(btn, mode) {
        const block = btn.closest('.block-item');
        const visual = block.querySelector('.editor-visual');
        const raw = block.querySelector('.editor-raw');
        
        // Reset styles for tabs
        btn.parentElement.querySelectorAll('button').forEach(b => {
            b.className = 'bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded font-bold';
        });
        btn.className = 'bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded font-bold';

        if (mode === 'visual') {
            raw.classList.add('hidden');
            visual.classList.remove('hidden');
        } else {
            visual.classList.add('hidden');
            raw.classList.remove('hidden');
        }
    }

    // ==========================================
    // --- LOGIKA TABEL (Naprawa usuwania kolumn)
    // ==========================================
    function syncTableRawToVisual(block) {
        const raw = block.querySelector('.table-data').value;
        const tbody = block.querySelector('.visual-table-body');
        tbody.innerHTML = '';
        
        const rows = raw.split('\n');
        if (rows.length === 0 || (rows.length === 1 && rows[0].trim() === '')) return;
        
        const colsCount = rows[0].split('\t').length;
        
        // NOWOŚĆ: Wiersz kontrolny do usuwania kolumn
        const ctrlTr = document.createElement('tr');
        for (let i = 0; i < colsCount; i++) {
            ctrlTr.innerHTML += `<th class="bg-gray-100 border p-1 text-center"><button type="button" onclick="removeTableCol(this, ${i})" class="text-red-400 hover:text-red-600 text-[10px] font-bold">Usuń kol.</button></th>`;
        }
        ctrlTr.innerHTML += `<th class="bg-gray-100 border-0 w-8"></th>`;
        tbody.appendChild(ctrlTr);

        rows.forEach((r, rowIdx) => {
            const tr = document.createElement('tr');
            tr.className = rowIdx === 0 ? "bg-blue-50 font-bold" : "";
            const cells = r.split('\t');
            for (let i = 0; i < colsCount; i++) {
                let c = cells[i] !== undefined ? cells[i] : '';
                tr.innerHTML += `<td class="border p-0"><input type="text" class="w-full text-sm outline-none px-2 py-1 bg-transparent" value="${c.replace(/"/g, '&quot;')}" oninput="syncTableVisualToRaw(this)"></td>`;
            }
            tr.innerHTML += `<td class="border-0 w-8 text-center"><button type="button" onclick="removeTableRow(this)" class="text-red-400 hover:text-red-600 text-xs font-bold" title="Usuń wiersz">X</button></td>`;
            tbody.appendChild(tr);
        });
    }

    function syncTableVisualToRaw(element) {
        const block = element.closest('.block-item') || element;
        const tbody = block.querySelector('.visual-table-body');
        const textarea = block.querySelector('.table-data');
        
        let tsv = [];
        // Pomijamy pierwszy wiersz (kontrolny z przyciskami 'Usuń kol.')
        const dataRows = Array.from(tbody.querySelectorAll('tr')).slice(1);
        dataRows.forEach(tr => {
            let row = [];
            tr.querySelectorAll('input').forEach(inp => row.push(inp.value));
            tsv.push(row.join('\t'));
        });
        textarea.value = tsv.join('\n');
    }

    function addTableRow(btn) {
        const block = btn.closest('.block-item');
        const textarea = block.querySelector('.table-data');
        const cols = textarea.value.split('\n')[0].split('\t').length || 1;
        const newRow = new Array(cols).fill('-').join('\t');
        textarea.value += (textarea.value ? '\n' : '') + newRow;
        syncTableRawToVisual(block);
    }

    function addTableCol(btn) {
        const block = btn.closest('.block-item');
        const textarea = block.querySelector('.table-data');
        let rows = textarea.value.split('\n');
        rows = rows.map((r, i) => r + (i === 0 ? '\tNagłówek' : '\t-'));
        textarea.value = rows.join('\n');
        syncTableRawToVisual(block);
    }

    function removeTableRow(btn) {
        const block = btn.closest('.block-item');
        btn.closest('tr').remove();
        syncTableVisualToRaw(block);
    }

    // NOWOŚĆ: Funkcja usuwania kolumny
    function removeTableCol(btn, colIdx) {
        const block = btn.closest('.block-item');
        const textarea = block.querySelector('.table-data');
        let rows = textarea.value.split('\n');
        rows = rows.map(r => {
            let cells = r.split('\t');
            cells.splice(colIdx, 1);
            return cells.join('\t');
        });
        textarea.value = rows.join('\n');
        syncTableRawToVisual(block);
    }

    // ==========================================
    // --- LOGIKA PRZELOTÓW (Naprawa usuwania i zdjęć)
    // ==========================================
    
    // NOWOŚĆ: Funkcja do bezpiecznego usuwania bez gubienia referencji DOM
    function removeFlightSegment(btn) {
        const block = btn.closest('.block-item'); // Najpierw pobierz referencję do rodzica
        btn.closest('.flight-segment').remove();  // Następnie usuń sam segment
        syncFlightVisualToRaw(block);             // Odśwież kod HTML
    }

    // Pozostałe, powiązane z tym przyciskiem skrypty edytora
    function initFlightVisual(block) {
        const stateStr = block.querySelector('.flight-state').value;
        const state = JSON.parse(decodeURIComponent(stateStr || '%5B%5D'));
        const container = block.querySelector('.flight-segments-container');
        container.innerHTML = '';
        
        if (state.length === 0) {
            addFlightSegmentHTML(container, 'flight', {});
        } else {
            state.forEach(seg => addFlightSegmentHTML(container, seg.type, seg));
            const footerState = state.find(s => s.type === 'footer');
            if (footerState) {
                block.querySelector('.flight-f-arr').value = footerState.arrival || '';
                block.querySelector('.flight-f-time').value = footerState.duration || '';
            }
        }
    }

    function addFlightSegment(btn, type) {
        const container = btn.closest('.editor-visual').querySelector('.flight-segments-container');
        addFlightSegmentHTML(container, type, {});
        syncFlightVisualToRaw(btn.closest('.block-item'));
    }

    function addFlightSegmentHTML(container, type, data) {
        const div = document.createElement('div');
        div.className = "flight-segment relative bg-white border rounded p-3 shadow-sm";
        div.dataset.type = type;
        
        if (type === 'flight') {
            div.innerHTML = `
                <button type="button" onclick="removeFlightSegment(this)" class="absolute top-2 right-2 text-red-500 font-bold text-xs bg-red-50 px-2 py-1 rounded hover:bg-red-100 z-10">X Usuń Lot</button>
                <div class="grid grid-cols-2 gap-3 mb-2 pr-20">
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold block">Linia Lotnicza</label>
                        <input type="text" class="f-airline w-full border-b p-1 text-xs outline-none" placeholder="LOT LO601" value="${data.airline || ''}">
                    </div>
                    <div>
                        <label class="text-[10px] uppercase text-gray-500 font-bold block mb-1">Logotyp (Media)</label>
                        ${imgInputTpl('f-logo', 'URL Logotypu', data.logo || '')} </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-1 border-r pr-2">
                        <label class="text-[10px] uppercase text-gray-500 font-bold block">Czas trwania (PL)</label>
                        <input type="text" class="f-dur-pl w-full border-b p-1 text-xs outline-none mb-1" placeholder="2 godz. 40 min" value="${data.durPl || ''}">
                        <label class="text-[10px] uppercase text-gray-500 font-bold block mt-2">Czas trwania (EN)</label>
                        <input type="text" class="f-dur-en w-full border-b p-1 text-xs outline-none" placeholder="2 hr 40 min" value="${data.durEn || ''}">
                    </div>
                    <div class="col-span-2 grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[10px] uppercase text-gray-500 font-bold block text-blue-600">Start (Godzina i Miejsce)</label>
                            <input type="text" class="f-dep-pl w-full border-b p-1 text-xs outline-none mb-1" placeholder="09:20 WAW Chopin" value="${data.depPl || ''}">
                            <input type="text" class="f-dep-en w-full border-b p-1 text-xs outline-none text-gray-400" placeholder="EN: 09:20 WAW Chopin" value="${data.depEn || ''}">
                        </div>
                        <div>
                            <label class="text-[10px] uppercase text-gray-500 font-bold block text-green-600">Lądowanie (Godzina i Miejsce)</label>
                            <input type="text" class="f-arr-pl w-full border-b p-1 text-xs outline-none mb-1" placeholder="13:00 ATH Ateny" value="${data.arrPl || ''}">
                            <input type="text" class="f-arr-en w-full border-b p-1 text-xs outline-none text-gray-400" placeholder="EN: 13:00 ATH Athens" value="${data.arrEn || ''}">
                        </div>
                    </div>
                </div>`;
        } else if (type === 'connection') {
            div.className = "flight-segment relative bg-indigo-50 border border-indigo-200 rounded p-2 text-center";
            div.innerHTML = `
                <button type="button" onclick="removeFlightSegment(this)" class="absolute top-1 right-1 text-red-500 font-bold text-xs hover:text-red-700 bg-white rounded-full w-5 h-5 z-10">X</button>
                <input type="text" class="f-conn-pl w-full bg-transparent border-b border-indigo-300 p-1 text-xs text-center outline-none font-bold text-indigo-800 mb-1 relative z-0" placeholder="1 godz. 55 min Przesiadka" value="${data.connPl || ''}">
                <input type="text" class="f-conn-en w-full bg-transparent border-b border-indigo-300 p-1 text-xs text-center outline-none text-indigo-500 relative z-0" placeholder="EN: 1 hr 55 min Connection" value="${data.connEn || ''}">`;
        }
        
        div.querySelectorAll('input').forEach(inp => {
            inp.addEventListener('input', () => syncFlightVisualToRaw(inp.closest('.block-item')));
            inp.addEventListener('change', () => syncFlightVisualToRaw(inp.closest('.block-item'))); 
        });
        
        container.appendChild(div);
    }

    function syncFlightVisualToRaw(block) {
        let html = '<div class="przelot-widget">\n';
        let state = [];

        block.querySelectorAll('.flight-segment').forEach(seg => {
            const type = seg.dataset.type;
            
            if (type === 'flight') {
                const s = {
                    type: 'flight',
                    airline: seg.querySelector('.f-airline').value,
                    logo: seg.querySelector('.f-logo').value,
                    durPl: seg.querySelector('.f-dur-pl').value,
                    durEn: seg.querySelector('.f-dur-en').value,
                    depPl: seg.querySelector('.f-dep-pl').value,
                    depEn: seg.querySelector('.f-dep-en').value,
                    arrPl: seg.querySelector('.f-arr-pl').value,
                    arrEn: seg.querySelector('.f-arr-en').value,
                };
                state.push(s);
                
                // Formatowanie czasu trwania z łamaniem linii
                const durPlFormatted = s.durPl ? s.durPl.replace(/(godz\.|hr|min)\s+/g, '$1<br>') : '-';
                const durEnFormatted = s.durEn ? s.durEn.replace(/(godz\.|hr|min)\s+/g, '$1<br>') : '';
                
                // Warunkowe renderowanie drugiego języka
                const enDur = durEnFormatted ? `<span class="en-translation">${durEnFormatted}</span>` : '';
                const enDep = s.depEn ? `<span class="en-translation">${s.depEn}</span>` : '';
                const enArr = s.arrEn ? `<span class="en-translation">${s.arrEn}</span>` : '';
                
                html += `
<div class="header">
    <img class="logo" src="${s.logo || 'https://via.placeholder.com/100x30?text=LOGO'}">
    <span>${s.airline || 'Linia Lotnicza'}</span>
</div>
<div class="body">
    <div class="duration">${durPlFormatted} ${enDur}</div>
    <div class="graphic"><div class="circle"></div><div class="line"></div><div class="circle"></div></div>
    <div class="stops">
        <div>${s.depPl || '-'} ${enDep}</div>
        <div style="height:85px;"></div>
        <div>${s.arrPl || '-'} ${enArr}</div>
    </div>
</div>\n`;

            } else if (type === 'connection') {
                const s = {
                    type: 'connection',
                    connPl: seg.querySelector('.f-conn-pl').value,
                    connEn: seg.querySelector('.f-conn-en').value
                };
                state.push(s);
                
                // Warunkowe renderowanie drugiego języka dla przesiadki
                const enConn = s.connEn ? `<span class="en-translation">${s.connEn}</span>` : '';
                
                html += `
<div class="infobubble">
    ${s.connPl || '-'}
    ${enConn}
</div>\n`;
            }
        });

        const fArr = block.querySelector('.flight-f-arr').value;
        const fTime = block.querySelector('.flight-f-time').value;
        state.push({ type: 'footer', arrival: fArr, duration: fTime });
        
        html += `
<div class="footer">
    <span style="margin-right:12px;"><b>${fArr || 'Przylot: ---'}</b></span>
    <span><b>${fTime || 'Czas trwania: ---'}</b></span>
</div>
</div>`;

        block.querySelector('.flight-html').value = html;
        block.querySelector('.flight-state').value = encodeURIComponent(JSON.stringify(state));
    }

    function savePage() {
        const rootData = getBlocksFromContainer(document.getElementById('editor'));
        
        // Animacja na przycisku
        const btn = document.querySelector('button[onclick="savePage()"]');
        const oldText = btn.innerText;
        btn.innerText = "Zapisywanie...";
        
        fetch('/admin/pages/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id: pageId,
                title: document.getElementById('pageTitle').value,
                slug: document.getElementById('pageSlug').value,
                content: rootData
            })
        })
        .then(res => res.json())
        .then(d => {
            btn.innerText = d.status === 'success' ? 'Zapisano!' : 'Błąd zapisu';
            setTimeout(() => btn.innerText = oldText, 2000);
        });
    }

    // --- UPLOAD ZDJĘĆ Z KOMPUTERA (W BLOKU IMAGE) ---
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.style.display='none';
    document.body.appendChild(fileInput);
    let activeUploadEl = null;

    fileInput.addEventListener('change', function() {
        if(!this.files[0]) return;
        const fd = new FormData();
        fd.append('file', this.files[0]);
        
        fetch('/admin/media/upload', {method:'POST', body:fd})
        .then(r=>r.json()).then(d => {
            if(d.url && activeUploadEl) {
                activeUploadEl.innerHTML = `<img src="${d.url}" class="w-full rounded shadow"><input type="hidden" class="block-content" value="${d.url}">`;
                activeUploadEl.className = "relative group-hover:border-blue-500 transition";
            }
        });
    });

    function triggerUpload(el) {
        activeUploadEl = el;
        fileInput.click();
    }

    // --- HELPER DO INPUTÓW Z MEDIA PICKEREM (np. Baner, Ikony) ---
    function triggerInputUpload(btn) {
        const input = btn.previousElementSibling.previousElementSibling; // Dopasowanie pod strukturę (input <- btnPicker <- btnUpload)
        const fileInputTemp = document.createElement('input');
        fileInputTemp.type = 'file';
        fileInputTemp.accept = 'image/*';
        fileInputTemp.onchange = e => { if(e.target.files[0]) handleInputUpload(input, e.target.files[0]); };
        fileInputTemp.click();
    }

    function handleInputUpload(inputEl, file) {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('shortcut', '1'); 
        
        inputEl.value = 'Wgrywanie...';
        inputEl.disabled = true;
        fetch('/admin/media/upload', {method:'POST', body:fd})
        .then(r=>r.json()).then(d => {
            inputEl.value = d.url || '';
            inputEl.disabled = false;
        }).catch(() => {
            inputEl.value = '';
            inputEl.disabled = false;
        });
    }

    function imgInputTpl(className, placeholder, value = '') {
        const uniqueId = 'img_in_' + Math.random().toString(36).substr(2, 9);
        return `
        <div class="flex w-full mb-1">
            <input type="text" id="${uniqueId}" class="${className} w-full border border-r-0 p-2 text-sm rounded-l bg-gray-50 focus:bg-white focus:outline-none" placeholder="${placeholder}" value="${value}">
            <button type="button" onclick="openMediaPicker('${uniqueId}')" class="bg-purple-100 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition" title="Wybierz z Media">📂</button>
            <button type="button" onclick="triggerInputUpload(this)" class="bg-blue-100 hover:bg-blue-200 border border-blue-200 text-blue-700 px-3 rounded-r text-sm font-bold transition" title="Wgraj Plik">⬆️</button>
        </div>`;
    }

    let activePickerInputId = null;
    function openMediaPicker(inputId) {
        activePickerInputId = inputId;
        document.getElementById('mediaPickerModal').classList.remove('hidden');
        document.getElementById('mediaPickerFrame').src = '/admin/media?picker=1';
    }

    function closeMediaPicker() {
        document.getElementById('mediaPickerModal').classList.add('hidden');
        document.getElementById('mediaPickerFrame').src = '';
    }

    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'media_selected') {
            if (activePickerInputId) {
                document.getElementById(activePickerInputId).value = event.data.url;
            }
            closeMediaPicker();
        }
    });
</script>
</body>
</html>