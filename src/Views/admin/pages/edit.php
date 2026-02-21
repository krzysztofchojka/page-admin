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
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col">

    <div class="bg-white shadow p-4 flex justify-between items-center z-50 sticky top-0">
        <div class="flex items-center gap-4">
            <a href="/admin/pages" class="text-gray-500 hover:text-black">← Back</a>
            <input type="text" id="pageTitle" value="<?= htmlspecialchars($page['title']) ?>" class="text-xl font-bold border-b border-transparent hover:border-gray-300 focus:outline-none px-2" placeholder="Page Title" />
            <input type="text" id="pageSlug" value="<?= htmlspecialchars($page['slug'] ?? '') ?>" class="bg-gray-50 border-none text-sm px-2 py-1 rounded w-32" placeholder="slug" />
        </div>
        <div class="flex gap-2 items-center">
    <select id="blockSelector" class="bg-white border hover:bg-gray-50 px-2 py-1 text-sm font-bold text-gray-700 rounded shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="text">T Text</option>
        <option value="image">🖼 Image</option>
        <option value="linked_image">🔗 Linked Image</option>
        <option value="carousel">🎠 Kafelki/Karuzela</option>
        <option value="form">📝 Form</option>
        <option value="gallery">📷 Gallery</option>
        <option value="raw_html">&lt;/&gt; HTML</option>
        <option value="columns_2">◫ 2 Cols</option>
        <option value="columns_3">☰ 3 Cols</option>
        <option value="image_cards">🗂 Siatka Kafelków (Z obrazkiem)</option>
        <option value="banner">🏔 Baner (Hero)</option>
    </select>
    <button onclick="addBlockToContainer(document.getElementById('blockSelector').value)" class="bg-gray-800 hover:bg-gray-900 text-white px-3 py-1 text-sm font-bold rounded shadow transition">
        + Dodaj Blok
    </button>
    <button onclick="savePage()" class="ml-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded shadow font-bold">Save</button>
</div>
    </div>

    <div class="flex-1 overflow-auto p-8">
        <div id="editor" class="drop-zone max-w-6xl mx-auto min-h-[600px] bg-white shadow-xl rounded-lg p-8 grid grid-cols-1 gap-6">
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
        // Key: DOM Element ID, Value: Quill Instance
        const quillRegistry = {};

        // 2. INITIALIZATION
        let activeContainer = document.getElementById('editor');

        // Initialize the main editor
        initSortable(activeContainer);

        // Load Saved Data
        renderRecursive(savedContent, activeContainer);

        // 3. CORE FUNCTIONS

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

        function addCarouselTab(btn) {
    const container = btn.previousElementSibling;
    const div = document.createElement('div');
    div.className = "carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded shadow-sm relative group";
    div.innerHTML = `
    <button type="button" onclick="this.closest('.carousel-tab').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">⚙️</button>
    <button type="button" onclick="this.closest('.carousel-tab').remove()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">X</button>
    <div class="flex gap-2 mb-2 w-full">
        <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/Emoji/URL')}</div>
        <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł Kafelka">
    </div>
    ${itemSettingsTpl()}
    <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-300 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
`;
    container.appendChild(div);
    // Ważne: Aktywuj drag&drop dla nowej zakładki
    initSortable(div.querySelector('.tab-content'));
}

        function initSortable(el) {
            Sortable.create(el, {
                group: 'shared', 
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'ghost',
                fallbackOnBody: true,
                swapThreshold: 0.65,
                // Disable sorting on text areas so we can select text
                filter: '.no-drag', 
                preventOnFilter: false
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
                const el = renderBlock(block.type, block.content);
                container.appendChild(el);
                
                // Initialize Quill if it's a text block
                if (block.type === 'text') {
                    initQuill(el.querySelector('.quill-editor'), block.content);
                }

                // If it's a layout block, render children
                if (block.type === 'columns_2' && block.children) {
                    const leftCol = el.querySelector('.col-left');
                    const rightCol = el.querySelector('.col-right');
                    
                    if(block.children.left) renderRecursive(block.children.left, leftCol);
                    if(block.children.right) renderRecursive(block.children.right, rightCol);
                }
                if (block.type === 'columns_3' && block.children) {
                    renderRecursive(block.children.left, el.querySelector('.col-left'));
                    renderRecursive(block.children.center, el.querySelector('.col-center'));
                    renderRecursive(block.children.right, el.querySelector('.col-right'));
                }
                if (block.type === 'carousel' && block.content && block.content.tabs) {
    const tabZones = el.querySelectorAll('.tab-content');
    block.content.tabs.forEach((tab, idx) => {
        if (tab.children && tabZones[idx]) {
            renderRecursive(tab.children, tabZones[idx]);
        } else if (tab.content && typeof tab.content === 'string' && tabZones[idx]) {
            // Bezpiecznik: jeśli masz w bazie stare karuzele tekstowe
            tabZones[idx].innerHTML = `<div class="p-2 bg-yellow-100 text-xs">Skopiuj ten stary tekst, usuń go stąd i dodaj Blok Tekstowy: <br> ${tab.content}</div>`;
        }
    });
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
            
            // Set initial content
            if(content) quill.root.innerHTML = content;
            
            // Register instance
            quillRegistry[id] = quill;
        }

        // Block Factory
        function renderBlock(type, content = '') {
            const div = document.createElement('div');
            div.className = "group relative border border-gray-200 hover:border-blue-400 rounded p-4 mb-4 bg-gray-50 transition-all";
            div.dataset.type = type;

            const blockSettings = typeof block !== 'undefined' && block.settings ? block.settings : { id: '', css: '', style: '' };

const controls = `
<div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-50">
    <button onclick="this.parentElement.nextElementSibling.classList.toggle('hidden')" class="text-gray-500 hover:text-gray-800 p-1 bg-white rounded shadow" title="Ustawienia Bloku">⚙️</button>
    <span class="drag-handle text-gray-500 hover:text-blue-600 p-1 bg-white rounded shadow cursor-move" title="Przeciągnij">✥</span>
    <button onclick="if(confirm('Delete?')) this.closest('.group').remove()" class="text-red-400 hover:text-red-600 p-1 bg-white rounded shadow" title="Usuń">🗑</button>
</div>
<div class="block-settings-panel hidden bg-gray-100 p-3 mt-8 mb-4 border border-gray-300 rounded text-sm shadow-inner">
    <div class="grid grid-cols-3 gap-4">
        <div>
            <label class="block font-bold text-gray-600 mb-1">ID (np. do kotwic)</label>
            <input type="text" class="set-id w-full border p-1 rounded" placeholder="np. sekcja-kontakt" value="${blockSettings.id || ''}">
        </div>
        <div>
            <label class="block font-bold text-gray-600 mb-1">Klasy CSS (Tailwind)</label>
            <input type="text" class="set-css w-full border p-1 rounded" placeholder="np. mt-10 bg-gray-50 p-4" value="${blockSettings.css || ''}">
        </div>
        <div>
            <label class="block font-bold text-gray-600 mb-1">Style Inline (CSS)</label>
            <input type="text" class="set-style w-full border p-1 rounded" placeholder="np. color: red;" value="${blockSettings.style || ''}">
        </div>
    </div>
</div>
`;
            
            let innerHTML = '';

            // --- 1. QUILL TEXT ---
            if (type === 'text') {
                innerHTML = `
                    <div class="bg-white rounded shadow-sm no-drag">
                        <div class="quill-editor"></div>
                    </div>`;
            }

            // --- 2. RAW HTML ---
            else if (type === 'raw_html') {
                innerHTML = `
                    <div class="bg-gray-900 rounded p-2 no-drag border border-gray-700">
                        <div class="flex justify-between items-center mb-1">
                            <label class="text-xs font-mono text-gray-400">&lt;/&gt; RAW HTML</label>
                            <span class="text-xs text-gray-600">Be careful with syntax</span>
                        </div>
                        <textarea class="w-full h-32 bg-gray-800 text-green-400 font-mono text-sm p-2 rounded focus:outline-none focus:ring-1 focus:ring-green-500 block-content placeholder-gray-600" placeholder="<div>Your HTML here...</div>">${content}</textarea>
                    </div>`;
            }

            // --- 3. 2 COLUMNS ---
            else if (type === 'columns_2') {
                div.className = "group relative border-2 border-dashed border-indigo-200 bg-indigo-50/30 rounded p-4 mb-4";
                innerHTML = `
                    <div class="flex gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">2 Column Layout</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="col-left drop-zone bg-white/50 border border-indigo-100 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
                        <div class="col-right drop-zone bg-white/50 border border-indigo-100 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
                    </div>
                `;
                setTimeout(() => {
                    initSortable(div.querySelector('.col-left'));
                    initSortable(div.querySelector('.col-right'));
                }, 0);
            }
            // --- 3.5. 3 COLUMNS ---
            else if (type === 'columns_3') {
                div.className = "group relative border-2 border-dashed border-emerald-200 bg-emerald-50/30 rounded p-4 mb-4";
                innerHTML = `
                    <div class="flex gap-2 mb-2">
                        <span class="text-xs font-bold text-emerald-500 uppercase tracking-wider">3 Column Layout</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="col-left drop-zone bg-white/50 border border-emerald-100 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
                        <div class="col-center drop-zone bg-white/50 border border-emerald-100 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
                        <div class="col-right drop-zone bg-white/50 border border-emerald-100 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
                    </div>
                `;
                setTimeout(() => {
                    initSortable(div.querySelector('.col-left'));
                    initSortable(div.querySelector('.col-center'));
                    initSortable(div.querySelector('.col-right'));
                }, 0);
            }
            // --- 4.5. LINKED IMAGE ---
            else if (type === 'linked_image') {
                const data = typeof content === 'object' ? content : { url: '', link: '' };
                innerHTML = `
                <div class="bg-white border rounded p-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold text-indigo-600 uppercase">🔗 Linked Image</span>
                    </div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Adres URL Zdjęcia (lub wrzuć do Media i wklej link)</label>
                    <input type="text" class="block-img-url w-full border p-2 text-sm mb-3 rounded" placeholder="https://..." value="${data.url || ''}">
                    
                    <label class="block text-xs font-bold text-gray-500 mb-1">Link docelowy (po kliknięciu)</label>
                    <input type="text" class="block-link-url w-full border p-2 text-sm rounded" placeholder="https://..." value="${data.link || ''}">
                </div>`;
            }

            else if (type === 'carousel') {
    div.className = "group relative border-2 border-dashed border-blue-300 bg-blue-50 rounded p-4 mb-4";
    const data = typeof content === 'object' ? content : { arrows: true, tabs: [] };
    
    let tabsHTML = '';
    if(data.tabs) {
        data.tabs.forEach((tab) => {
            tabsHTML += `
<div class="carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded shadow-sm relative group">
    <button type="button" onclick="this.closest('.carousel-tab').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">⚙️</button>
    <button type="button" onclick="this.closest('.carousel-tab').remove()" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">X</button>
    <div class="flex gap-2 mb-2 w-full">
        <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/Emoji/URL', tab.icon)}</div>
        <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł Kafelka" value="${tab.label || ''}">
    </div>
    ${itemSettingsTpl(tab.settings)}
    <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-300 rounded p-4 min-h-[100px]" onclick="setActive(this, event)"></div>
</div>`;
        });
    }

    innerHTML = `
    <div class="flex justify-between items-center mb-3">
        <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">🎠 Kafelki Nawigacyjne (Zagnieżdżanie Bloków)</span>
        <label class="text-sm font-bold text-gray-700 flex items-center gap-2">
            <input type="checkbox" class="carousel-arrows" ${data.arrows ? 'checked' : ''}> Pokaż strzałki boczne
        </label>
    </div>
    <div class="carousel-tabs-container min-h-[5px] mb-2">
        ${tabsHTML}
    </div>
    <button type="button" onclick="addCarouselTab(this)" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded shadow font-bold w-full">+ Dodaj Kafelek</button>
    `;

    // Opóźnienie, aby DOM się wyrenderował przed dodaniem SortableJS
    setTimeout(() => {
        div.querySelectorAll('.tab-content').forEach(el => initSortable(el));
    }, 0);
}
            
            // --- 4. IMAGE ---
            else if (type === 'image') {
                const hasImg = content && content.length > 5;
                innerHTML = `
                    <div class="${hasImg ? 'relative' : 'p-8 border-2 border-dashed border-gray-300 text-center cursor-pointer'}" onclick="triggerUpload(this)">
                        ${hasImg ? 
                            `<img src="${content}" class="w-full rounded shadow"><input type="hidden" class="block-content" value="${content}">` : 
                            `<p class="text-gray-400 font-bold pointer-events-none">📂 Upload Image</p><input type="hidden" class="block-content" value="">`
                        }
                    </div>`;
            }

            // --- 5. FORM ---
            else if (type === 'form') {
                innerHTML = `
                    <div class="bg-blue-50 border border-blue-200 p-2 rounded text-center">
                        <label class="block text-xs font-bold text-blue-800 mb-1">Form:</label>
                        <select onchange="this.parentElement.dataset.val = this.value" class="block-select border rounded text-sm p-1 w-full text-center font-bold">
                            ${buildOptions(availableForms, content)}
                        </select>
                    </div>`;
            }

            // --- 6. GALLERY ---
            else if (type === 'gallery') {
                innerHTML = `
                    <div class="bg-purple-50 border border-purple-200 p-2 rounded text-center">
                        <label class="block text-xs font-bold text-purple-800 mb-1">Gallery:</label>
                        <select onchange="this.parentElement.dataset.val = this.value" class="block-select border rounded text-sm p-1 w-full text-center font-bold">
                            ${buildOptions(availableGalleries, content)}
                        </select>
                    </div>`;
            }
            // --- 8. IMAGE CARDS (SIATKA KAFELKÓW) ---
else if (type === 'image_cards') {
    div.className = "group relative border-2 border-dashed border-orange-300 bg-orange-50 rounded p-4 mb-4";
    const data = typeof content === 'object' ? content : { cards: [] };
    
    let cardsHTML = '';
    if (data.cards) {
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
    <div class="flex justify-between items-center mb-3">
        <span class="text-xs font-bold text-orange-800 uppercase tracking-wider">🗂 Siatka Kafelków (Z obrazkiem)</span>
    </div>
    <div class="cards-container min-h-[5px] mb-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        ${cardsHTML}
    </div>
    <button type="button" onclick="addImageCard(this)" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded shadow font-bold w-full">+ Dodaj Kartę</button>
    `;
}
// --- 9. BANNER (HERO) ---
else if (type === 'banner') {
    const data = typeof content === 'object' ? content : { bg: '', title: '', subtitle: '' };
    innerHTML = `
    <div class="bg-white border rounded p-4 border-l-4 border-blue-500">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xs font-bold text-blue-600 uppercase">🏔 Baner Główny</span>
        </div>
        <label class="block text-xs font-bold text-gray-500 mb-1">URL Zdjęcia w tle (Tło całego baneru)</label>
        <input type="text" class="ban-bg w-full border p-2 text-sm mb-3 rounded" placeholder="https://..." value="${data.bg || ''}">
        
        <label class="block text-xs font-bold text-gray-500 mb-1">Tytuł (na półprzezroczystym pasku)</label>
        <input type="text" class="ban-title w-full border p-2 text-sm rounded mb-3 font-bold text-orange-500" placeholder="Np. BestDrive Experience Ateny" value="${data.title || ''}">
        
        <label class="block text-xs font-bold text-gray-500 mb-1">Podtytuł (Tekst pod paskiem)</label>
        <textarea class="ban-subtitle w-full border p-2 text-sm rounded h-16" placeholder="Prosimy o wypełnienie formularza...">${data.subtitle || ''}</textarea>
    </div>`;
}


            div.innerHTML = controls + innerHTML;

            // Restore dropdown values
            if(type === 'form' || type === 'gallery') {
                const sel = div.querySelector('select');
                if(sel) { sel.value = content || ''; }
            }

            return div;
        }

        // 4. HELPER FUNCTIONS

        function addBlockToContainer(type) {
            const target = activeContainer || document.getElementById('editor');
            const el = renderBlock(type);
            target.appendChild(el);
            
            // If new block is text, init Quill
            if(type === 'text') {
                initQuill(el.querySelector('.quill-editor'), '');
            }
        }

        function setActive(el, e) {
            activeContainer = el;
            highlightContainer(el);
            e.stopPropagation();
        }

        function buildOptions(list, selected) {
            let html = '<option value="">-- Select --</option>';
            list.forEach(i => html += `<option value="${i.id}" ${i.id==selected?'selected':''}>${i.title}</option>`);
            return html;
        }

        // 5. SAVING LOGIC (RECURSIVE)
        
        function getBlocksFromContainer(container) {
            const blocks = [];
            Array.from(container.children).forEach(el => {
                if(!el.dataset.type) return;
                
                const type = el.dataset.type;
                let content = '';
                let children = {};

                if(type === 'columns_2') {
                    children = {
                        left: getBlocksFromContainer(el.querySelector('.col-left')),
                        right: getBlocksFromContainer(el.querySelector('.col-right'))
                    };
                } 
                else if(type === 'columns_3') {
                    children = {
                        left: getBlocksFromContainer(el.querySelector('.col-left')),
                        center: getBlocksFromContainer(el.querySelector('.col-center')),
                        right: getBlocksFromContainer(el.querySelector('.col-right'))
                    };
                }
                else if (type === 'text') {
                    // Get Content from Quill Instance
                    const editorDiv = el.querySelector('.quill-editor');
                    const quill = quillRegistry[editorDiv.id];
                    content = quill.root.innerHTML;
                }
                else if (type === 'raw_html') {
                    content = el.querySelector('textarea').value;
                }
                else if (type === 'image') {
                    content = el.querySelector('.block-content').value;
                }
                else if (type === 'form' || type === 'gallery') {
                    content = el.querySelector('select').value;
                }
                else if (type === 'linked_image') {
                    content = {
                        url: el.querySelector('.block-img-url').value,
                        link: el.querySelector('.block-link-url').value
                    };
                }
                else if (type === 'carousel') {
    const tabs = [];
    el.querySelectorAll('.carousel-tab').forEach(tabEl => {
        const panel = tabEl.querySelector('.item-settings-panel');
tabs.push({
    icon: tabEl.querySelector('.tab-icon').value,
    label: tabEl.querySelector('.tab-label').value,
    children: getBlocksFromContainer(tabEl.querySelector('.tab-content')),
    settings: panel ? { id: panel.querySelector('.set-id').value, css: panel.querySelector('.set-css').value, style: panel.querySelector('.set-style').value } : {}
});
    });
    content = {
        arrows: el.querySelector('.carousel-arrows').checked,
        tabs: tabs
    };
}
                else if (type === 'image_cards') {
    const cards = [];
    el.querySelectorAll('.image-card').forEach(cardEl => {
        const panel = cardEl.querySelector('.item-settings-panel');
        cards.push({
            img: cardEl.querySelector('.card-img').value,
            title: cardEl.querySelector('.card-title').value,
            subtitle: cardEl.querySelector('.card-subtitle').value,
            link: cardEl.querySelector('.card-link').value,
            settings: panel ? { id: panel.querySelector('.set-id').value, css: panel.querySelector('.set-css').value, style: panel.querySelector('.set-style').value } : {}
        });
    });
    content = { cards: cards };
}
        else if (type === 'banner') {
            content = {
                bg: el.querySelector('.ban-bg').value,
                title: el.querySelector('.ban-title').value,
                subtitle: el.querySelector('.ban-subtitle').value
            };
        }

        const settingsPanel = el.querySelector('.block-settings-panel');
let settings = {};
if (settingsPanel) {
    settings = {
        id: settingsPanel.querySelector('.set-id').value,
        css: settingsPanel.querySelector('.set-css').value,
        style: settingsPanel.querySelector('.set-style').value
    };
}
blocks.push({ type, content, children, settings });

            });
            return blocks;
        }

        function savePage() {
            const rootData = getBlocksFromContainer(document.getElementById('editor'));
            
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
            .then(d => alert(d.status === 'success' ? 'Saved!' : 'Error'));
        }

        // 6. UPLOAD LOGIC
        const fileInput = document.createElement('input');
        fileInput.type = 'file'; fileInput.style.display='none';
        document.body.appendChild(fileInput);
        let activeUploadEl = null;

        fileInput.addEventListener('change', function() {
            if(!this.files[0]) return;
            const fd = new FormData(); fd.append('file', this.files[0]);
            
            fetch('/admin/media/upload', {method:'POST', body:fd})
            .then(r=>r.json()).then(d => {
                if(d.url && activeUploadEl) {
                    activeUploadEl.innerHTML = `<img src="${d.url}" class="w-full rounded shadow"><input type="hidden" class="block-content" value="${d.url}">`;
                    activeUploadEl.className = "relative";
                }
            });
        });

        function triggerUpload(el) {
            activeUploadEl = el;
            fileInput.click();
        }
        // --- UPLOAD & PASTE HELPERY ---
function triggerInputUpload(btn) {
    const input = btn.previousElementSibling;
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    fileInput.onchange = e => {
        if(e.target.files[0]) handleInputUpload(input, e.target.files[0]);
    };
    fileInput.click();
}

function handleInputUpload(inputEl, file) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('shortcut', '1'); // <-- DODANA LINIJKA (Flaga dla backendu)
    
    inputEl.value = 'Wgrywanie...';
    inputEl.disabled = true;
    fetch('/admin/media/upload', {method:'POST', body:fd})
        .then(r=>r.json()).then(d => {
            inputEl.value = d.url || '';
            inputEl.disabled = false;
            // Opcjonalnie wywołujemy zdarzenie 'change', aby builder to "zauważył"
            inputEl.dispatchEvent(new Event('change', { bubbles: true }));
        }).catch(() => {
            inputEl.value = '';
            inputEl.disabled = false;
            alert('Błąd wgrywania');
        });
}

// Nasłuchiwacz globalny na wklejanie (Ctrl+V)
document.addEventListener('paste', function(e) {
    if (e.target && e.target.classList.contains('img-upload-input')) {
        const items = (e.clipboardData || e.originalEvent.clipboardData).items;
        for (let index in items) {
            const item = items[index];
            if (item.kind === 'file' && item.type.indexOf('image/') !== -1) {
                const blob = item.getAsFile();
                handleInputUpload(e.target, blob);
                e.preventDefault(); // Zatrzymuje wklejenie dziwnego ciągu znaków
                break;
            }
        }
    }
});

// Generator HTML dla inteligentnego inputa
function imgInputTpl(className, placeholder, value = '') {
    // Generujemy unikalne ID dla inputa, by łatwo do niego wrzucić wynik
    const uniqueId = 'img_in_' + Math.random().toString(36).substr(2, 9);
    return `
    <div class="flex w-full mb-1">
        <input type="text" id="${uniqueId}" class="${className} img-upload-input w-full border border-r-0 p-2 text-sm rounded-l bg-gray-50 focus:outline-none focus:bg-white" placeholder="${placeholder} (lub Ctrl+V)" value="${value}">
        <button type="button" onclick="openMediaPicker('${uniqueId}')" class="bg-purple-100 hover:bg-purple-200 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition" title="Wybierz z serwera">📂</button>
        <button type="button" onclick="triggerInputUpload(this)" class="bg-blue-100 hover:bg-blue-200 border border-blue-200 text-blue-700 px-3 rounded-r text-sm font-bold transition" title="Wgraj z dysku">⬆️</button>
    </div>`;
}

// Dodaj funkcję obsługującą Media Picker
let activePickerInputId = null;

function openMediaPicker(inputId) {
    activePickerInputId = inputId;
    document.getElementById('mediaPickerModal').classList.remove('hidden');
    // Ładujemy media managera w trybie 'picker' (przekazujemy parametr w URL)
    document.getElementById('mediaPickerFrame').src = '/admin/media?picker=1';
}

function closeMediaPicker() {
    document.getElementById('mediaPickerModal').classList.add('hidden');
    document.getElementById('mediaPickerFrame').src = '';
}

// Nasłuchuj wiadomości od iFrame'a (postMessage)
window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'media_selected') {
        if (activePickerInputId) {
            document.getElementById(activePickerInputId).value = event.data.url;
            // Wywołaj zdarzenie 'change' jeśli podpięto jakieś akcje
            document.getElementById(activePickerInputId).dispatchEvent(new Event('change'));
        }
        closeMediaPicker();
    }
});

// Generator HTML dla ustawień pojedynczego elementu (ID/CSS)
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
    </script>
</body>
</html>