const pageId = window.CMS_CONFIG.pageId;
const savedContent = window.CMS_CONFIG.savedContent;
const availableForms = window.CMS_CONFIG.availableForms;
const availableGalleries = window.CMS_CONFIG.availableGalleries;
const availableCategories = window.CMS_CONFIG.availableCategories || [];

// Definicja obrazka zastępczego
const defaultPlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='400'%3E%3Crect width='100%25' height='100%25' fill='%23f3f4f6'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='sans-serif' font-size='20' fill='%239ca3af'%3EWybierz lub wgraj obraz%3C/text%3E%3C/svg%3E";

const blockIcons = { text: '<i class=i-type></i>', image: '<i class=i-image></i>', video: '<i class=i-video></i>', button: '<i class=i-mouse-pointer-click></i>', divider: '<i class=i-separator-horizontal></i>', quote: '<i class=i-quote></i>', columns_2: '<i class=i-columns-2></i>', columns_3: '<i class=i-columns-3></i>', banner: '<i class=i-panel-top></i>', image_cards: '<i class=i-layout-grid></i>', carousel: '<i class=i-gallery-horizontal-end></i>', accordion: '<i class=i-list-collapse></i>', map: '<i class=i-map-pin></i>', countdown: '<i class=i-loader-circle></i>', table: '<i class=i-table></i>', flight: '<i class=i-plane></i>', form: '<i class=i-clipboard-list></i>', gallery: '<i class=i-images></i>', raw_html: '<i class=i-code-xml></i>', system_login: '<i class=i-lock-keyhole></i>', system_register: '<i class=i-clipboard-list></i>', system_change_password: '<i class=i-key-round></i>', system_lockdown: '<i class=i-construction></i>', posts_grid: '<i class=i-newspaper></i>', section: '<i class=i-rectangle-horizontal></i>', grid: '<i class=i-layout-grid></i>', heading: '<i class=i-heading></i>', ticker: '<i class=i-megaphone></i>', switcher: '<i class=i-arrow-left-right></i>', schedule: '<i class=i-calendar-clock></i>', translate: '<i class=i-languages></i>' };
const blockNames = { text: 'Tekst', image: 'Obrazek', video: 'Wideo', button: 'Przycisk', divider: 'Odstęp', quote: 'Cytat', columns_2: '2 Kolumny', columns_3: '3 Kolumny', banner: 'Baner', image_cards: 'Siatka Kart', carousel: 'Karuzela', accordion: 'Akordeon', map: 'Mapa', countdown: 'Odliczanie', table: 'Tabela', flight: 'Loty', form: 'Formularz', gallery: 'Galeria', raw_html: 'HTML', system_login: 'Logowanie', system_register: 'Rejestracja', system_change_password: 'Zmień Hasło', system_lockdown: 'Lockdown', posts_grid: 'Posty', section: 'Sekcja', grid: 'Siatka', heading: 'Nagłówek', ticker: 'Pasek NEWS', switcher: 'Przełącznik', schedule: 'Harmonogram', translate: 'Tłumacz' };

// ==========================================
// 0. HELPERY (formatowanie, pola wyboru, kolory)
// ==========================================

function esc(v) {
    return String(v === undefined || v === null ? '' : v)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function selectTpl(cls, value, options) {
    return `<select class="${cls} w-full border p-2 rounded bg-white text-sm">` +
        options.map(([v, l]) => `<option value="${esc(v)}" ${String(value ?? '') === String(v) ? 'selected' : ''}>${l}</option>`).join('') +
        `</select>`;
}

function colorInputTpl(cls, value = '', placeholder = 'brak') {
    const hex = /^#[0-9a-f]{6}$/i.test(value || '') ? value : '#ffffff';
    return `<div class="flex items-center gap-1">
        <input type="color" value="${hex}" class="w-9 h-9 p-0.5 border rounded cursor-pointer shrink-0 bg-white" title="Wybierz kolor" oninput="this.nextElementSibling.value=this.value; this.nextElementSibling.dispatchEvent(new Event('input', {bubbles:true}))">
        <input type="text" class="${cls} w-full border p-2 rounded text-xs font-mono" placeholder="${placeholder}" value="${esc(value)}">
    </div>`;
}

function fieldTpl(label, inner, extra = '') {
    return `<div class="${extra}"><label class="block text-[11px] font-bold text-gray-500 mb-1">${label}</label>${inner}</div>`;
}

function checkTpl(cls, checked, label) {
    return `<label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer select-none"><input type="checkbox" class="${cls}" ${checked ? 'checked' : ''}> ${label}</label>`;
}

const FMT_FIELDS = ['align', 'maxw', 'pad', 'mt', 'mb', 'bg', 'color', 'radius', 'shadow', 'show', 'pos'];
const SPACE_OPTS = [['', '— domyślny —'], ['none', 'Brak'], ['sm', 'Mały'], ['md', 'Średni'], ['lg', 'Duży'], ['xl', 'Bardzo duży']];

function formatPanelTpl(s) {
    return `
        <div class="text-[10px] font-bold uppercase text-blue-700 mt-4 mb-2 border-t border-blue-200 pt-3 tracking-wider"><i class=i-palette></i> Formatowanie i układ (działa na telefonach i komputerach)</div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            ${fieldTpl('Wyrównanie tekstu', selectTpl('set-align', s.align, [['', '— domyślne —'], ['left', 'Do lewej'], ['center', 'Wyśrodkowane'], ['right', 'Do prawej'], ['justify', 'Wyjustowane']]))}
            ${fieldTpl('Maks. szerokość', selectTpl('set-maxw', s.maxw, [['', 'Pełna'], ['sm', 'Wąska (640px)'], ['md', 'Średnia (768px)'], ['lg', 'Szeroka (1024px)'], ['xl', 'Bardzo szeroka (1200px)'], ['2xl', '1400px']]))}
            ${fieldTpl('Wewnętrzny odstęp', selectTpl('set-pad', s.pad, SPACE_OPTS))}
            ${fieldTpl('Odstęp nad / pod', `<div class="flex gap-1">${selectTpl('set-mt', s.mt, SPACE_OPTS)}${selectTpl('set-mb', s.mb, SPACE_OPTS)}</div>`)}
            ${fieldTpl('Kolor tła', colorInputTpl('set-bg', s.bg))}
            ${fieldTpl('Kolor tekstu', colorInputTpl('set-color', s.color))}
            ${fieldTpl('Zaokrąglenie', selectTpl('set-radius', s.radius, [['', 'Brak'], ['sm', 'Małe'], ['md', 'Średnie'], ['lg', 'Duże'], ['xl', 'Bardzo duże'], ['full', 'Pigułka']]))}
            ${fieldTpl('Cień', selectTpl('set-shadow', s.shadow, [['', 'Brak'], ['sm', 'Delikatny'], ['md', 'Średni'], ['lg', 'Mocny']]))}
            ${fieldTpl('Widoczność', selectTpl('set-show', s.show, [['', 'Wszędzie'], ['desktop', 'Tylko komputer'], ['mobile', 'Tylko telefon']]))}
            ${fieldTpl('Pozycja', selectTpl('set-pos', s.pos, [['', 'Normalna'], ['sticky', 'Przyklejona u góry (sticky)'], ['fixed-bl', 'Stała: lewy dół'], ['fixed-br', 'Stała: prawy dół'], ['fixed-tl', 'Stała: lewa góra'], ['fixed-tr', 'Stała: prawa góra']]))}
        </div>`;
}

function readSettings(el) {
    const p = el.querySelector(':scope > .block-settings-panel');
    if (!p) return {};
    const s = {
        id: p.querySelector('.set-id').value,
        css: p.querySelector('.set-css').value,
        style: p.querySelector('.set-style').value
    };
    FMT_FIELDS.forEach(f => {
        const inp = p.querySelector('.set-' + f);
        if (inp && inp.value !== '') s[f] = inp.value;
    });
    return s;
}

// Starsze zapisy mogły dublować zakładki wewnętrznych karuzel w karuzeli nadrzędnej
function filterNestedCarouselTabs(tabs) {
    if (!Array.isArray(tabs)) return [];
    const nested = new Set();
    const walk = (blocks) => {
        if (!Array.isArray(blocks)) return;
        blocks.forEach(b => {
            if (!b) return;
            if (b.type === 'carousel' && b.content && Array.isArray(b.content.tabs)) b.content.tabs.forEach(t => nested.add(t.label || ''));
            if (b.type === 'switcher' && b.content && Array.isArray(b.content.tabs)) b.content.tabs.forEach(t => walk(t.children));
            if (Array.isArray(b.children)) walk(b.children);
            else if (b.children && typeof b.children === 'object') Object.values(b.children).forEach(walk);
        });
    };
    tabs.forEach(t => walk(t.children));
    return tabs.filter(t => !nested.has(t.label || ''));
}

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
        const icon = blockIcons[type] || '<i class=i-puzzle></i>';
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
                    <span class="text-gray-300 text-[10px] drag-handle-nav opacity-0 group-hover:opacity-100 transition px-1"><i class=i-grip-vertical></i></span>
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
                if(dz.className.includes('tab-content')) {
                    const lbl = dz.closest('.carousel-tab')?.querySelector('.tab-label')?.value;
                    zoneName = lbl ? 'Zakładka: ' + lbl : 'Zakładka';
                }
                if(dz.className.includes('intro-content')) zoneName = 'Ekran startowy';
                if(dz.className.includes('section-content')) zoneName = 'Zawartość sekcji';
                if(dz.className.includes('grid-cell')) zoneName = 'Komórka ' + (Array.from(dz.parentElement.children).indexOf(dz) + 1);
                if(dz.className.includes('switcher-content')) {
                    const lbl = dz.closest('.switcher-tab')?.querySelector('.sw-label')?.value;
                    zoneName = 'Wariant: ' + (lbl || '');
                }

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
        if (!block || !block.type) return;
        if (block.type === 'carousel' && block.content && Array.isArray(block.content.tabs)) {
            block.content.tabs = filterNestedCarouselTabs(block.content.tabs);
        }
        const el = renderBlock(block.type, block.content, block.settings);
        container.appendChild(el);

        if (block.type === 'section' && Array.isArray(block.children)) {
            renderRecursive(block.children, el.querySelector(':scope > .section-content'));
        }
        if (block.type === 'grid' && block.children && typeof block.children === 'object') {
            el.querySelectorAll(':scope > .grid-cells > .grid-cell').forEach((cell, i) => {
                if (Array.isArray(block.children['col_' + i])) renderRecursive(block.children['col_' + i], cell);
            });
        }
        if (block.type === 'switcher' && block.content && Array.isArray(block.content.tabs)) {
            el.querySelectorAll(':scope > .switcher-tabs-container > .switcher-tab').forEach((tabEl, i) => {
                const t = block.content.tabs[i];
                if (t && Array.isArray(t.children)) renderRecursive(t.children, tabEl.querySelector(':scope > .switcher-content'));
            });
        }
        if (block.type === 'carousel' && block.content && Array.isArray(block.content.intro)) {
            renderRecursive(block.content.intro, el.querySelector(':scope > .carousel-intro-wrap > .intro-content'));
        }

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
            const tabZones = el.querySelectorAll(':scope > .carousel-tabs-container > .carousel-tab > .tab-content');
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
    Size.whitelist = ['12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px', '40px', '48px'];
    Quill.register(Size, true);
}
const Align = typeof Quill !== 'undefined' ? Quill.import('attributors/style/align') : null;
if(Align) Quill.register(Align, true);
// Krój pisma jako styl inline (działa na stronie publicznej bez dodatkowych klas CSS)
const Font = typeof Quill !== 'undefined' ? Quill.import('attributors/style/font') : null;
if(Font) {
    Font.whitelist = ['lato', 'montserrat', 'arial', 'georgia', 'monospace'];
    Quill.register(Font, true);
}

// Quill nie obsługuje "miękkich" <br> wewnątrz akapitu i po cichu je usuwa
// (np. w treściach wklejonych ze starych stron). Rozbijamy je na osobne akapity.
function splitSoftBreaks(html) {
    if (!html || html.indexOf('<br') === -1) return html;
    const tpl = document.createElement('template');
    tpl.innerHTML = html;
    tpl.content.querySelectorAll('p, h1, h2, h3, h4, h5, h6').forEach(block => {
        const hasInnerBr = Array.from(block.childNodes).some((n, i, arr) => n.nodeName === 'BR' && i < arr.length - 1);
        if (!hasInnerBr) return;
        const parts = [[]];
        block.childNodes.forEach(n => { if (n.nodeName === 'BR') parts.push([]); else parts[parts.length - 1].push(n); });
        const frag = document.createDocumentFragment();
        parts.forEach(nodes => {
            const el = block.cloneNode(false);
            nodes.forEach(n => el.appendChild(n.cloneNode(true)));
            if (!el.textContent.trim() && !el.querySelector('img')) el.innerHTML = '<br>';
            frag.appendChild(el);
        });
        block.replaceWith(frag);
    });
    return tpl.innerHTML;
}

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
                    [{ 'header': [1, 2, 3, 4, false] }],
                    [{ 'font': Font ? [false, ...Font.whitelist] : [] }],
                    [{ 'size': Size ? [false, ...Size.whitelist] : [] }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'script': 'sub' }, { 'script': 'super' }],
                    [{ 'align': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'indent': '-1' }, { 'indent': '+1' }],
                    ['blockquote', 'link', 'image', 'video'],
                    ['code-block'],
                    ['clean']
                ],
                handlers: {
                    // Obrazek wgrywany do Mediów zamiast base64 w treści strony
                    'image': function() {
                        const q = this.quill;
                        const inp = document.createElement('input');
                        inp.type = 'file';
                        inp.accept = 'image/*';
                        inp.onchange = () => {
                            const file = inp.files && inp.files[0];
                            if (!file) return;
                            const fd = new FormData();
                            fd.append('file', file);
                            fd.append('shortcut', '1');
                            fetch('/admin/media/upload', { method: 'POST', body: fd })
                                .then(r => r.json())
                                .then(d => {
                                    if (!d.url) { alert('Nie udało się wgrać obrazka.'); return; }
                                    const range = q.getSelection(true);
                                    q.insertEmbed(range ? range.index : q.getLength(), 'image', d.url, 'user');
                                })
                                .catch(() => alert('Błąd wgrywania obrazka.'));
                        };
                        inp.click();
                    },
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

    if (content) quill.root.innerHTML = splitSoftBreaks(content);
    // Zapisujemy instancję bezpośrednio w elemencie DOM, żeby ułatwić pobieranie
    element.__quill = quill; 
}

function renderBlock(type, content = '', blockSettings = null) {
    const div = document.createElement('div');
    const uid = 'b_' + Math.random().toString(36).substr(2, 9);
    div.dataset.uid = uid;
    div.dataset.type = type;
    div.className = "group relative border border-gray-200 hover:border-blue-400 rounded-xl p-5 mb-4 bg-white transition-all duration-300 shadow-sm block-item";
    
    const settings = (blockSettings && typeof blockSettings === 'object' && !Array.isArray(blockSettings)) ? blockSettings : { id: '', css: '', style: '' };

    const controls = `
    <div class="absolute -top-3 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity z-50">
        <span class="drag-handle text-gray-600 hover:text-blue-600 p-1.5 bg-gray-100 border border-gray-300 rounded shadow cursor-move" title="Przeciągnij (Drag & Drop)"><i class=i-grip-vertical></i> Przesuń</span>
        <button type="button" onclick="this.parentElement.nextElementSibling.classList.toggle('hidden')" class="text-gray-600 hover:text-gray-800 p-1.5 bg-gray-100 border border-gray-300 rounded shadow" title="Ustawienia (ID, Klasy CSS)"><i class=i-settings></i></button>
        <button type="button" onclick="if(confirm('Na pewno usunąć ten blok?')) { this.closest('.block-item').remove(); window.updateNavigator(); }" class="text-red-500 hover:text-red-700 p-1.5 bg-gray-100 border border-gray-300 rounded shadow" title="Usuń blok"><i class=i-trash-2></i></button>
    </div>
    <div class="block-settings-panel hidden bg-blue-50 p-4 mt-6 mb-4 border border-blue-200 rounded text-sm shadow-inner">
        <div class="grid grid-cols-3 gap-4">
            <div><label class="block font-bold text-gray-600 mb-1">ID HTML</label><input type="text" class="set-id w-full border p-2 rounded" placeholder="np. sekcja-1" value="${esc(settings.id)}"></div>
            <div><label class="block font-bold text-gray-600 mb-1">Klasy CSS (Tailwind)</label><input type="text" class="set-css w-full border p-2 rounded" placeholder="np. mt-10 text-center" value="${esc(settings.css)}"></div>
            <div><label class="block font-bold text-gray-600 mb-1">Style wew. (Inline)</label><input type="text" class="set-style w-full border p-2 rounded" placeholder="np. background: #000;" value="${esc(settings.style)}"></div>
        </div>
        ${formatPanelTpl(settings)}
    </div>`;

    let innerHTML = '';

    if (type === 'text') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-blue-500 uppercase"><i class=i-type></i> Tekst / Edytor Wizualny</span></div>
        <div class="bg-gray-50 border rounded-lg no-drag"><div class="quill-editor"></div></div>`;
    } 
    else if (type === 'posts_grid') {
        div.className += " border-2 border-dashed border-purple-200 bg-purple-50/20";
        const data = typeof content === 'object' && content !== null ? content : { limit: 6, category: '' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-purple-600 uppercase"><i class=i-newspaper></i> Siatka Postów (Blog)</span></div>
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
                <label class="text-xs font-mono text-gray-400"><i class=i-code-xml></i> ZAAWANSOWANY KOD HTML</label>
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
        <div class="flex gap-2 mb-3"><span class="text-xs font-bold text-indigo-500 uppercase tracking-wider"><i class=i-columns-2></i> Układ: 2 Kolumny</span></div>
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
        <div class="flex gap-2 mb-3"><span class="text-xs font-bold text-emerald-500 uppercase tracking-wider"><i class=i-columns-3></i> Układ: 3 Kolumny</span></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-green-500 uppercase"><i class=i-image></i> Pojedynczy Obrazek</span></div>
        <div class="mb-3">
            <div class="flex w-full mb-2">
                <input type="text" id="${uniqueId}" class="block-content w-full border border-r-0 p-2 text-sm rounded-l bg-gray-50 focus:bg-white focus:outline-none" placeholder="Adres URL obrazka..." value="${imgValue}" oninput="this.closest('.block-item').querySelector('.img-preview').src = this.value || defaultPlaceholder">
                <button type="button" onclick="window.openMediaPicker('${uniqueId}')" class="bg-purple-100 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition" title="Wybierz z Media"><i class=i-folder-open></i></button>
                <button type="button" onclick="window.triggerInputUpload(this)" class="bg-blue-100 hover:bg-blue-200 border border-blue-200 text-blue-700 px-3 rounded-r text-sm font-bold transition" title="Wgraj Plik"><i class=i-upload></i></button>
            </div>
        </div>
        <div class="bg-gray-100 border border-gray-200 rounded-lg overflow-hidden flex items-center justify-center min-h-[100px]">
            <img src="${hasImg ? imgValue : defaultPlaceholder}" class="img-preview w-full object-contain max-h-[400px]">
        </div>`;
    }
    else if (type === 'linked_image') {
        const data = typeof content === 'object' ? content : { url: '', link: '' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-indigo-600 uppercase"><i class=i-link></i> Podlinkowany Obrazek</span></div>
        <label class="block text-xs font-bold text-gray-500 mb-1">URL Obrazka (Wklej link)</label>
        ${imgInputTpl('block-img-url', 'URL Obrazka', data.url)}
        <label class="block text-xs font-bold text-gray-500 mt-3 mb-1">Link docelowy po kliknięciu</label>
        <input type="text" class="block-link-url w-full border p-2 text-sm rounded bg-gray-50 focus:bg-white" placeholder="https://..." value="${data.link || ''}">`;
    }
    else if (type === 'form') {
        innerHTML = `
        <div class="bg-teal-50 border border-teal-200 p-4 rounded-lg text-center">
            <label class="block text-sm font-bold text-teal-800 mb-2"><i class=i-clipboard-list></i> Wybierz Formularz</label>
            <select class="block-select border rounded p-2 w-full text-center font-bold bg-white shadow-sm focus:ring-2 focus:ring-teal-500">
                ${buildOptions(availableForms, content)}
            </select>
        </div>`;
    }
    else if (type === 'gallery') {
        innerHTML = `
        <div class="bg-pink-50 border border-pink-200 p-4 rounded-lg text-center">
            <label class="block text-sm font-bold text-pink-800 mb-2"><i class=i-images></i> Wybierz Galerię</label>
            <select class="block-select border rounded p-2 w-full text-center font-bold bg-white shadow-sm focus:ring-2 focus:ring-pink-500">
                ${buildOptions(availableGalleries, content)}
            </select>
        </div>`;
    }
    else if (type === 'banner') {
        const data = typeof content === 'object' ? content : { bg: '', title: '', subtitle: '' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-4"><span class="text-xs font-bold text-orange-600 uppercase"><i class=i-panel-top></i> Baner (Hero Section)</span></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase"><i class=i-map-pin></i> Mapa Leaflet</span></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-purple-500 uppercase"><i class=i-loader-circle></i> Odliczanie</span></div>
        <label class="block text-xs text-gray-500 mb-1">Tytuł nad licznikiem</label>
        <input type="text" class="cd-title w-full border p-2 rounded mb-3" placeholder="Do startu wydarzenia:" value="${data.title}">
        <label class="block text-xs text-gray-500 mb-1">Data i godzina zakończenia</label>
        <input type="datetime-local" class="cd-date w-full border p-2 rounded" value="${data.date}">`;
    }
    else if (type === 'table') {
        innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold text-blue-500 uppercase"><i class=i-table></i> Tabela Danych</span>
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
            <span class="text-xs font-bold text-sky-500 uppercase"><i class=i-plane></i> Przeloty</span>
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
                <button type="button" onclick="window.addFlightSegment(this, 'flight')" class="text-xs bg-white border border-sky-300 text-sky-700 px-4 py-2 rounded shadow-sm font-bold hover:bg-sky-100"><i class=i-plane></i> Dodaj Lot</button>
                <button type="button" onclick="window.addFlightSegment(this, 'connection')" class="text-xs bg-white border border-indigo-300 text-indigo-700 px-4 py-2 rounded shadow-sm font-bold hover:bg-indigo-100"><i class=i-timer></i> Dodaj Przesiadkę</button>
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
                    <button type="button" onclick="this.closest('.image-card').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10" title="Ustawienia kafelka"><i class=i-settings></i></button>
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
        <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-orange-500 uppercase"><i class=i-layout-grid></i> Siatka Kart (Obrazek + Tekst)</span></div>
        <div class="cards-container min-h-[5px] mb-3 grid grid-cols-1 md:grid-cols-2 gap-4">
            ${cardsHTML}
        </div>
        <button type="button" onclick="window.addImageCard(this)" class="bg-white border-2 border-orange-500 text-orange-600 hover:bg-orange-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj Kartę</button>`;
    }
    else if (type === 'carousel') {
        div.className += " border-2 border-dashed border-blue-300 bg-blue-50/30";
        const data = (typeof content === 'object' && content) ? content : { arrows: true, tabs: [] };
        const tabsHTML = (data.tabs || []).map(tab => carouselTabTpl(tab)).join('');
        const invert = data.invertIcons === undefined ? true : !!data.invertIcons;
        innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs font-bold text-blue-800 uppercase tracking-wider"><i class=i-gallery-horizontal-end></i> Karuzela / Zakładki</span>
            <label class="text-sm font-bold text-gray-700 flex items-center gap-2"><input type="checkbox" class="carousel-arrows" ${data.arrows ? 'checked' : ''}> Pokaż strzałki</label>
        </div>
        <details class="carousel-opts no-drag bg-white border border-blue-100 rounded-lg p-3 mb-3">
            <summary class="text-xs font-bold text-blue-700 cursor-pointer"><i class=i-palette></i> Wygląd kafelków i panelu</summary>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                ${fieldTpl('Styl kafelków', selectTpl('car-style', data.style || 'default', [['default', 'Standardowy'], ['tiles', 'Duże kafelki (gradient)']]))}
                ${fieldTpl('Panel treści', selectTpl('car-panel', data.panel || 'card', [['card', 'Biała karta'], ['plain', 'Bez tła']]))}
                ${fieldTpl('Kolor kafelka (góra)', colorInputTpl('car-from', data.tileFrom || '', 'auto'))}
                ${fieldTpl('Kolor kafelka (dół)', colorInputTpl('car-to', data.tileTo || '', 'auto'))}
                ${fieldTpl('Kolor aktywnego', colorInputTpl('car-active', data.activeColor || '', 'auto'))}
                ${fieldTpl('Kolor tekstu', colorInputTpl('car-text', data.textColor || '', '#ffffff'))}
                <div class="flex items-end pb-2">${checkTpl('car-invert', invert, 'Odwróć kolory ikon')}</div>
            </div>
            <p class="text-[11px] text-gray-500 mt-2">Każda zakładka ma kotwicę – link <code>#agenda</code> (albo własna kotwica) otworzy ją z dowolnego miejsca strony.</p>
        </details>
        <div class="carousel-intro-wrap mb-3 bg-amber-50 border border-amber-200 rounded-lg p-3">
            <div class="text-xs font-bold text-amber-800 mb-2"><i class=i-flag></i> Ekran startowy <span class="font-normal">(widoczny, zanim użytkownik kliknie kafelek – zostaw pusty, by od razu otwierać 1. zakładkę)</span></div>
            <div class="intro-content drop-zone bg-white border-2 border-dashed border-amber-200 rounded p-4 min-h-[70px]"></div>
        </div>
        <div class="carousel-tabs-container min-h-[5px] mb-3">${tabsHTML}</div>
        <button type="button" onclick="window.addCarouselTab(this)" class="bg-white border-2 border-blue-500 text-blue-600 hover:bg-blue-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj Zakładkę / Kafelek</button>`;
        setTimeout(() => {
            div.querySelectorAll(':scope > .carousel-tabs-container > .carousel-tab > .tab-content').forEach(el => initSortable(el));
            initSortable(div.querySelector(':scope > .carousel-intro-wrap > .intro-content'));
        }, 0);
    }
    else if (type === 'section') {
        div.className += " border-2 border-dashed border-cyan-300 bg-cyan-50/20";
        const d = (typeof content === 'object' && content) ? content : { inner: 'xl', padY: 'md', padX: 'md' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-cyan-700 uppercase tracking-wider"><i class=i-rectangle-horizontal></i> Sekcja / Kontener</span></div>
        <details class="section-opts no-drag bg-white border border-cyan-100 rounded-lg p-3 mb-3" ${content ? '' : 'open'}>
            <summary class="text-xs font-bold text-cyan-700 cursor-pointer"><i class=i-palette></i> Tło, szerokość i wysokość sekcji</summary>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                ${fieldTpl('Kolor tła', colorInputTpl('sec-bgcolor', d.bgColor))}
                ${fieldTpl('Zdjęcie w tle', imgInputTpl('sec-bgimage', 'URL zdjęcia', d.bgImage || ''), 'col-span-2 md:col-span-3')}
                ${fieldTpl('Dopasowanie zdjęcia', selectTpl('sec-bgsize', d.bgSize || 'cover', [['cover', 'Wypełnij (przytnij)'], ['contain', 'Zmieść całe'], ['100% auto', 'Pełna szerokość'], ['auto', 'Oryginalny rozmiar']]))}
                ${fieldTpl('Kadr zdjęcia', selectTpl('sec-bgpos', d.bgPos || 'center', [['center', 'Środek'], ['top', 'Góra'], ['bottom', 'Dół'], ['left', 'Lewo'], ['right', 'Prawo']]))}
                ${fieldTpl('Kadr poziomy (np. 58%)', `<input type="text" class="sec-bgposx w-full border p-2 rounded text-sm" placeholder="np. 58%" value="${esc(d.bgPosX)}">`)}
                ${fieldTpl('Nakładka (kolor)', colorInputTpl('sec-overlay', d.overlay))}
                ${fieldTpl('Krycie nakładki %', `<input type="number" min="0" max="100" class="sec-overlayop w-full border p-2 rounded text-sm" value="${esc(d.overlayOpacity ?? 40)}">`)}
                ${fieldTpl('Kolor tekstu', colorInputTpl('sec-textcolor', d.textColor))}
                ${fieldTpl('Szerokość treści', selectTpl('sec-inner', d.inner || 'xl', [['full', 'Pełna'], ['sm', '640px'], ['md', '768px'], ['lg', '1024px'], ['xl', '1200px'], ['2xl', '1400px']]))}
                ${fieldTpl('Odstęp pionowy', selectTpl('sec-pady', d.padY || 'md', [['none', 'Brak'], ['sm', 'Mały'], ['md', 'Średni'], ['lg', 'Duży'], ['xl', 'Bardzo duży']]))}
                ${fieldTpl('Odstęp poziomy', selectTpl('sec-padx', d.padX || 'md', [['none', 'Brak'], ['sm', 'Mały'], ['md', 'Średni'], ['lg', 'Duży']]))}
                ${fieldTpl('Min. wysokość', `<input type="text" class="sec-minh w-full border p-2 rounded text-sm" placeholder="np. 300px / 50vh" value="${esc(d.minHeight)}">`)}
                ${fieldTpl('Proporcje (szer/wys)', `<input type="text" class="sec-ratio w-full border p-2 rounded text-sm" placeholder="np. 1680/268" value="${esc(d.ratio)}">`)}
                <div class="flex flex-col gap-2 justify-end pb-1">
                    ${checkTpl('sec-fullbleed', d.fullBleed, 'Na całą szerokość ekranu')}
                    ${checkTpl('sec-vcenter', d.vCenter, 'Wyśrodkuj w pionie')}
                    ${checkTpl('sec-bgfixed', d.bgFixed, 'Tło nieruchome (parallax)')}
                </div>
            </div>
        </details>
        <div class="section-content drop-zone bg-white border-2 border-dashed border-cyan-200 rounded p-4 min-h-[100px]"></div>`;
        setTimeout(() => {
            initSortable(div.querySelector(':scope > .section-content'));
            window.sectionPreview(div);
            div.querySelector(':scope > .section-opts').addEventListener('input', () => window.sectionPreview(div));
        }, 0);
    }
    else if (type === 'grid') {
        div.className += " border-2 border-dashed border-violet-300 bg-violet-50/20";
        const d = (typeof content === 'object' && content) ? content : { count: 3, cols: 3, colsTablet: 2, colsMobile: 1, gap: 'md', valign: 'stretch' };
        const count = Math.max(1, Math.min(12, parseInt(d.count || 2, 10)));
        const nums = (max) => Array.from({ length: max }, (_, i) => [String(i + 1), String(i + 1)]);
        let cells = '';
        for (let i = 0; i < count; i++) cells += gridCellTpl();
        innerHTML = `
        <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-violet-700 uppercase tracking-wider"><i class=i-layout-grid></i> Siatka (responsywne kolumny)</span></div>
        <div class="grid-opts no-drag grid grid-cols-3 md:grid-cols-6 gap-2 mb-3 bg-white border border-violet-100 rounded-lg p-3">
            ${fieldTpl('Liczba komórek', `<input type="number" min="1" max="12" class="grid-count w-full border p-2 rounded text-sm" value="${count}" onchange="window.gridSetCount(this.closest('.block-item'))">`)}
            ${fieldTpl('<i class=i-monitor></i> Kolumny', selectTpl('grid-cols', d.cols || count, nums(6)))}
            ${fieldTpl('<i class=i-tablet></i> Tablet', selectTpl('grid-cols-t', d.colsTablet || 2, nums(4)))}
            ${fieldTpl('<i class=i-smartphone></i> Telefon', selectTpl('grid-cols-m', d.colsMobile || 1, nums(3)))}
            ${fieldTpl('Odstęp', selectTpl('grid-gap', d.gap || 'md', [['none', 'Brak'], ['sm', 'Mały'], ['md', 'Średni'], ['lg', 'Duży'], ['xl', 'Bardzo duży']]))}
            ${fieldTpl('Wyrównanie pion.', selectTpl('grid-valign', d.valign || 'stretch', [['stretch', 'Rozciągnij'], ['start', 'Góra'], ['center', 'Środek'], ['end', 'Dół']]))}
        </div>
        <div class="grid-cells grid gap-3">${cells}</div>`;
        setTimeout(() => {
            div.querySelectorAll(':scope > .grid-cells > .grid-cell').forEach(c => initSortable(c));
            window.gridPreview(div);
            div.querySelector(':scope > .grid-opts').addEventListener('change', () => window.gridPreview(div));
        }, 0);
    }
    else if (type === 'heading') {
        const d = (typeof content === 'object' && content) ? content : { text: typeof content === 'string' ? content : '', level: 'h2' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-slate-700 uppercase"><i class=i-heading></i> Nagłówek</span></div>
        <textarea class="hd-text w-full border p-2 rounded text-xl font-bold mb-3 no-drag" rows="2" placeholder="Treść nagłówka (Enter = nowa linia)">${esc(d.text)}</textarea>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            ${fieldTpl('Poziom (SEO)', selectTpl('hd-level', d.level || 'h2', [['h1', 'H1 – tytuł strony'], ['h2', 'H2'], ['h3', 'H3'], ['h4', 'H4'], ['h5', 'H5'], ['h6', 'H6']]))}
            ${fieldTpl('Rozmiar', selectTpl('hd-size', d.size || '', [['', 'Wg poziomu'], ['sm', 'S'], ['md', 'M'], ['lg', 'L'], ['xl', 'XL'], ['2xl', '2XL'], ['3xl', '3XL'], ['4xl', '4XL (hero)']]))}
            ${fieldTpl('Wyrównanie', selectTpl('hd-align', d.align || '', [['', 'Domyślne'], ['left', 'Lewo'], ['center', 'Środek'], ['right', 'Prawo']]))}
            ${fieldTpl('Grubość', selectTpl('hd-weight', d.weight || '700', [['300', 'Cienka'], ['400', 'Normalna'], ['600', 'Półgruba'], ['700', 'Gruba'], ['900', 'Bardzo gruba']]))}
            ${fieldTpl('Krój pisma', selectTpl('hd-font', d.font || '', [['', 'Domyślny strony'], ['lato', 'Lato'], ['montserrat', 'Montserrat'], ['serif', 'Szeryfowy'], ['mono', 'Maszynowy']]))}
            ${fieldTpl('Kolor', colorInputTpl('hd-color', d.color))}
            ${fieldTpl('Rozstrzelenie', selectTpl('hd-spacing', d.spacing || '', [['', 'Normalne'], ['tight', 'Ciasne'], ['wide', 'Szerokie'], ['wider', 'Szersze'], ['widest', 'Najszersze']]))}
            <div class="flex flex-col gap-2 justify-end pb-1">${checkTpl('hd-upper', d.upper, 'WERSALIKI')}${checkTpl('hd-shadow', d.shadow, 'Cień tekstu')}</div>
        </div>`;
    }
    else if (type === 'ticker') {
        const d = (typeof content === 'object' && content) ? content : { label: 'NEWS', items: '', speed: 60, shape: 'bar', bg: '#1e40af' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-blue-700 uppercase"><i class=i-megaphone></i> Pasek wiadomości (NEWS)</span></div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
            ${fieldTpl('Etykieta', `<input type="text" class="tk-label w-full border p-2 rounded text-sm font-bold" value="${esc(d.label ?? 'NEWS')}">`)}
            ${fieldTpl('Kształt', selectTpl('tk-shape', d.shape || 'bar', [['bar', 'Pasek'], ['tab', 'Zakładka (zaokrąglony dół)'], ['pill', 'Pigułka']]))}
            ${fieldTpl('Prędkość (px/s)', `<input type="number" min="10" max="300" class="tk-speed w-full border p-2 rounded text-sm" value="${esc(d.speed || 60)}">`)}
            ${fieldTpl('Separator', `<input type="text" maxlength="3" class="tk-sep w-full border p-2 rounded text-sm" value="${esc(d.separator ?? '•')}">`)}
            ${fieldTpl('Tło', colorInputTpl('tk-bg', d.bg))}
            ${fieldTpl('Tło – gradient do', colorInputTpl('tk-bg2', d.bg2))}
            ${fieldTpl('Kolor tekstu', colorInputTpl('tk-color', d.color, '#ffffff'))}
        </div>
        ${fieldTpl('Wiadomości (każda w nowej linii, można użyć &lt;b&gt; i &lt;a&gt;)', `<textarea class="tk-items w-full border p-2 rounded text-sm h-24 no-drag" placeholder="Zbiórka na lotnisku o 7:00\nPamiętajcie o paszportach!">${esc(d.items)}</textarea>`)}`;
    }
    else if (type === 'switcher') {
        div.className += " border-2 border-dashed border-sky-300 bg-sky-50/20";
        const d = (typeof content === 'object' && content) ? content : { tabs: [{ label: 'Wariant A' }, { label: 'Wariant B' }], remember: true, key: '' };
        const tabsHTML = (d.tabs || []).map(t => switcherTabTpl(t)).join('');
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-sky-700 uppercase tracking-wider"><i class=i-arrow-left-right></i> Przełącznik wariantów (np. Wariant A / Wariant B)</span></div>
        <div class="switcher-opts no-drag grid grid-cols-2 md:grid-cols-6 gap-3 mb-3 bg-white border border-sky-100 rounded-lg p-3">
            ${fieldTpl('Klucz wspólny', `<input type="text" class="sw-key w-full border p-2 rounded text-sm" placeholder="np. grupa" value="${esc(d.key)}" title="Przełączniki z tym samym kluczem zmieniają się razem na całej stronie">`)}
            ${fieldTpl('Wyrównanie', selectTpl('sw-align', d.align || 'center', [['left', 'Lewo'], ['center', 'Środek'], ['right', 'Prawo']]))}
            ${fieldTpl('Kolor przycisku', colorInputTpl('sw-color', d.color, '#0064fa'))}
            ${fieldTpl('Kolor aktywnego', colorInputTpl('sw-active', d.activeColor, '#1e2e65'))}
            ${fieldTpl('Kolor tekstu', colorInputTpl('sw-text', d.textColor, '#ffffff'))}
            <div class="flex items-end pb-2">${checkTpl('sw-remember', d.remember === undefined ? true : d.remember, 'Zapamiętaj wybór')}</div>
        </div>
        <div class="switcher-tabs-container">${tabsHTML}</div>
        <button type="button" onclick="window.addSwitcherTab(this)" class="bg-white border-2 border-sky-500 text-sky-700 hover:bg-sky-50 text-sm px-4 py-2 rounded-lg font-bold w-full transition">+ Dodaj wariant</button>`;
        setTimeout(() => {
            div.querySelectorAll(':scope > .switcher-tabs-container > .switcher-tab > .switcher-content').forEach(z => initSortable(z));
        }, 0);
    }
    else if (type === 'schedule') {
        const d = (typeof content === 'object' && content) ? content : { title: '', rows: '' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-emerald-700 uppercase"><i class=i-calendar-clock></i> Harmonogram / Agenda</span></div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3">
            ${fieldTpl('Tytuł (np. Dzień 1)', `<input type="text" class="sch-title w-full border p-2 rounded text-sm font-bold" value="${esc(d.title)}">`, 'col-span-2')}
            ${fieldTpl('Kolor godzin', colorInputTpl('sch-accent', d.accent, '#1e40af'))}
            <div class="flex items-end pb-2">${checkTpl('sch-striped', d.striped, 'Paski co drugi wiersz')}</div>
        </div>
        ${fieldTpl('Pozycje: <code>godzina | opis</code> – linia bez „|” to śródtytuł', `<textarea class="sch-rows w-full border p-2 rounded text-sm font-mono h-40 no-drag" placeholder="Sesja poranna\n09:00 | Rejestracja uczestników\n10:30 | Przerwa kawowa">${esc(d.rows)}</textarea>`)}`;
    }
    else if (type === 'translate') {
        const d = (typeof content === 'object' && content) ? content : { pageLang: 'pl', langs: 'en,ru,it,es,pl' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-gray-700 uppercase"><i class=i-languages></i> Google Tłumacz</span></div>
        <div class="grid grid-cols-2 gap-3">
            ${fieldTpl('Język strony', `<input type="text" class="tr-page w-full border p-2 rounded text-sm" value="${esc(d.pageLang || 'pl')}">`)}
            ${fieldTpl('Dostępne języki (po przecinku)', `<input type="text" class="tr-langs w-full border p-2 rounded text-sm" value="${esc(d.langs || 'en,pl')}">`)}
        </div>
        <p class="text-[11px] text-gray-500 mt-2">Wskazówka: w <i class=i-settings></i> <i class=i-arrow-right></i> Pozycja ustaw „Stała: prawy dół”, aby widżet był zawsze w rogu ekranu.</p>`;
    }
    else if (type === 'video') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase"><i class=i-video></i> Wideo / Embed</span></div>
        <label class="block text-xs font-bold text-gray-500 mb-1">URL (YouTube / Vimeo / MP4)</label>
        <input type="text" class="block-video w-full border p-2 rounded bg-gray-50 focus:bg-white" placeholder="https://www.youtube.com/watch?v=..." value="${content || ''}">
        <p class="text-xs text-gray-400 mt-1">Wklej pełny link do filmu lub ścieżkę do wgranego pliku .mp4</p>
        `;
    }
    else if (type === 'button') {
        const data = (typeof content === 'object' && content) ? content : { label: 'Kliknij tutaj', url: '', style: 'primary' };
        innerHTML = `
        <div class="flex items-center gap-2 mb-3"><span class="text-xs font-bold text-indigo-500 uppercase"><i class=i-mouse-pointer-click></i> Przycisk / Link</span></div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            ${fieldTpl('Tekst na przycisku', `<input type="text" class="btn-label w-full border p-2 rounded" placeholder="Dowiedz się więcej" value="${esc(data.label)}">`)}
            ${fieldTpl('Adres URL (lub #kotwica)', `<input type="text" class="btn-url w-full border p-2 rounded" placeholder="https://... / #agenda / /logout" value="${esc(data.url)}">`)}
            ${fieldTpl('Styl', selectTpl('btn-style', data.style || 'primary', [['primary', 'Pełny kolor'], ['secondary', 'Obrys'], ['pill', 'Pigułka'], ['tab', 'Zakładka (zaokrąglony dół)'], ['link', 'Zwykły link (tekst)']]))}
            ${fieldTpl('Rozmiar', selectTpl('btn-size', data.size || 'md', [['sm', 'Mały'], ['md', 'Średni'], ['lg', 'Duży']]))}
            ${fieldTpl('Kolor', colorInputTpl('btn-color', data.color, 'kolor główny'))}
            ${fieldTpl('Kolor tekstu', colorInputTpl('btn-textcolor', data.textColor, 'auto'))}
            ${fieldTpl('Pozycja pozioma (%)', `<input type="number" min="0" max="100" step="0.5" class="btn-offset w-full border p-2 rounded" placeholder="wg wyrównania" value="${esc(data.offsetX)}" title="Np. 58 = środek przycisku na 58% szerokości. Skaluje się z ekranem.">`)}
            <div class="flex flex-col gap-2 justify-end pb-1">${checkTpl('btn-full', data.full, 'Na całą szerokość')}${checkTpl('btn-newtab', data.newTab, 'Otwórz w nowej karcie')}</div>
        </div>`;
    }
    else if (type === 'divider') {
        const data = typeof content === 'object' ? content : { height: '8' };
        innerHTML = `
        <div class="flex justify-between items-center"><span class="text-xs font-bold text-gray-400 uppercase"><i class=i-separator-horizontal></i> Separator / Odstęp Pionowy</span></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-yellow-500 uppercase"><i class=i-quote></i> Cytat / Referencje</span></div>
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
        <div class="flex gap-2 mb-2"><span class="text-xs font-bold text-purple-600 uppercase"><i class=i-list-collapse></i> Rozwijany Akordeon (FAQ)</span></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase"><i class=i-lock-keyhole></i> System: Logowanie</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3 text-gray-400"><i class=i-lock-keyhole-thin></i></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase"><i class=i-clipboard-list></i> System: Rejestracja</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3 text-gray-400"><i class=i-clipboard-list-thin></i></div>
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
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase"><i class=i-key-round></i> System: Zmiana Hasła</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3 text-gray-400"><i class=i-key-round-thin></i></div><h4 class="font-bold text-gray-700 text-lg">Moduł Zmiany Hasła</h4>
            <div class="mt-4 bg-white border border-gray-200 rounded p-4 opacity-50 shadow-sm"><div class="h-8 bg-gray-100 rounded mb-2 w-full"></div><div class="h-8 bg-gray-100 rounded mb-4 w-full"></div><div class="h-10 bg-yellow-500 rounded w-full"></div></div>
        </div>`;
    }
    else if (type === 'system_lockdown') {
        innerHTML = `
        <div class="flex items-center gap-2 mb-2"><span class="text-xs font-bold text-red-500 uppercase"><i class=i-construction></i> System: Lockdown</span></div>
        <div class="bg-gray-50 border-2 border-dashed border-gray-300 p-8 rounded-xl max-w-sm mx-auto my-4 text-center pointer-events-none">
            <div class="text-4xl mb-3 text-gray-400"><i class=i-construction-thin></i></div><h4 class="font-bold text-gray-700 text-lg">Moduł Lockdown (Hasło Ogólne)</h4>
            <div class="mt-4 bg-white border border-gray-200 rounded p-4 opacity-50 shadow-sm"><div class="h-8 bg-gray-100 rounded mb-4 w-full"></div><div class="h-10 bg-gray-800 rounded w-full"></div></div>
        </div>`;
    }
    else {
        innerHTML = `<div class="p-4 bg-gray-200 text-center">Brak definicji bloku: ${type}</div>`;
    }

    div.innerHTML = controls + innerHTML;
    
    if(type === 'form' || type === 'gallery') {
        const sel = div.querySelector(':scope > div > .block-select');
        if(sel) sel.value = content || '';
    }
    
    return div;
}

// ==========================================
// 3. EVENTY (DODAWANIE TABÓW, KART)
// ==========================================

function carouselTabTpl(tab = {}) {
    return `
    <div class="carousel-tab border border-gray-200 bg-white p-3 mb-2 rounded-lg shadow-sm relative group">
        <button type="button" onclick="if(confirm('Usunąć zakładkę wraz z zawartością?')) { this.closest('.carousel-tab').remove(); window.updateNavigator(); }" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10 shadow">X</button>
        <div class="flex gap-2 mb-2 w-full">
            <div class="w-1/3">${imgInputTpl('tab-icon', 'Ikona/Emoji', tab.icon || '')}</div>
            <input type="text" class="tab-label w-2/3 border p-2 text-sm rounded font-bold h-[38px]" placeholder="Tytuł zakładki" value="${esc(tab.label)}" oninput="window.updateNavigator()">
        </div>
        <div class="grid grid-cols-2 gap-2 mb-2">
            <input type="text" class="tab-anchor border p-1.5 text-xs rounded" placeholder="Kotwica (auto z tytułu), np. agenda" value="${esc(tab.anchor)}">
            <input type="text" class="tab-link border p-1.5 text-xs rounded" placeholder="Zamiast treści: link (np. /rejestracja)" value="${esc(tab.link)}">
        </div>
        <div class="tab-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded p-4 min-h-[100px]"></div>
    </div>`;
}

window.addCarouselTab = function(btn) {
    const container = btn.previousElementSibling;
    const tmp = document.createElement('div');
    tmp.innerHTML = carouselTabTpl();
    const div = tmp.firstElementChild;
    container.appendChild(div);
    initSortable(div.querySelector(':scope > .tab-content'));
    window.updateNavigator();
};

function switcherTabTpl(tab = {}) {
    return `
    <div class="switcher-tab border border-sky-200 bg-white p-3 mb-2 rounded-lg shadow-sm relative group">
        <button type="button" onclick="if(confirm('Usunąć wariant wraz z zawartością?')) { this.closest('.switcher-tab').remove(); window.updateNavigator(); }" class="absolute -top-2 -right-2 bg-red-500 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10 shadow">X</button>
        <input type="text" class="sw-label w-full border p-2 text-sm rounded font-bold mb-2" placeholder="Napis na przycisku, np. Wariant A" value="${esc(tab.label)}" oninput="window.updateNavigator()">
        <div class="switcher-content drop-zone bg-gray-50 border-2 border-dashed border-gray-200 rounded p-4 min-h-[80px]"></div>
    </div>`;
}

window.addSwitcherTab = function(btn) {
    const container = btn.previousElementSibling;
    const tmp = document.createElement('div');
    tmp.innerHTML = switcherTabTpl({ label: 'WARIANT ' + (container.children.length + 1) });
    const div = tmp.firstElementChild;
    container.appendChild(div);
    initSortable(div.querySelector(':scope > .switcher-content'));
    window.updateNavigator();
};

function gridCellTpl() {
    return `<div class="grid-cell drop-zone bg-white border-2 border-dashed border-violet-200 rounded-lg p-3 min-h-[100px]"></div>`;
}

window.gridSetCount = function(block) {
    const wrap = block.querySelector(':scope > .grid-cells');
    const input = block.querySelector(':scope > .grid-opts .grid-count');
    const target = Math.max(1, Math.min(12, parseInt(input.value, 10) || 1));
    let cells = Array.from(wrap.querySelectorAll(':scope > .grid-cell'));

    while (cells.length > target) {
        const last = cells[cells.length - 1];
        if (last.querySelector('.block-item') && !confirm('Ostatnia komórka zawiera bloki – usunąć ją razem z zawartością?')) {
            input.value = cells.length;
            break;
        }
        last.remove();
        cells.pop();
    }
    while (cells.length < target) {
        const tmp = document.createElement('div');
        tmp.innerHTML = gridCellTpl();
        const cell = tmp.firstElementChild;
        wrap.appendChild(cell);
        initSortable(cell);
        cells.push(cell);
    }
    window.gridPreview(block);
    window.updateNavigator();
};

window.gridPreview = function(block) {
    const wrap = block.querySelector(':scope > .grid-cells');
    const cols = parseInt(block.querySelector(':scope > .grid-opts .grid-cols').value, 10) || 1;
    wrap.style.gridTemplateColumns = `repeat(${cols}, minmax(0, 1fr))`;
};

window.sectionPreview = function(block) {
    const zone = block.querySelector(':scope > .section-content');
    const opts = block.querySelector(':scope > .section-opts');
    if (!zone || !opts) return;
    const v = (c) => (opts.querySelector('.' + c) || {}).value || '';
    const img = v('sec-bgimage');
    zone.style.backgroundColor = v('sec-bgcolor');
    zone.style.backgroundImage = img ? `url("${img.replace(/"/g, '%22')}")` : '';
    zone.style.backgroundSize = v('sec-bgsize') || 'cover';
    zone.style.backgroundPosition = v('sec-bgposx') ? v('sec-bgposx') + ' center' : (v('sec-bgpos') || 'center');
    zone.style.backgroundRepeat = 'no-repeat';
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
    <button type="button" onclick="this.closest('.image-card').querySelector('.item-settings-panel').classList.toggle('hidden')" class="absolute -top-2 right-6 bg-gray-500 hover:bg-gray-700 text-white w-6 h-6 rounded-full text-xs font-bold opacity-0 group-hover:opacity-100 transition z-10" title="Ustawienia kafelka"><i class=i-settings></i></button>
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
        
        const settings = readSettings(el);
        const q = (sel) => el.querySelector(sel);

        if (type === 'section') {
            const o = (c) => (q(':scope > .section-opts .' + c) || {});
            content = {
                bgColor: o('sec-bgcolor').value, bgImage: o('sec-bgimage').value, bgSize: o('sec-bgsize').value,
                bgPos: o('sec-bgpos').value, bgPosX: o('sec-bgposx').value, overlay: o('sec-overlay').value,
                overlayOpacity: o('sec-overlayop').value, textColor: o('sec-textcolor').value, inner: o('sec-inner').value,
                padY: o('sec-pady').value, padX: o('sec-padx').value, minHeight: o('sec-minh').value, ratio: o('sec-ratio').value,
                fullBleed: !!o('sec-fullbleed').checked, vCenter: !!o('sec-vcenter').checked, bgFixed: !!o('sec-bgfixed').checked
            };
            children = getBlocksFromContainer(q(':scope > .section-content'));
        } else if (type === 'grid') {
            const o = (c) => q(':scope > .grid-opts .' + c).value;
            content = { count: o('grid-count'), cols: o('grid-cols'), colsTablet: o('grid-cols-t'), colsMobile: o('grid-cols-m'), gap: o('grid-gap'), valign: o('grid-valign') };
            children = {};
            el.querySelectorAll(':scope > .grid-cells > .grid-cell').forEach((cell, i) => { children['col_' + i] = getBlocksFromContainer(cell); });
        } else if (type === 'heading') {
            content = {
                text: q('.hd-text').value, level: q('.hd-level').value, size: q('.hd-size').value, align: q('.hd-align').value,
                weight: q('.hd-weight').value, font: q('.hd-font').value, color: q('.hd-color').value, spacing: q('.hd-spacing').value,
                upper: q('.hd-upper').checked, shadow: q('.hd-shadow').checked
            };
        } else if (type === 'ticker') {
            content = {
                label: q('.tk-label').value, shape: q('.tk-shape').value, speed: q('.tk-speed').value, separator: q('.tk-sep').value,
                bg: q('.tk-bg').value, bg2: q('.tk-bg2').value, color: q('.tk-color').value, items: q('.tk-items').value
            };
        } else if (type === 'switcher') {
            const o = (c) => q(':scope > .switcher-opts .' + c);
            const tabs = [];
            el.querySelectorAll(':scope > .switcher-tabs-container > .switcher-tab').forEach(t => {
                tabs.push({ label: t.querySelector(':scope > .sw-label').value, children: getBlocksFromContainer(t.querySelector(':scope > .switcher-content')) });
            });
            content = {
                key: o('sw-key').value, align: o('sw-align').value, color: o('sw-color').value, activeColor: o('sw-active').value,
                textColor: o('sw-text').value, remember: o('sw-remember').checked, tabs
            };
        } else if (type === 'schedule') {
            content = { title: q('.sch-title').value, accent: q('.sch-accent').value, striped: q('.sch-striped').checked, rows: q('.sch-rows').value };
        } else if (type === 'translate') {
            content = { pageLang: q('.tr-page').value, langs: q('.tr-langs').value };
        } else if(type === 'columns_2') {
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
            content = el.querySelector(':scope > div > .block-select').value;
        } else if (type === 'linked_image') {
            content = { url: el.querySelector('.block-img-url').value, link: el.querySelector('.block-link-url').value };
        } else if (type === 'banner') {
            content = { bg: el.querySelector('.ban-bg').value, title: el.querySelector('.ban-title').value, subtitle: el.querySelector('.ban-subtitle').value };
        } else if (type === 'carousel') {
            // :scope – tylko WŁASNE zakładki (wcześniej łapało też zakładki zagnieżdżonych karuzel)
            const tabs = [];
            el.querySelectorAll(':scope > .carousel-tabs-container > .carousel-tab').forEach(tabEl => {
                const tab = {
                    icon: tabEl.querySelector('.tab-icon').value,
                    label: tabEl.querySelector('.tab-label').value,
                    children: getBlocksFromContainer(tabEl.querySelector(':scope > .tab-content'))
                };
                const anchor = tabEl.querySelector(':scope > div > .tab-anchor');
                const link = tabEl.querySelector(':scope > div > .tab-link');
                if (anchor && anchor.value) tab.anchor = anchor.value;
                if (link && link.value) tab.link = link.value;
                tabs.push(tab);
            });
            const o = (c) => q(':scope > .carousel-opts .' + c);
            content = {
                arrows: q(':scope > div > label > .carousel-arrows').checked,
                style: o('car-style').value, panel: o('car-panel').value,
                tileFrom: o('car-from').value, tileTo: o('car-to').value, activeColor: o('car-active').value, textColor: o('car-text').value,
                invertIcons: o('car-invert').checked,
                intro: getBlocksFromContainer(q(':scope > .carousel-intro-wrap > .intro-content')),
                tabs: tabs
            };
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
            content = {
                label: q('.btn-label').value, url: q('.btn-url').value, style: q('.btn-style').value, size: q('.btn-size').value,
                color: q('.btn-color').value, textColor: q('.btn-textcolor').value, full: q('.btn-full').checked, newTab: q('.btn-newtab').checked,
                offsetX: q('.btn-offset') ? q('.btn-offset').value : ''
            };
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
    // Tylko strefy główne szablonu – strefy zagnieżdżone (kolumny, zakładki) zapisują się w swoich blokach.
    // Wcześniej trafiały tu też jako osobne klucze "b_xxx_zone_N" i dublowały treść.
    const zones = Array.from(document.querySelectorAll('div[data-zone-uid]')).filter(z => !z.closest('.block-item'));
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

    return fetch('/admin/pages/save', {
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
        inputEl.dispatchEvent(new Event('input', { bubbles: true }));
    }).catch(() => {
        inputEl.value = '';
        inputEl.disabled = false;
    });
}

function imgInputTpl(className, placeholder, value = '') {
    const uniqueId = 'img_in_' + Math.random().toString(36).substr(2, 9);
    return `
    <div class="flex w-full mb-1">
        <input type="text" id="${uniqueId}" class="${className} w-full border border-r-0 p-2 text-sm rounded-l bg-gray-50 focus:bg-white focus:outline-none" placeholder="${placeholder}" value="${esc(value)}" oninput="const p = this.closest('.block-item'); if(p){ const img = p.querySelector('.img-preview'); if(img && img.closest('.block-item') === p) img.src = this.value || defaultPlaceholder; }">
        <button type="button" onclick="window.openMediaPicker('${uniqueId}')" class="bg-purple-100 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition" title="Wybierz z Media"><i class=i-folder-open></i></button>
        <button type="button" onclick="window.triggerInputUpload(this)" class="bg-blue-100 hover:bg-blue-200 border border-blue-200 text-blue-700 px-3 rounded-r text-sm font-bold transition" title="Wgraj Plik"><i class=i-upload></i></button>
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
                inputEl.dispatchEvent(new Event('input', { bubbles: true })); 
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