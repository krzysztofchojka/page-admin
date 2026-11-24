const formId = window.CMS_CONFIG.formId;
const savedFields = window.CMS_CONFIG.savedFields;
const canvas = window.CMS_CONFIG.canvas;
const savedSettings = window.CMS_CONFIG.savedSettings;

document.getElementById('reqLogin').checked = savedSettings.reqLogin || false;
document.getElementById('fillOnce').checked = savedSettings.fillOnce || false;
document.getElementById('editable').checked = savedSettings.editable || false;
if (document.getElementById('lockFilled')) document.getElementById('lockFilled').checked = savedSettings.lockFilled || false;
if (document.getElementById('formStyle')) document.getElementById('formStyle').value = savedSettings.style || 'default';
if (document.getElementById('submitLabel')) document.getElementById('submitLabel').value = savedSettings.submitLabel || '';

const WIDTHS = [['sixth', '1/6'], ['third', '1/3'], ['semi', '1/2'], ['half', '2/3'], ['full', 'Full']];
const NO_VALUE_TYPES = ['html', 'section', 'account', 'honeypot', 'captcha_image', 'captcha_turnstile'];
const escAttr = (v) => String(v ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');

Sortable.create(document.getElementById('form-sidebar'), {
    group: { name: 'shared', pull: 'clone', put: false },
    animation: 150,
    sort: false,
    ghostClass: 'sidebar-ghost'
});

Sortable.create(canvas, {
    group: 'shared',
    animation: 150,
    ghostClass: 'ghost',
    handle: '.drag-handle',
    onAdd: function (evt) {
        const itemEl = evt.item;
        if (itemEl.classList.contains('sidebar-block')) {
            const type = itemEl.dataset.type;
            const defaultWidth = document.getElementById('defaultFieldWidth').value;
            const newField = createFieldElement({ type: type, label: ['html', 'account'].includes(type) ? '' : (type === 'section' ? 'NOWA SEKCJA' : 'Nowe pole'), width: type === 'section' ? 'full' : defaultWidth, required: false });
            itemEl.parentNode.insertBefore(newField, itemEl);
            itemEl.parentNode.removeChild(itemEl);
        }
    }
});

savedFields.forEach(field => {
    canvas.appendChild(createFieldElement(field));
});

function createFieldElement(data) {
    const div = document.createElement('div');
    div.className = "field-card relative border border-gray-200 p-4 rounded-xl bg-white shadow-sm hover:border-blue-400 transition-all group col-span-1";
    
    const fieldId = data.custom_id || data.id || 'f_' + Math.random().toString(36).substr(2, 9);
    div.dataset.type = data.type;
    div.dataset.width = data.width || 'full';
    div.dataset.id = fieldId;
    updateWidthClasses(div, div.dataset.width);

    const isReq = data.required ? 'checked' : '';

    let visualHTML = '';
    let extraSettings = '';
    if(['text', 'email', 'phone'].includes(data.type)) {
        visualHTML = `<div class="mt-3 w-full h-9 bg-gray-50 border border-gray-200 rounded pointer-events-none"></div>`;
    } else if (data.type === 'textarea') {
        visualHTML = `<div class="mt-3 w-full h-16 bg-gray-50 border border-gray-200 rounded pointer-events-none"></div>`;
    } else if (data.type === 'select') {
        visualHTML = `<div class="mt-3 w-full h-9 bg-gray-50 border border-gray-200 rounded flex items-center justify-between px-3 text-gray-400 pointer-events-none"><span class="text-xs">Wybierz opcję...</span><span class="text-[10px]"><i class=i-chevron-down></i></span></div>`;
    } else if (data.type === 'radio' || data.type === 'checkbox') {
        const icon = data.type === 'radio' ? '<i class=i-circle></i>' : '<i class=i-square></i>';
        visualHTML = `<div class="mt-3 flex flex-col gap-1.5 pointer-events-none text-gray-400 text-xs font-mono ml-1">
            <div class="flex items-center gap-2"><span class="text-base leading-none">${icon}</span> Przykładowa opcja 1</div>
            <div class="flex items-center gap-2"><span class="text-base leading-none">${icon}</span> Przykładowa opcja 2</div>
        </div>`;
    } else if (data.type === 'file') {
        visualHTML = `<div class="mt-3 w-full h-12 border-2 border-dashed border-gray-300 bg-gray-50 rounded flex items-center justify-center text-gray-400 text-xs font-bold pointer-events-none"><i class=i-paperclip></i> Upuść plik tutaj</div>`;
        extraSettings = `
        <div class="mt-2 mb-2">
            <label class="text-xs font-bold text-gray-500 block">Dozwolone rozszerzenia (po przecinku)</label>
            <input type="text" class="field-allowed-exts w-full border p-2 rounded text-sm mt-1 bg-gray-50" placeholder="jpg, png, pdf, zip" value="${data.allowedExts || 'jpg, png, pdf, zip, doc, docx'}">
        </div>
        <div class="mt-2">
            <label class="text-xs font-bold text-gray-500 block">Maksymalny rozmiar (MB)</label>
            <input type="number" class="field-max-size w-full border p-2 rounded text-sm mt-1 bg-gray-50" placeholder="Zostaw puste dla max z serwera" value="${data.maxSize || ''}">
        </div>`;
    } else if (data.type === 'html') {
        visualHTML = `<div class="mt-3 w-full h-12 bg-gray-800 rounded flex items-center justify-center text-green-400 text-xs font-mono pointer-events-none">&lt; KOD HTML /&gt;</div>`;
    } else if (data.type === 'section') {
        visualHTML = `<div class="mt-2 border-t-2 border-indigo-300"></div>`;
        extraSettings = `
        <div class="mt-2">
            <label class="text-xs font-bold text-gray-500 block">Opis pod nagłówkiem (opcjonalnie)</label>
            <textarea class="field-desc w-full border p-2 rounded text-sm mt-1 bg-gray-50" rows="2" placeholder="np. Paszport musi być ważny min. 6 miesięcy">${escAttr(data.desc)}</textarea>
        </div>`;
    } else if (data.type === 'account') {
        visualHTML = `<div class="mt-3 w-full h-9 bg-gray-200 border border-gray-300 rounded flex items-center px-3 text-gray-500 text-xs pointer-events-none"><i class=i-user></i> e-mail zalogowanego użytkownika (tylko odczyt)</div>`;
    } else if (data.type === 'date') {
        visualHTML = `<div class="mt-3 w-full h-9 bg-gray-50 border border-gray-200 rounded pointer-events-none"></div>`;
    }

    if (['text', 'email', 'phone', 'textarea', 'date', 'select'].includes(data.type)) {
        extraSettings += `
        <div class="mt-2">
            <label class="text-xs font-bold text-gray-500 block">Podpowiedź w polu (placeholder)</label>
            <input type="text" class="field-placeholder w-full border p-2 rounded text-sm mt-1 bg-gray-50" placeholder="np. jeśli brak – pozostaw puste" value="${escAttr(data.placeholder)}">
        </div>`;
    }

    if (['select', 'radio', 'checkbox'].includes(data.type)) {
        extraSettings = `
            <div class="mt-3">
                <textarea class="field-options hidden">${data.options || ''}</textarea>
                <button type="button" onclick="openOptionsEditor(this)" class="w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold py-2 rounded shadow-sm transition"><i class=i-settings></i> Edytor opcji wyboru i limitów</button>
            </div>
            ${data.type !== 'select' ? `<label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer mt-2"><input type="checkbox" class="field-inline" ${data.inline ? 'checked' : ''}> Opcje w jednej linii (np. Tak / Nie)</label>` : ''}
        ` + extraSettings;
    }
    if (data.type === 'html') {
        extraSettings = `
            <div class="mt-2">
                <label class="text-xs font-bold text-gray-500 block">Kod HTML / Tekst do wyświetlenia</label>
                <textarea class="field-html w-full border p-2 rounded text-sm mt-1 font-mono bg-gray-50" rows="3">${data.html || ''}</textarea>
            </div>
        `;
    }

    // Required checkbox doesn't apply to pure HTML
    const requiredCheckbox = !NO_VALUE_TYPES.includes(data.type) ? `
        <div class="mt-3 border-t border-gray-200 pt-3 flex flex-col gap-2">
            <label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer">
                <input type="checkbox" class="field-required rounded border-gray-300 text-blue-600 focus:ring-blue-500" ${isReq}>
                Wymagane pole (użytkownik musi wypełnić)
            </label>
            <label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer" title="Działa, gdy w ustawieniach formularza włączone jest 'Uzupełnianie danych'">
                <input type="checkbox" class="field-always-editable rounded border-gray-300 text-blue-600" ${data.alwaysEditable ? 'checked' : ''}>
                <i class=i-lock-open></i> Zawsze edytowalne (nie blokuj po wysłaniu)
            </label>
        </div>
    ` : '';

    div.innerHTML = `
        <div class="flex flex-wrap justify-between items-center gap-1 mb-3">
            <span class="text-[10px] font-bold uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded cursor-move drag-handle"><i class=i-grip-vertical></i> ${data.type}</span>
            <div class="flex flex-wrap gap-1">
                <div class="width-btns flex bg-gray-100 p-0.5 rounded border border-gray-200">
                    ${WIDTHS.map(([w, l]) => `<button type="button" data-w="${w}" onclick="setWidth(this.closest('.field-card'), '${w}')" class="px-1.5 py-0.5 text-[10px] font-bold rounded hover:bg-white hover:shadow-sm ${(data.width || 'full') === w ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500'}">${l}</button>`).join('')}
                </div>
                <div class="flex gap-1">
                    <button type="button" onclick="this.closest('.field-card').querySelector('.settings-panel').classList.toggle('hidden')" class="p-1.5 text-xs bg-white hover:bg-gray-100 rounded border shadow-sm" title="Ustawienia zaawansowane"><i class=i-settings></i></button>
                    <button type="button" onclick="this.closest('.field-card').remove()" class="p-1.5 text-xs text-red-500 bg-white hover:bg-red-50 rounded border shadow-sm" title="Usuń pole"><i class=i-x></i></button>
                </div>
            </div>
        </div>

        <div>
            <input type="text" class="field-label w-full font-bold bg-transparent border-b border-gray-200 focus:border-blue-500 outline-none text-base ${data.type === 'section' ? 'text-indigo-800 uppercase' : 'text-gray-800'}" value="${escAttr(data.label)}" placeholder="${data.type === 'html' ? 'Opis/Etykieta (dla Ciebie)' : (data.type === 'section' ? 'Tytuł sekcji, np. DANE PASZPORTU' : 'Wpisz pytanie / etykietę pola (puste = bez etykiety)')}">
        </div>
        
        ${visualHTML}
        
        <div class="settings-panel hidden bg-gray-50 p-3 rounded border border-gray-200 mt-4 text-sm relative z-0">
            <div>
                <label class="text-xs font-bold text-gray-500 block">ID Pola w Bazie oraz HTML (id)</label>
                <input type="text" class="field-custom-id w-full border p-1.5 rounded mt-1 text-xs font-mono bg-white" value="${fieldId}">
                <p class="text-[10px] text-gray-400 mt-1">Nadaj własne ID jeśli korzystasz z własnego HTML/CSS i musisz łatwo namierzać to pole (id="xyz").</p>
            </div>
            ${extraSettings}
            ${requiredCheckbox}
        </div>
    `;
    return div;
}

function setWidth(card, width) {
    card.dataset.width = width;
    updateWidthClasses(card, width);
    card.querySelectorAll('.width-btns button').forEach(b => {
        const on = b.dataset.w === width;
        b.classList.toggle('bg-white', on); b.classList.toggle('shadow-sm', on); b.classList.toggle('text-blue-600', on);
        b.classList.toggle('text-gray-500', !on);
    });
}

// Podgląd w kreatorze: siatka 6-kolumnowa (tak jak na stronie publicznej)
function updateWidthClasses(el, width) {
    el.classList.remove('md:col-span-1', 'md:col-span-2', 'md:col-span-3', 'md:col-span-4', 'md:col-span-6');
    const map = { sixth: 'md:col-span-1', third: 'md:col-span-2', semi: 'md:col-span-3', half: 'md:col-span-4', full: 'md:col-span-6' };
    el.classList.add(map[width] || 'md:col-span-6');
}

let activeOptionsTextarea = null;

function openOptionsEditor(btn) {
    activeOptionsTextarea = btn.previousElementSibling;
    const rawData = activeOptionsTextarea.value;
    const list = document.getElementById('options-modal-list');
    list.innerHTML = '';
    
    if(rawData) {
        const lines = rawData.split('\n');
        lines.forEach(line => {
            if(!line.trim()) return;
            const parts = line.split('|limit:');
            addOptionRow(parts[0].trim(), parts[1] ? parts[1].trim() : '');
        });
    } else {
        addOptionRow();
    }
    document.getElementById('options-modal').classList.remove('hidden');
}

function closeOptionsModal() {
    document.getElementById('options-modal').classList.add('hidden');
    activeOptionsTextarea = null;
}

function addOptionRow(label = '', limit = '') {
    const tr = document.createElement('tr');
    tr.className = "border-b border-gray-100 group";
    tr.innerHTML = `
        <td class="py-1.5 pr-2"><input type="text" class="opt-label w-full border border-gray-300 focus:border-blue-500 rounded p-1.5 text-sm outline-none" placeholder="Nazwa wyświetlana" value="${label}"></td>
        <td class="py-1.5 pr-2"><input type="number" class="opt-limit w-full border border-gray-300 focus:border-blue-500 rounded p-1.5 text-sm outline-none" placeholder="Bez limitu" value="${limit}"></td>
        <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 font-bold w-6 h-6 rounded hover:bg-red-50 transition"><i class=i-x></i></button></td>
    `;
    document.getElementById('options-modal-list').appendChild(tr);
}

function saveOptionsModal() {
    const rows = document.querySelectorAll('#options-modal-list tr');
    let result = [];
    rows.forEach(tr => {
        const label = tr.querySelector('.opt-label').value.trim();
        const limit = tr.querySelector('.opt-limit').value.trim();
        if(label) {
            let line = label;
            if(limit && limit > 0) line += '|limit:' + limit;
            result.push(line);
        }
    });
    if(activeOptionsTextarea) activeOptionsTextarea.value = result.join('\n');
    closeOptionsModal();
}

function saveForm() {
    const fields = [];
    canvas.querySelectorAll('.field-card').forEach(card => {
        const type = card.dataset.type;
        const reqCheck = card.querySelector('.field-required');
        const field = {
            id: card.dataset.id,
            custom_id: card.querySelector('.field-custom-id').value || card.dataset.id,
            type: type,
            label: card.querySelector('.field-label').value,
            width: card.dataset.width,
            required: reqCheck ? reqCheck.checked : false
        };
        
        if (['select', 'radio', 'checkbox'].includes(type)) {
            field.options = card.querySelector('.field-options').value;
        }
        if (type === 'html') {
            field.html = card.querySelector('.field-html').value;
        }
        if (type === 'section') {
            field.desc = card.querySelector('.field-desc').value;
        }
        const ph = card.querySelector('.field-placeholder');
        if (ph && ph.value.trim()) field.placeholder = ph.value.trim();
        const inl = card.querySelector('.field-inline');
        if (inl && inl.checked) field.inline = true;
        const ae = card.querySelector('.field-always-editable');
        if (ae && ae.checked) field.alwaysEditable = true;
        if (type === 'file') {
            field.allowedExts = card.querySelector('.field-allowed-exts') ? card.querySelector('.field-allowed-exts').value : 'jpg, png, pdf, zip, doc, docx';
            field.maxSize = card.querySelector('.field-max-size') ? card.querySelector('.field-max-size').value : '';
        }
        fields.push(field);
    });

    const settings = {
        reqLogin: document.getElementById('reqLogin').checked,
        fillOnce: document.getElementById('fillOnce').checked,
        editable: document.getElementById('editable').checked,
        lockFilled: document.getElementById('lockFilled') ? document.getElementById('lockFilled').checked : false,
        style: document.getElementById('formStyle') ? document.getElementById('formStyle').value : 'default',
        submitLabel: document.getElementById('submitLabel') ? document.getElementById('submitLabel').value : ''
    };

    fetch('/admin/forms/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: formId,
            title: document.getElementById('formTitle').value,
            fields: fields,
            settings: settings
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
            const btn = document.querySelector('button[onclick="saveForm()"]');
            const oldTxt = btn.innerText;
            btn.innerText = 'Zapisano pomyślnie!';
            setTimeout(() => btn.innerText = oldTxt, 2500);
        }
    });
}