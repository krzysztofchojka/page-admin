const pageId = window.CMS_CONFIG.pageId;
const savedContent = window.CMS_CONFIG.savedContent;
const availableForms = window.CMS_CONFIG.availableForms;
const availableGalleries = window.CMS_CONFIG.availableGalleries;
const availableCategories = window.CMS_CONFIG.availableCategories || [];

// Definicja obrazka zastępczego
const defaultPlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='20' fill='%239ca3af'%3EWybierz lub wgraj obraz%3C/text%3E%3C/svg%3E";

const blockIcons = { text: 'T', image: '🖼', video: '▶️', button: '🔘', divider: '➖', quote: '❝', columns_2: '◫', columns_3: '☰', banner: '🏔', image_cards: '🗂', carousel: '🎠', accordion: '⇕', map: '📍', countdown: '⏳', table: '🗄️', flight: '✈️', form: '📝', gallery: '📷', raw_html: '</>', system_login: '🔐', system_register: '📝', system_change_password: '🔑', system_lockdown: '🚧', posts_grid: '📰' };
const blockNames = { text: 'Tekst', image: 'Obrazek', video: 'Wideo', button: 'Przycisk', divider: 'Odstęp', quote: 'Cytat', columns_2: '2 Kolumny', columns_3: '3 Kolumny', banner: 'Baner', image_cards: 'Siatka Kart', carousel: 'Karuzela', accordion: 'Akordeon', map: 'Mapa', countdown: 'Odliczanie', table: 'Tabela', flight: 'Loty', form: 'Formularz', gallery: 'Galeria', raw_html: 'HTML', system_login: 'Logowanie', system_register: 'Rejestracja', system_change_password: 'Zmień Hasło', system_lockdown: 'Lockdown', posts_grid: 'Posty' };

let navSortables = [];

// ==========================================
// 1. NAWIGATOR I INICJALIZACJA
// ==========================================

function updateNavigator() {
    const navTree = document.getElementById('navigator-tree');
    if (!navTree) return; // Zabezpieczenie dla edytora postów (gdzie nie ma nawigatora)
    
    navTree.innerHTML = '';
    
    const rootZones = Array.from(document.querySelectorAll('.drop-zone[data-zone-uid]')).filter(z => !z.closest('.block-item'));

    if (rootZones.length > 0) {
        rootZones.forEach(zone => {
            if(rootZones.length > 1) {
                const zoneTitle = document.createElement('div');
                zoneTitle.className = "text-[10px] font-bold uppercase text-gray-500 mt-3 mb-1 pl-1";
                zoneTitle.innerText = "Strefa: " + zone.dataset.zoneUid;
                navTree.appendChild(zoneTitle);
            }

            const ul = document.createElement('ul');
            ul.className = 'nav-drop-zone min-h-[30px] pb-4 space-y-1';
            ul.dataset.refZone = zone.dataset.zoneUid;
            
            buildNavTree(zone, ul);
            navTree.appendChild(ul);
        });
    } else {
        const editor = document.getElementById('editor');
        if(editor) {
            const ul = document.createElement('ul');
            ul.className = 'nav-drop-zone min-h-[30px] pb-4 space-y-1';
            ul.dataset.refZone = 'editor';
            buildNavTree(editor, ul);
            navTree.appendChild(ul);
        }
    }
    
    initNavSortables();
}
window.updateNavigator = updateNavigator;

function buildNavTree(domContainer, navContainer) {
    Array.from(domContainer.children).forEach(el => {
        if (!el.classList.contains('block-item')) return;
        
        const uid = el.dataset.uid;
        const type = el.dataset.type;
        const icon = blockIcons[type] || '🧩';
        const name = blockNames[type] || type;

        const li = document.createElement('li');
        li.className = 'my-1 nav-item bg-white border border-gray-200 rounded shadow-sm group cursor-move';
        li.dataset.refUid = uid;

        let html = `
            <div class="flex items-center justify-between p-2 hover:bg-blue-50 transition cursor-pointer border border-transparent hover:border-blue-200 rounded" onclick="window.scrollToBlock('${uid}')">
                <div class="flex items-center gap-2 pointer-events-none">
                    <span class="text-gray-400 w-5 text-center text-lg">${icon}</span>
                    <span class="text-gray-700 font-bold text-xs truncate max-w-[140px]">${name}</span>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" onclick="window.toggleBlockSettings('${uid}', event)" class="text-gray-500 hover:text-blue-600 px-2 font-bold opacity-0 group-hover:opacity-100 transition" title="Ustawienia bloku">⋮</button>
                    <span class="text-gray-300 text-[10px] drag-handle-nav opacity-0 group-hover:opacity-100 transition px-1">✥</span>
                </div>
            </div>
        `;

        const childZones = Array.from(el.querySelectorAll('.drop-zone')).filter(dz => dz.closest('.block-item') === el);

        if (childZones.length > 0) {
            const zonesContainer = document.createElement('div');
            zonesContainer.className = 'pl-2 pr-2 pb-2';
            
            childZones.forEach((dz, idx) => {
                if(!dz.dataset.zoneUid) dz.dataset.zoneUid = uid + '_zone_' + idx;
                
                let zoneName = 'Zawartość';
                if(dz.className.includes('col-left')) zoneName = 'Lewa kolumna';
                if(dz.className.includes('col-center')) zoneName = 'Środkowa kolumna';
                if(dz.className.includes('col-right')) zoneName = 'Prawa kolumna';
                if(dz.className.includes('tab-content')) zoneName = 'Zakładka';

                const zoneLabel = document.createElement('div');
                zoneLabel.className = 'text-[9px] text-gray-400 font-bold uppercase mb-1 mt-1';
                zoneLabel.innerText = zoneName;

                const ul = document.createElement('ul');
                ul.className = 'nav-drop-zone min-h-[30px] bg-gray-50 border border-dashed border-gray-200 rounded p-1 mb-1 space-y-1';
                ul.dataset.refZone = dz.dataset.zoneUid;

                buildNavTree(dz, ul);
                zonesContainer.appendChild(zoneLabel);
                zonesContainer.appendChild(ul);
            });
            li.innerHTML = html;
            li.appendChild(zonesContainer);
        } else {
            li.innerHTML = html;
        }
        navContainer.appendChild(li);
    });
}

function initNavSortables() {
    navSortables.forEach(s => s.destroy());
    navSortables = [];

    document.querySelectorAll('.nav-drop-zone').forEach(el => {
        navSortables.push(Sortable.create(el, {
            group: { name: 'navigator', put: ['navigator', 'shared'] }, // POZWALA NA DROP Z PRAWIEGO PASKA
            animation: 150,
            handle: '.drag-handle-nav',
            fallbackOnBody: true,
            ghostClass: 'nav-ghost',
            swapThreshold: 0.65,
            
            // Gdy element zostaje dodany DO nawigatora z zewnętrznego źródła (np. z prawego paska)
            onAdd: function (evt) {
                const itemEl = evt.item;
                
                // Jeśli element przyszedł z prawego paska
                if (itemEl.classList.contains('sidebar-block')) {
                    const type = itemEl.dataset.type;
                    const toList = evt.to;
                    const newIndex = evt.newIndex;
                    const refZoneUid = toList.dataset.refZone;
                    
                    // 1. Usuwamy wizualny śmieć z nawigatora
                    itemEl.parentNode.removeChild(itemEl);
                    
                    // 2. Znajdujemy odpowiednią strefę drop-zone w GŁÓWNYM EDYTORZE
                    const realZone = document.querySelector(`[data-zone-uid="${refZoneUid}"]`);
                    
                    if (realZone) {
                        // 3. Generujemy nowy, pełny klocek HTML
                        const newBlock = renderBlock(type);
                        
                        // 4. Wstawiamy go do głównego edytora w odpowiednim miejscu (odwzorowując pozycję w nawigatorze)
                        const childBlocks = Array.from(realZone.children).filter(c => c.classList.contains('block-item'));
                        if (newIndex < childBlocks.length) {
                            realZone.insertBefore(newBlock, childBlocks[newIndex]);
                        } else {
                            realZone.appendChild(newBlock);
                        }
                        
                        // 5. Inicjujemy zawartość w głównym klocku (tak samo jak w standardowym dodawaniu)
                        if (type === 'text') initQuill(newBlock.querySelector('.quill-editor'), '');
                        if (type === 'accordion') initSortable(newBlock.querySelector('.tab-content'));
                        if (type === 'carousel') newBlock.querySelectorAll('.tab-content').forEach(tc => initSortable(tc));
                        if (type === 'columns_2' || type === 'columns_3') {
                            newBlock.querySelectorAll('.drop-zone').forEach(col => initSortable(col));
                        }
                        
                        // 6. Odświeżamy nawigator od zera (który sam zaciągnie nowo wygenerowany element)
                        updateNavigator();
                    }
                }
            },
            
            // Kiedy porządkujesz elementy WEWNĄTRZ nawigatora
            onEnd: function (evt) {
                const itemEl = evt.item;
                const toList = evt.to;
                const newIndex = evt.newIndex;
                const refUid = itemEl.dataset.refUid;
                const refZoneUid = toList.dataset.refZone;

                const realBlock = document.querySelector(`[data-uid="${refUid}"]`);
                const realZone = document.querySelector(`[data-zone-uid="${refZoneUid}"]`);

                if (realBlock && realZone) {
                    const childBlocks = Array.from(realZone.children).filter(c => c.classList.contains('block-item') && c !== realBlock);
                    if (newIndex < childBlocks.length) {
                        realZone.insertBefore(realBlock, childBlocks[newIndex]);
                    } else {
                        realZone.appendChild(realBlock);
                    }
                }
                updateNavigator();
            }
        }));
    });
}

window.scrollToBlock = function(uid) {
    const el = document.querySelector(`[data-uid="${uid}"]`);
    const scrollContainer = document.getElementById('main-editor-scroll');
    if (el && scrollContainer) {
        document.querySelectorAll('.ring-4').forEach(e => e.classList.remove('ring-4', 'ring-blue-500', 'ring-offset-2', 'z-50'));
        const y = el.getBoundingClientRect().top + scrollContainer.scrollTop - 40;
        scrollContainer.scrollTo({top: y-80, behavior: 'smooth'});
        el.classList.add('ring-4', 'ring-blue-500', 'ring-offset-2', 'z-50');
        setTimeout(() => el.classList.remove('ring-4', 'ring-blue-500', 'ring-offset-2', 'z-50'), 1500);
    }
}

window.toggleBlockSettings = function(uid, e) {
    e.stopPropagation(); // Zapobiega podwójnemu kliknięciu w rodzica
    window.scrollToBlock(uid); // Najpierw przesuwa ekran na blok
    
    const block = document.querySelector(`[data-uid="${uid}"]`);
    if (block) {
        const settingsPanel = block.querySelector('.block-settings-panel');
        if (settingsPanel) {
            // Otwiera panel ustawień (jeśli był zamknięty)
            settingsPanel.classList.remove('hidden');
            
            // Opcjonalnie: podświetla na ułamek sekundy cały blok, aby wskazać, że to on
            block.classList.add('ring-4', 'ring-orange-400', 'ring-offset-2');
            setTimeout(() => block.classList.remove('ring-4', 'ring-orange-400', 'ring-offset-2'), 1000);
        }
    }
}

function initSortable(el) {
    if(!el) return;
    
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
            if (itemEl.classList.contains('sidebar-block')) {
                const type = itemEl.dataset.type;
                const newBlock = renderBlock(type);
                itemEl.parentNode.insertBefore(newBlock, itemEl);
                itemEl.parentNode.removeChild(itemEl);
                
                if (type === 'text') initQuill(newBlock.querySelector('.quill-editor'), '');
                if (type === 'accordion') initSortable(newBlock.querySelector('.tab-content'));
                if (type === 'carousel') newBlock.querySelectorAll('.tab-content').forEach(tc => initSortable(tc));
                if (type === 'columns_2' || type === 'columns_3') {
                     newBlock.querySelectorAll('.drop-zone').forEach(col => initSortable(col));
                }

                updateNavigator();
            }
        },
        onEnd: function (evt) {
            updateNavigator();
        }
    });

    el.addEventListener('click', (e) => {
        if(e.target === el) {
            window.setActive(el, e);
        }
    });
}

window.setActive = function(el, e) {
    document.querySelectorAll('.ring-2').forEach(d => d.classList.remove('ring-2', 'ring-blue-300'));
    if(el && !el.id.includes('editor')) el.classList.add('ring-2', 'ring-blue-300');
    if(e) e.stopPropagation();
}

function renderRecursive(blocks, container) {
    if (!blocks || !container) return;
    
    blocks.forEach(block => {
        const el = renderBlock(block.type, block.content, block.settings);
        container.appendChild(el);

        if (block.type === 'text') initQuill(el.querySelector('.quill-editor'), block.content);
        
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

// ==========================================
// 2. RENDEROWANIE BLOKÓW (HTML/JS)
// ==========================================
const Size = typeof Quill !== 'undefined' ? Quill.import('attributors/style/size') : null;
if(Size) {
    Size.whitelist = ['12px', '16px', '20px', '24px', '32px'];
    Quill.register(Size, true);
}
const Align = typeof Quill !== 'undefined' ? Quill.import('attributors/style/align') : null;
if(Align) Quill.register(Align, true);

function initQuill(element, content) {
    if (!element) return;
    
    const container = element.parentElement;
    const txtArea = document.createElement('textarea');
    txtArea.className = 'quill-source-area';
    txtArea.style.display = 'none';
    container.appendChild(txtArea);

    // Zamiast szukać po ID, przekazujemy bezpośrednio element DOM!
    const quill = new Quill(element, {
        theme: 'snow',
        modules: {
            toolbar: {
                container: [
                    [{ 'header': [1, 2, 3, false] }],
                    [{ 'size': Size ? Size.whitelist : [] }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'code-block'],
                    ['clean']
                ],
                handlers: {
                    'code-block': function() {
                        const isSourceMode = txtArea.style.display === 'block';
                        if (!isSourceMode) {
                            txtArea.value = quill.root.innerHTML;
                            quill.container.style.display = 'none';
                            txtArea.style.display = 'block';
                        } else {
                            quill.root.innerHTML = txtArea.value;
                            txtArea.style.display = 'none';
                            quill.container.style.display = 'block';
                        }
                    }
                }
            }
        }
    });

    if (content) quill.root.innerHTML = content;
    // Zapisujemy instancję bezpośrednio w elemencie DOM, żeby ułatwić pobieranie
    element.__quill = quill; 
}

function renderBlock(type, content = '', blockSettings = null) {
    const div = document.createElement('div');
    const uid = 'b_' + Math.random().toString(36).substr(2, 9);
    div.dataset.uid = uid;
    div.dataset.type = type;
    div.className = "group relative border border-gray-200 hover:border-blue-400 rounded-xl p-5 mb-4 bg-white transition-all duration-300 shadow-sm block-item";
    
    const settings = blockSettings || { id: '', css: '', style: '' };

    const controls = `
    <div class="absolute -top-3 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-50">
        <span class="drag-handle text-gray-600 hover:text-blue-600 p-1.5 bg-gray-100 border border-gray-300 rounded shadow cursor-move" title="Przeciągnij (Drag & Drop)">✥ Przesuń</span>
        <button type="button" onclick="this.parentElement.nextElementSibling.classList.toggle('hidden')" class="text-gray-600 hover:text-gray-800 p-1.5 bg-gray-100 border border-gray-300 rounded shadow" title="Ustawienia (ID, Klasy CSS)">⚙️</button>
        <button type="button" onclick="if(confirm('Na pewno usunąć ten blok?')) { this.closest('.block-item').remove(); window.updateNavigator(); }" class="text-red-500 hover:text-red-700 p-1.5 bg-gray-100 border border-gray-300 rounded shadow" title="Usuń blok">🗑</button>
    </div>
    <div class="block-settings-panel hidden bg-blue-50 p-4 mt-6 mb-4 border border-blue-200 rounded text-sm shadow-inner">
        <div class="grid grid-cols-3 gap-4">
            <div><label class="block font-bold text-gray-600 mb-1">ID HTML</label><input type="text" class="set-id w-full border p-2 rounded" placeholder="np. sekcja-1" value="${settings.id || ''}"></div>
            <div><label class="block font-bold text-gray-600 mb-1">Klasy CSS (Tailwind)</label><input type="text" class="set-css w-full border p-2 rounded" placeholder="np. mt-10 text-center" value="${settings.css || ''}"></div>
            <div><label class="block font-bold text-gray-600 mb-1">Style wew. (Inline)</label><input type="text" class="set-style w-full border p-2 rounded" placeholder="np. background: #000;" value="${settings.style || ''}"></div>
        </div>
    </div>`;

    let innerHTML = '';

    if (type === 'text') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-blue-500 uppercase">T Tekst / Edytor Wizualny</span></div>
        <div class="bg-gray-50 border rounded-lg no-drag"><div class="quill-editor"></div></div>`;
    } 
    else if (type === 'posts_grid') {
        div.className += " border-2 border-dashed border-purple-200 bg-purple-50/20";
        const data = typeof content === 'object' && content !== null ? content : { limit: 6, category: '' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-purple-600 uppercase">📰 Siatka Postów (Blog)</span></div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Kategoria</label>
                <select class="p-category w-full border p-2 rounded bg-white focus:ring-2 focus:ring-purple-500">
                    ${buildOptions(availableCategories, data.category)}
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">Limit postów do wyświetlenia</label>
                <input type="number" class="p-limit w-full border p-2 rounded bg-white focus:ring-2 focus:ring-purple-500" placeholder="6" value="${data.limit || 6}">
            </div>
        </div>`;
    }
    else if (type === 'raw_html') {
        const safeContent = (typeof content === 'string' ? content : '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        
        innerHTML = `
        <div class="bg-gray-900 rounded p-3 no-drag border border-gray-700">
            <div class="flex justify-between items-center mb-2">
                <label class="text-xs font-mono text-gray-400">&lt;/&gt; ZAAWANSOWANY KOD HTML</label>
                <button type="button" class="toggle-ace-btn text-[10px] bg-gray-700 text-gray-300 px-2 py-1 rounded hover:text-white border border-gray-600 transition shadow">Rozszerz / Zwiń okno</button>
            </div>
            <div class="ace-editor-container w-full rounded border border-gray-800" style="height: 150px; transition: height 0.3s;"></div>
            <textarea class="block-content hidden">${safeContent}</textarea>
        </div>`;
        
        // Zamiast szukać po ID używamy referencji bezpośredniej querySelector na wygenerowanym kontenerze
        setTimeout(() => {
            const aceContainer = div.querySelector('.ace-editor-container');
            const hiddenTextarea = div.querySelector('.block-content');
            const toggleBtn = div.querySelector('.toggle-ace-btn');
            
            if(aceContainer && hiddenTextarea && typeof ace !== 'undefined') {
                const editor = ace.edit(aceContainer);
                editor.setTheme("ace/theme/monokai");
                editor.session.setMode("ace/mode/html");
                editor.setOptions({ fontSize: "13px", showPrintMargin: false, wrap: true });
                
                editor.session.setValue(hiddenTextarea.value);
                
                editor.session.on('change', () => {
                    hiddenTextarea.value = editor.getValue();
                });
                
                if (toggleBtn) {
                    toggleBtn.onclick = () => {
                        aceContainer.style.height = (aceContainer.style.height === '500px' ? '150px' : '500px');
                        setTimeout(() => editor.resize(), 300);
                    };
                }
            }
        }, 100);
    }
    else if (type === 'columns_2') {
        div.className += " border-2 border-dashed border-indigo-200 bg-indigo-50/20";
        innerHTML = `
        <div class="flex gap-2 mb-3"><span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">◫ Układ: 2 Kolumny</span></div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="col-left drop-zone bg-white border border-indigo-100 rounded-lg p-4 min-h-[120px]"></div>
            <div class="col-right drop-zone bg-white border border-indigo-100 rounded-lg p-4 min-h-[120px]"></div>
        </div>`;
        setTimeout(() => {
            initSortable(div.querySelector('.col-left'));
            initSortable(div.querySelector('.col-right'));
        }, 0);
    } 
    else if (type === 'columns_3') {
        div.className += " border-2 border-dashed border-emerald-200 bg-emerald-50/20";
        innerHTML = `
        <div class="flex gap-2 mb-3"><span class="text-xs font-bold text-emerald-500 uppercase tracking-wider">☰ Układ: 3 Kolumny</span></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="col-left drop-zone bg-white border border-emerald-100 rounded-lg p-4 min-h-[120px]"></div>
            <div class="col-center drop-zone bg-white border border-emerald-100 rounded-lg p-4 min-h-[120px]"></div>
            <div class="col-right drop-zone bg-white border border-emerald-100 rounded-lg p-4 min-h-[120px]"></div>
        </div>`;
        setTimeout(() => {
            initSortable(div.querySelector('.col-left'));
            initSortable(div.querySelector('.col-center'));
            initSortable(div.querySelector('.col-right'));
        }, 0);
    }
    else if (type === 'image') {
        const hasImg = typeof content === 'string' && content.length > 5;
        const imgValue = hasImg ? content : '';
        const uniqueId = 'img_in_' + Math.random().toString(36).substr(2, 9);
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-green-500 uppercase">🖼 Pojedynczy Obrazek</span></div>
        <div class="mb-3">
            <div class="flex w-full mb-2">
                <input type="text" id="${uniqueId}" class="block-content w-full border border-r-0 p-2 text-sm rounded-l bg-gray-50 focus:bg-white focus:outline-none" placeholder="Adres URL obrazka..." value="${imgValue}" oninput="this.closest('.block-item').querySelector('.img-preview').src = this.value || defaultPlaceholder">
                <button type="button" onclick="window.openMediaPicker('${uniqueId}')" class="bg-purple-100 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition" title="Wybierz z Media">📂</button>
                <button type="button" onclick="window.triggerInputUpload(this)" class="bg-blue-100 hover:bg-blue-200 border border-blue-200 text-blue-700 px-3 rounded-r text-sm font-bold transition" title="Wgraj Plik">⬆️</button>
            </div>
        </div>
        <div class="bg-gray-100 border border-gray-200 rounded-lg overflow-hidden flex items-center justify-center min-h-[100px]">
            <img src="${hasImg ? imgValue : defaultPlaceholder}" class="img-preview w-full object-contain max-h-[400px]">
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
            <select class="block-select border rounded p-2 w-full text-center font-bold bg-white shadow-sm focus:ring-2 focus:ring-teal-500">
                ${buildOptions(availableForms, content)}
            </select>
        </div>`;
    }
    else if (type === 'gallery') {
        innerHTML = `
        <div class="bg-pink-50 border border-pink-200 p-4 rounded-lg text-center">
            <label class="block text-sm font-bold text-pink-800 mb-2">📷 Wybierz Galerię</label>
            <select class="block-select border rounded p-2 w-full text-center font-bold bg-white shadow-sm focus:ring-2 focus:ring-pink-500">
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
    else if (type === 'countdown') {
        const data = typeof content === 'object' ? content : { date: '', title: 'Do startu wydarzenia:' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-purple-500 uppercase">⏳ Odliczanie</span></div>
        <label class="block text-xs text-gray-500 mb-1">Tytuł nad licznikiem</label>
        <input type="text" class="cd-title w-full border p-2 rounded mb-3" placeholder="Do startu wydarzenia:" value="${data.title}">
        <label class="block text-xs text-gray-500 mb-1">Data i godzina zakończenia</label>
        <input type="datetime-local" class="cd-date w-full border p-2 rounded" value="${data.date}">`;
    }
    else if (type === 'table') {
        innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-blue-500 uppercase">🗄️ Tabela Danych</span>
            <div class="flex gap-2">
                <button type="button" onclick="window.toggleEditor(this, 'visual')" class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded font-bold">Wizualny</button>
                <button type="button" onclick="window.toggleEditor(this, 'raw')" class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded font-bold">Surowy (Excel)</button>
            </div>
        </div>
        <div class="editor-visual overflow-x-auto bg-gray-50 p-2 rounded border">
            <table class="w-full text-left bg-white border">
                <tbody class="visual-table-body"></tbody>
            </table>
            <div class="mt-2 flex gap-2">
                <button type="button" onclick="window.addTableRow(this)" class="text-xs bg-white border px-3 py-1 rounded shadow-sm font-bold">+ Wiersz</button>
                <button type="button" onclick="window.addTableCol(this)" class="text-xs bg-white border px-3 py-1 rounded shadow-sm font-bold">+ Kolumna</button>
            </div>
        </div>
        <div class="editor-raw hidden">
            <label class="block text-xs text-gray-500 mb-1">Wklej tabele prosto z Excela (wartości oddzielone tabulatorem)</label>
            <textarea class="table-data w-full h-32 border p-2 text-sm rounded whitespace-pre font-mono focus:outline-none focus:ring-1 focus:ring-blue-500" oninput="window.syncTableRawToVisual(this.closest('.block-item'))" placeholder="Kolumna1\\tKolumna2\\nWartość1\\tWartość2">${content || 'Nagłówek 1\\tNagłówek 2\\nWartość A\\tWartość B'}</textarea>
        </div>`;
        setTimeout(() => window.syncTableRawToVisual(div), 0);
    }
    else if (type === 'flight') {
        const data = typeof content === 'object' ? content : { html: content, state: [] };
        const rawHtml = data.html || '';
        const stateJson = encodeURIComponent(JSON.stringify(data.state || []));
        innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-sky-500 uppercase">✈️ Przeloty</span>
            <div class="flex gap-2">
                <button type="button" onclick="window.toggleEditor(this, 'visual')" class="bg-blue-100 text-blue-700 text-xs px-3 py-1 rounded font-bold">Kreator</button>
                <button type="button" onclick="window.toggleEditor(this, 'raw')" class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded font-bold">KOD HTML</button>
            </div>
        </div>
        <input type="hidden" class="flight-state" value="${stateJson}">
        <div class="editor-visual bg-sky-50 p-4 rounded-lg border border-sky-100">
            <div class="flight-segments-container flex flex-col gap-3 mb-4"></div>
            <div class="bg-white p-3 rounded border shadow-sm mt-4">
                <span class="text-xs font-bold text-gray-500 block mb-2">Stopka widżetu (Podsumowanie)</span>
                <div class="grid grid-cols-2 gap-3">
                    <input type="text" class="flight-f-arr w-full border p-1.5 text-xs rounded" placeholder="Przylot: wt., 30 wrz 2025" oninput="window.syncFlightVisualToRaw(this.closest('.block-item'))">
                    <input type="text" class="flight-f-time w-full border p-1.5 text-xs rounded" placeholder="Czas trwania: 5 godz. 40 min" oninput="window.syncFlightVisualToRaw(this.closest('.block-item'))">
                </div>
            </div>
            <div class="flex gap-2 mt-4">
                <button type="button" onclick="window.addFlightSegment(this, 'flight')" class="text-xs bg-white border border-sky-300 text-sky-700 px-4 py-2 rounded shadow-sm font-bold hover:bg-sky-100">✈️ Dodaj Lot</button>
                <button type="button" onclick="window.addFlightSegment(this, 'connection')" class="text-xs bg-white border border-indigo-300 text-indigo-700 px-4 py-2 rounded shadow-sm font-bold hover:bg-indigo-100">⏱ Dodaj Przesiadkę</button>
            </div>
        </div>
        <div class="editor-raw hidden">
            <label class="block text-xs text-gray-500 mb-1">Wygenerowany kod HTML (Możesz edytować go ręcznie)</label>
            <textarea class="flight-html w-full h-64 border p-3 rounded font-mono text-xs bg-gray-900 text-sky-300 focus:outline-none">${rawHtml}</textarea>
        </div>`;
        setTimeout(() => initFlightVisual(div), 0);
    }
    else if (type === 'image_cards') {
        div.className += " border-2 border-dashed border-orange-300 bg-orange-50/20";
        const data = typeof content === 'object' ? content : { cards: [] };
        let cardsHTML = '';
        if (data.cards && data.cards.length > 0) {
            data.cards.forEach((card) => {
                cardsHTML += `
                <div class="image-card border border-gray-300 bg-white p-3 mb-2 rounded shadow-sm relative group grid grid-cols-2 gap-2">
                    <button type="button" onclick="this.closest('.image-card').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10" title="Ustawienia kafelka">⚙️</button>
                    <button type="button" onclick="this.closest('.image-card').remove(); window.updateNavigator();" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">X</button>
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
        <button type="button" onclick="window.addImageCard(this)" class="bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj Kartę</button>`;
    }
    else if (type === 'carousel') {
        div.className += " border-2 border-dashed border-blue-300 bg-blue-50/30";
        const data = typeof content === 'object' ? content : { arrows: true, tabs: [] };
        let tabsHTML = '';
        if(data.tabs) {
            data.tabs.forEach((tab) => {
                tabsHTML += `
                <div class="carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded-lg shadow-sm relative group">
                    <button type="button" onclick="this.closest('.carousel-tab').remove(); window.updateNavigator();" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10 shadow">X</button>
                    <div class="flex gap-2 mb-2 w-full">
                        <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/Emoji', tab.icon)}</div>
                        <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł zakładki" value="${tab.label || ''}">
                    </div>
                    <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded p-4 min-h-[100px]"></div>
                </div>`;
            });
        }
        innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-bold text-blue-800 uppercase tracking-wider">🎠 Karuzela / Zakładki</span>
            <label class="text-sm font-bold text-gray-700 flex items-center gap-2"><input type="checkbox" class="carousel-arrows" ${data.arrows ? 'checked' : ''}> Pokaż strzałki</label>
        </div>
        <div class="carousel-tabs-container min-h-[5px] mb-3">${tabsHTML}</div>
        <button type="button" onclick="window.addCarouselTab(this)" class="bg-white border-2 border-blue-500 text-blue-600 hover:bg-blue-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj Zakładkę / Kafelek</button>`;
        setTimeout(() => {
            div.querySelectorAll('.tab-content').forEach(el => initSortable(el));
        }, 0);
    }
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
                <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded min-h-[100px] p-4"></div>
            </div>
        </div>`;
        setTimeout(() => {
            initSortable(div.querySelector('.tab-content'));
        }, 0);
    }
    else if (type === 'system_login') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase">🔐 System: Logowanie</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3">🔐</div>
            <h4 class="font-bold text-gray-700 text-lg">Moduł Logowania</h4>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">W tym miejscu na gotowej stronie wyrenderuje się kompletny formularz logowania do systemu.</p>
            <div class="mt-4 bg-white border border-gray-200 rounded p-4 opacity-50 shadow-sm">
                <div class="h-8 bg-gray-100 rounded mb-2 w-full"></div>
                <div class="h-8 bg-gray-100 rounded mb-4 w-full"></div>
                <div class="h-10 bg-blue-500 rounded w-full"></div>
            </div>
        </div>`;
    }
    else if (type === 'system_register') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase">📝 System: Rejestracja</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3">📝</div>
            <h4 class="font-bold text-gray-700 text-lg">Moduł Rejestracji</h4>
            <p class="text-xs text-gray-500 mt-2 leading-relaxed">Tutaj wyświetli się formularz zakładania nowego konta dla odwiedzających.</p>
            <div class="mt-4 bg-white border border-gray-200 rounded p-4 opacity-50 shadow-sm">
                <div class="h-8 bg-gray-100 rounded mb-2 w-full"></div>
                <div class="h-8 bg-gray-100 rounded mb-2 w-full"></div>
                <div class="h-8 bg-gray-100 rounded mb-4 w-full"></div>
                <div class="h-10 bg-green-500 rounded w-full"></div>
            </div>
        </div>`;
    }
    else if (type === 'system_change_password') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase">🔑 System: Zmiana Hasła</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3">🔑</div><h4 class="font-bold text-gray-700 text-lg">Moduł Zmiany Hasła</h4>
            <div class="mt-4 bg-white border border-gray-200 rounded p-4 opacity-50 shadow-sm"><div class="h-8 bg-gray-100 rounded mb-2 w-full"></div><div class="h-8 bg-gray-100 rounded mb-4 w-full"></div><div class="h-10 bg-yellow-500 rounded w-full"></div></div>
        </div>`;
    }
    else if (type === 'system_lockdown') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase">🚧 System: Lockdown</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3">🚧</div><h4 class="font-bold text-gray-700 text-lg">Moduł Lockdown (Hasło Ogólne)</h4>
            <div class="mt-4 bg-white border border-gray-200 rounded p-4 opacity-50 shadow-sm"><div class="h-8 bg-gray-100 rounded mb-4 w-full"></div><div class="h-10 bg-gray-800 rounded w-full"></div></div>
        </div>`;
    }
    else {
        innerHTML = `<div class="p-4 bg-gray-200 text-center">Brak definicji bloku: ${type}</div>`;
    }

    div.innerHTML = controls + innerHTML;
    
    if(type === 'form' || type === 'gallery') {
        const sel = div.querySelector('select');
        if(sel) sel.value = content || '';
    }
    
    return div;
}

// ==========================================
// 3. EVENTY (DODAWANIE TABÓW, KART)
// ==========================================

window.addCarouselTab = function(btn) {
    const container = btn.previousElementSibling;
    const div = document.createElement('div');
    div.className = "carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded shadow-sm relative group";
    div.innerHTML = `
    <button type="button" onclick="this.closest('.carousel-tab').remove(); window.updateNavigator();" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10 shadow">X</button>
    <div class="flex gap-2 mb-2 w-full">
        <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/URL')}</div>
        <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł Zakładki">
    </div>
    <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded p-4 min-h-[100px]"></div>
    `;
    container.appendChild(div);
    initSortable(div.querySelector('.tab-content'));
    window.updateNavigator();
};

function buildOptions(list, selected) {
    let html = '<option value="">-- Wybierz z listy --</option>';
    if(list) list.forEach(i => {
        const label = i.title || i.name; // Rozpoznaje `title` (formularze) lub `name` (kategorie)
        html += `<option value="${i.id}" ${i.id==selected?'selected':''}>${label}</option>`;
    });
    return html;
}

window.addImageCard = function(btn) {
    const container = btn.previousElementSibling;
    const div = document.createElement('div');
    div.className = "image-card border border-gray-300 bg-white p-3 mb-2 rounded shadow-sm relative group grid grid-cols-2 gap-2";
    div.innerHTML = `
    <button type="button" onclick="this.closest('.image-card').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10" title="Ustawienia kafelka">⚙️</button>
    <button type="button" onclick="this.closest('.image-card').remove(); window.updateNavigator();" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10">X</button>
    <div class="col-span-2">${imgInputTpl('card-img', 'URL Zdjęcia')}</div>
    <input type="text" class="card-title w-full border p-2 text-sm rounded font-bold text-orange-600" placeholder="Tytuł (np. Agenda)">
    <input type="text" class="card-subtitle w-full border p-2 text-sm rounded" placeholder="Podtytuł (np. Harmonogram)">
    <div class="col-span-2"><input type="text" class="card-link w-full border p-2 text-sm rounded" placeholder="Link docelowy"></div>
    ${itemSettingsTpl()}
    `;
    container.appendChild(div);
    window.updateNavigator();
};

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

// ==========================================
// 4. ZBIERANIE I ZAPISYWANIE
// ==========================================

function getBlocksFromContainer(container) {
    const blocks = [];
    Array.from(container.children).forEach(el => {
        if(!el.dataset.type) return;

        const type = el.dataset.type;
        let content = '';
        let children = {};
        
        const settingsPanel = el.querySelector('.block-settings-panel');
        let settings = {};
        if (settingsPanel) {
            settings = {
                id: settingsPanel.querySelector('.set-id').value,
                css: settingsPanel.querySelector('.set-css').value,
                style: settingsPanel.querySelector('.set-style').value
            };
        }

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
            if(editorDiv && editorDiv.__quill) {
                content = editorDiv.__quill.root.innerHTML;
            }
        } else if (type === 'raw_html') {
            content = el.querySelector('textarea.block-content').value;
        } else if (type === 'image') {
            content = el.querySelector('.block-content').value;
        } else if (type === 'form' || type === 'gallery') {
            content = el.querySelector('select').value;
        } else if (type === 'linked_image') {
            content = { url: el.querySelector('.block-img-url').value, link: el.querySelector('.block-link-url').value };
        } else if (type === 'banner') {
            content = { bg: el.querySelector('.ban-bg').value, title: el.querySelector('.ban-title').value, subtitle: el.querySelector('.ban-subtitle').value };
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
        } else if (type === 'map') {
            content = { lat: el.querySelector('.map-lat').value, lng: el.querySelector('.map-lng').value, zoom: el.querySelector('.map-zoom').value, tooltip: el.querySelector('.map-tooltip').value };
        } else if (type === 'countdown') {
            content = { title: el.querySelector('.cd-title').value, date: el.querySelector('.cd-date').value };
        } else if (type === 'table') {
            content = el.querySelector('.table-data').value;
        } else if (type === 'flight') {
            content = {
                html: el.querySelector('.flight-html').value,
                state: JSON.parse(decodeURIComponent(el.querySelector('.flight-state').value || '%5B%5D'))
            };
        } else if (type === 'image_cards') {
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
        } else if (type === 'video') {
            content = el.querySelector('.block-video').value;
        } else if (type === 'button') {
            content = { label: el.querySelector('.btn-label').value, url: el.querySelector('.btn-url').value, style: el.querySelector('.btn-style').value };
        } else if (type === 'divider') {
            content = { height: el.querySelector('.div-height').value };
        } else if (type === 'quote') {
            content = { text: el.querySelector('.quote-text').value, author: el.querySelector('.quote-author').value };
        } 
        else if (type === 'posts_grid') {
            content = {
                category: el.querySelector('.p-category').value,
                limit: el.querySelector('.p-limit').value
            };
        }

        blocks.push({ type, content, children, settings });
    });
    return blocks;
}
window.getBlocksFromContainer = getBlocksFromContainer;

window.savePage = function() {
    const zones = document.querySelectorAll('div[data-zone-uid]');
    let dataToSave;

    if (zones.length > 0) {
        dataToSave = {};
        zones.forEach(zone => {
            const uid = zone.dataset.zoneUid;
            dataToSave[uid] = getBlocksFromContainer(zone);
        });
    } else {
        const editor = document.getElementById('editor');
        if (editor) {
             dataToSave = getBlocksFromContainer(editor);
        } else {
             dataToSave = [];
        }
    }

    const tplSelect = document.getElementById('pageTemplate');
    const templateId = tplSelect ? tplSelect.value : '';

    const btn = document.querySelector('button[onclick="savePage()"]') || document.querySelector('button[onclick="window.savePage()"]');
    if (!btn) return;
    
    const oldText = btn.innerText;
    btn.innerText = "Zapisywanie...";

    fetch('/admin/pages/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: pageId,
            title: document.getElementById('pageTitle').value,
            slug: document.getElementById('pageSlug').value,
            content: dataToSave,
            template_id: templateId,
            page_role: document.getElementById('pageRole').value
        })
    })
    .then(res => res.json())
    .then(d => {
        btn.innerText = d.status === 'success' ? 'Zapisano!' : 'Błąd zapisu';
        setTimeout(() => btn.innerText = oldText, 2000);
    });
};

// ==========================================
// 5. EDYTORY WIZUALNE TABEL/LOTÓW & MEDIA
// ==========================================

window.toggleEditor = function(btn, mode) {
    const block = btn.closest('.block-item');
    const visual = block.querySelector('.editor-visual');
    const raw = block.querySelector('.editor-raw');
    
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
};

window.syncTableRawToVisual = function(block) {
    const raw = block.querySelector('.table-data').value;
    const tbody = block.querySelector('.visual-table-body');
    tbody.innerHTML = '';
    
    const rows = raw.split('\n');
    if (rows.length === 0 || (rows.length === 1 && rows[0].trim() === '')) return;

    const colsCount = rows[0].split('\t').length;
    
    const ctrlTr = document.createElement('tr');
    for (let i = 0; i < colsCount; i++) {
        ctrlTr.innerHTML += `<th class="bg-gray-100 border p-1 text-center"><button type="button" onclick="window.removeTableCol(this, ${i})" class="text-red-400 hover:text-red-600 text-[10px] font-bold">Usuń kol.</button></th>`;
    }
    ctrlTr.innerHTML += `<th class="bg-gray-100 border-0 w-8"></th>`;
    tbody.appendChild(ctrlTr);

    rows.forEach((r, rowIdx) => {
        const tr = document.createElement('tr');
        tr.className = rowIdx === 0 ? "bg-blue-50 font-bold" : "";
        const cells = r.split('\t');
        for (let i = 0; i < colsCount; i++) {
            let c = cells[i] !== undefined ? cells[i] : '';
            tr.innerHTML += `<td class="border p-0"><input type="text" class="w-full text-sm outline-none px-2 py-1 bg-transparent" value="${c.replace(/"/g, '&quot;')}" oninput="window.syncTableVisualToRaw(this)"></td>`;
        }
        tr.innerHTML += `<td class="border-0 w-8 text-center"><button type="button" onclick="window.removeTableRow(this)" class="text-red-400 hover:text-red-600 text-xs font-bold" title="Usuń wiersz">X</button></td>`;
        tbody.appendChild(tr);
    });
};

window.syncTableVisualToRaw = function(element) {
    const block = element.closest('.block-item') || element;
    const tbody = block.querySelector('.visual-table-body');
    const textarea = block.querySelector('.table-data');
    
    let tsv = [];
    const dataRows = Array.from(tbody.querySelectorAll('tr')).slice(1);
    
    dataRows.forEach(tr => {
        let row = [];
        tr.querySelectorAll('input').forEach(inp => row.push(inp.value));
        tsv.push(row.join('\t'));
    });
    
    textarea.value = tsv.join('\n');
};

window.addTableRow = function(btn) {
    const block = btn.closest('.block-item');
    const textarea = block.querySelector('.table-data');
    const cols = textarea.value.split('\n')[0].split('\t').length || 1;
    const newRow = new Array(cols).fill('-').join('\t');
    textarea.value += (textarea.value ? '\n' : '') + newRow;
    window.syncTableRawToVisual(block);
};

window.addTableCol = function(btn) {
    const block = btn.closest('.block-item');
    const textarea = block.querySelector('.table-data');
    let rows = textarea.value.split('\n');
    rows = rows.map((r, i) => r + (i === 0 ? '\tNagłówek' : '\t-'));
    textarea.value = rows.join('\n');
    window.syncTableRawToVisual(block);
};

window.removeTableRow = function(btn) {
    const block = btn.closest('.block-item');
    btn.closest('tr').remove();
    window.syncTableVisualToRaw(block);
};

window.removeTableCol = function(btn, colIdx) {
    const block = btn.closest('.block-item');
    const textarea = block.querySelector('.table-data');
    let rows = textarea.value.split('\n');
    rows = rows.map(r => {
        let cells = r.split('\t');
        cells.splice(colIdx, 1);
        return cells.join('\t');
    });
    textarea.value = rows.join('\n');
    window.syncTableRawToVisual(block);
};

window.removeFlightSegment = function(btn) {
    const block = btn.closest('.block-item');
    btn.closest('.flight-segment').remove();
    window.syncFlightVisualToRaw(block);
};

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

window.addFlightSegment = function(btn, type) {
    const container = btn.closest('.editor-visual').querySelector('.flight-segments-container');
    addFlightSegmentHTML(container, type, {});
    window.syncFlightVisualToRaw(btn.closest('.block-item'));
};

function addFlightSegmentHTML(container, type, data) {
    const div = document.createElement('div');
    div.className = "flight-segment relative bg-white border rounded p-3 shadow-sm";
    div.dataset.type = type;
    
    if (type === 'flight') {
        div.innerHTML = `
        <button type="button" onclick="window.removeFlightSegment(this)" class="absolute top-2 right-2 text-red-500 font-bold text-xs bg-red-50 px-2 py-1 rounded hover:bg-red-100 z-10">X Usuń Lot</button>
        <div class="grid grid-cols-2 gap-3 mb-2 pr-20">
            <div><label class="text-[10px] uppercase text-gray-500 font-bold block">Linia Lotnicza</label><input type="text" class="f-airline w-full border-b p-1 text-xs outline-none" placeholder="LOT LO601" value="${data.airline || ''}"></div>
            <div><label class="text-[10px] uppercase text-gray-500 font-bold block mb-1">Logotyp (Media)</label>${imgInputTpl('f-logo', 'URL Logotypu', data.logo || '')}</div>
        </div>
        <div class="grid grid-cols-3 gap-3">
            <div class="col-span-1 border-r pr-2">
                <label class="text-[10px] uppercase text-gray-500 font-bold block">Czas (PL)</label><input type="text" class="f-dur-pl w-full border-b p-1 text-xs outline-none mb-1" placeholder="2 godz. 40 min" value="${data.durPl || ''}">
                <label class="text-[10px] uppercase text-gray-500 font-bold block mt-2">Czas (EN)</label><input type="text" class="f-dur-en w-full border-b p-1 text-xs outline-none" placeholder="2 hr 40 min" value="${data.durEn || ''}">
            </div>
            <div class="col-span-2 grid grid-cols-2 gap-2">
                <div>
                    <label class="text-[10px] uppercase text-gray-500 font-bold block text-blue-600">Start</label>
                    <input type="text" class="f-dep-pl w-full border-b p-1 text-xs outline-none mb-1" placeholder="09:20 WAW Chopin" value="${data.depPl || ''}">
                    <input type="text" class="f-dep-en w-full border-b p-1 text-xs outline-none text-gray-400" placeholder="EN: 09:20 WAW Chopin" value="${data.depEn || ''}">
                </div>
                <div>
                    <label class="text-[10px] uppercase text-gray-500 font-bold block text-green-600">Lądowanie</label>
                    <input type="text" class="f-arr-pl w-full border-b p-1 text-xs outline-none mb-1" placeholder="13:00 ATH Ateny" value="${data.arrPl || ''}">
                    <input type="text" class="f-arr-en w-full border-b p-1 text-xs outline-none text-gray-400" placeholder="EN: 13:00 ATH Athens" value="${data.arrEn || ''}">
                </div>
            </div>
        </div>`;
    } else if (type === 'connection') {
        div.className = "flight-segment relative bg-indigo-50 border border-indigo-200 rounded p-2 text-center";
        div.innerHTML = `
        <button type="button" onclick="window.removeFlightSegment(this)" class="absolute top-1 right-1 text-red-500 font-bold text-xs hover:text-red-700 bg-white rounded-full w-5 h-5 z-10">X</button>
        <input type="text" class="f-conn-pl w-full bg-transparent border-b border-indigo-300 p-1 text-xs text-center outline-none font-bold text-indigo-800 mb-1 relative z-0" placeholder="1 godz. 55 min Przesiadka" value="${data.connPl || ''}">
        <input type="text" class="f-conn-en w-full bg-transparent border-b border-indigo-300 p-1 text-xs text-center outline-none text-indigo-500 relative z-0" placeholder="EN: 1 hr 55 min Connection" value="${data.connEn || ''}">`;
    }
    
    div.querySelectorAll('input').forEach(inp => {
        inp.addEventListener('input', () => window.syncFlightVisualToRaw(inp.closest('.block-item')));
    });

    container.appendChild(div);
}

window.syncFlightVisualToRaw = function(block) {
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

            const durPlFormatted = s.durPl ? s.durPl.replace(/(godz\.|hr|min)\s+/g, '$1<br>') : '-';
            const durEnFormatted = s.durEn ? s.durEn.replace(/(godz\.|hr|min)\s+/g, '$1<br>') : '';
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
            const s = { type: 'connection', connPl: seg.querySelector('.f-conn-pl').value, connEn: seg.querySelector('.f-conn-en').value };
            state.push(s);
            const enConn = s.connEn ? `<span class="en-translation">${s.connEn}</span>` : '';
            html += `<div class="infobubble">${s.connPl || '-'} ${enConn}</div>\n`;
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
};

const fileInput = document.createElement('input');
fileInput.type = 'file';
fileInput.style.display='none';
document.body.appendChild(fileInput);

window.triggerInputUpload = function(btn) {
    const input = btn.previousElementSibling.previousElementSibling;
    const fileInputTemp = document.createElement('input');
    fileInputTemp.type = 'file';
    fileInputTemp.accept = 'image/*';
    fileInputTemp.onchange = e => {
        if(e.target.files[0]) handleInputUpload(input, e.target.files[0]);
    };
    fileInputTemp.click();
};

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
        inputEl.dispatchEvent(new Event('input'));
    }).catch(() => {
        inputEl.value = '';
        inputEl.disabled = false;
    });
}

function imgInputTpl(className, placeholder, value = '') {
    const uniqueId = 'img_in_' + Math.random().toString(36).substr(2, 9);
    return `
    <div class="flex w-full mb-1">
        <input type="text" id="${uniqueId}" class="${className} w-full border border-r-0 p-2 text-sm rounded-l bg-gray-50 focus:bg-white focus:outline-none" placeholder="${placeholder}" value="${value}" oninput="const p = this.closest('.block-item'); if(p){ const img = p.querySelector('.img-preview'); if(img) img.src = this.value || defaultPlaceholder; }">
        <button type="button" onclick="window.openMediaPicker('${uniqueId}')" class="bg-purple-100 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition" title="Wybierz z Media">📂</button>
        <button type="button" onclick="window.triggerInputUpload(this)" class="bg-blue-100 hover:bg-blue-200 border border-blue-200 text-blue-700 px-3 rounded-r text-sm font-bold transition" title="Wgraj Plik">⬆️</button>
    </div>`;
}

let activePickerInputId = null;
window.openMediaPicker = function(inputId) {
    activePickerInputId = inputId;
    document.getElementById('mediaPickerModal').classList.remove('hidden');
    document.getElementById('mediaPickerFrame').src = '/admin/media?picker=1';
};

window.closeMediaPicker = function() {
    document.getElementById('mediaPickerModal').classList.add('hidden');
    document.getElementById('mediaPickerFrame').src = '';
};

window.addEventListener('message', function(event) {
    if (event.data && event.data.type === 'media_selected') {
        if (activePickerInputId) {
            const inputEl = document.getElementById(activePickerInputId);
            if(inputEl) {
                inputEl.value = event.data.url;
                inputEl.dispatchEvent(new Event('input')); 
            }
        }
        window.closeMediaPicker();
    }
});

// ==========================================
// 6. INITIALIZATION (URUCHOMIENIE SKRYPTU NA KOŃCU)
// ==========================================

const sidebar = document.getElementById('block-sidebar');
if (sidebar) {
    Sortable.create(sidebar, {
        group: {
            name: 'shared',
            pull: 'clone', 
            put: false     
        },
        sort: false,       
        animation: 150
    });
}

const zones = document.querySelectorAll('.drop-zone[data-zone-uid]');

if (zones.length > 0) {
    zones.forEach(zone => {
        initSortable(zone);
        const uid = zone.dataset.zoneUid;
        if (savedContent && !Array.isArray(savedContent) && savedContent[uid]) {
            renderRecursive(savedContent[uid], zone);
        } else if (uid === 'editor' && Array.isArray(savedContent)) {
            renderRecursive(savedContent, zone);
        }
    });
} else {
    const editor = document.getElementById('editor');
    if (editor) {
        initSortable(editor);
        let blocksToRender = [];
        if (Array.isArray(savedContent)) {
            blocksToRender = savedContent;
        } else if (savedContent && savedContent['editor']) {
            blocksToRender = savedContent['editor'];
        }
        renderRecursive(blocksToRender, editor);
    }
}

updateNavigator();