const formId = window.CMS_CONFIG.formId;
const savedFields = window.CMS_CONFIG.savedFields;
const canvas = window.CMS_CONFIG.canvas;
const savedSettings = window.CMS_CONFIG.savedSettings;

document.getElementById('reqLogin').checked = savedSettings.reqLogin || false;
document.getElementById('fillOnce').checked = savedSettings.fillOnce || false;
document.getElementById('editable').checked = savedSettings.editable || false;

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
            const newField = createFieldElement({ type: type, label: type === 'html' ? '' : 'Nowe pole', width: defaultWidth, required: false });
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
    if(['text', 'email', 'phone'].includes(data.type)) {
        visualHTML = `<div class="mt-3 w-full h-9 bg-gray-50 border border-gray-200 rounded pointer-events-none"></div>`;
    } else if (data.type === 'textarea') {
        visualHTML = `<div class="mt-3 w-full h-16 bg-gray-50 border border-gray-200 rounded pointer-events-none"></div>`;
    } else if (data.type === 'select') {
        visualHTML = `<div class="mt-3 w-full h-9 bg-gray-50 border border-gray-200 rounded flex items-center justify-between px-3 text-gray-400 pointer-events-none"><span class="text-xs">Wybierz opcję...</span><span class="text-[10px]">▼</span></div>`;
    } else if (data.type === 'radio' || data.type === 'checkbox') {
        const icon = data.type === 'radio' ? '○' : '□';
        visualHTML = `<div class="mt-3 flex flex-col gap-1.5 pointer-events-none text-gray-400 text-xs font-mono ml-1">
            <div class="flex items-center gap-2"><span class="text-base leading-none">${icon}</span> Przykładowa opcja 1</div>
            <div class="flex items-center gap-2"><span class="text-base leading-none">${icon}</span> Przykładowa opcja 2</div>
        </div>`;
    } else if (data.type === 'file') {
        visualHTML = `<div class="mt-3 w-full h-12 border-2 border-dashed border-gray-300 bg-gray-50 rounded flex items-center justify-center text-gray-400 text-xs font-bold pointer-events-none">📎 Upuść plik tutaj</div>`;
    } else if (data.type === 'html') {
        visualHTML = `<div class="mt-3 w-full h-12 bg-gray-800 rounded flex items-center justify-center text-green-400 text-xs font-mono pointer-events-none">&lt; KOD HTML /&gt;</div>`;
    }

    let extraSettings = '';
    if (['select', 'radio', 'checkbox'].includes(data.type)) {
        extraSettings = `
            <div class="mt-3">
                <textarea class="field-options hidden">${data.options || ''}</textarea>
                <button type="button" onclick="openOptionsEditor(this)" class="w-full bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold py-2 rounded shadow-sm transition">⚙️ Edytor opcji wyboru i limitów</button>
            </div>
        `;
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
    const requiredCheckbox = data.type !== 'html' ? `
        <div class="mt-3 border-t border-gray-200 pt-3">
            <label class="flex items-center gap-2 text-xs font-bold text-gray-600 cursor-pointer">
                <input type="checkbox" class="field-required rounded border-gray-300 text-blue-600 focus:ring-blue-500" ${isReq}>
                Wymagane pole (użytkownik musi wypełnić)
            </label>
        </div>
    ` : '';

    div.innerHTML = `
        <div class="flex justify-between items-center mb-3">
            <span class="text-[10px] font-bold uppercase text-blue-600 bg-blue-50 px-2 py-1 rounded cursor-move drag-handle">✥ ${data.type}</span>
            <div class="flex gap-2">
                <div class="flex bg-gray-100 p-0.5 rounded border border-gray-200">
                    <button type="button" onclick="setWidth(this.closest('.field-card'), 'third')" class="px-2 py-0.5 text-[10px] font-bold rounded hover:bg-white hover:shadow-sm ${data.width === 'third' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500'}">1/3</button>
                    <button type="button" onclick="setWidth(this.closest('.field-card'), 'half')" class="px-2 py-0.5 text-[10px] font-bold rounded hover:bg-white hover:shadow-sm ${data.width === 'half' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500'}">1/2</button>
                    <button type="button" onclick="setWidth(this.closest('.field-card'), 'full')" class="px-2 py-0.5 text-[10px] font-bold rounded hover:bg-white hover:shadow-sm ${data.width === 'full' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500'}">Full</button>
                </div>
                <div class="flex gap-1">
                    <button type="button" onclick="this.closest('.field-card').querySelector('.settings-panel').classList.toggle('hidden')" class="p-1.5 text-xs bg-white hover:bg-gray-100 rounded border shadow-sm" title="Ustawienia zaawansowane">⚙️</button>
                    <button type="button" onclick="this.closest('.field-card').remove()" class="p-1.5 text-xs text-red-500 bg-white hover:bg-red-50 rounded border shadow-sm" title="Usuń pole">✕</button>
                </div>
            </div>
        </div>

        <div>
            <input type="text" class="field-label w-full font-bold bg-transparent border-b border-gray-200 focus:border-blue-500 outline-none text-base text-gray-800" value="${data.label || ''}" placeholder="${data.type === 'html' ? 'Opis/Etykieta (dla Ciebie)' : 'Wpisz pytanie / etykietę pola...'}">
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
    const btns = card.querySelectorAll('.bg-gray-100 button');
    btns.forEach(b => {
        b.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
        b.classList.add('text-gray-500');
    });
    if(width === 'third') btns[0].classList.add('bg-white', 'shadow-sm', 'text-blue-600');
    if(width === 'half') btns[1].classList.add('bg-white', 'shadow-sm', 'text-blue-600');
    if(width === 'full') btns[2].classList.add('bg-white', 'shadow-sm', 'text-blue-600');
}

function updateWidthClasses(el, width) {
    el.classList.remove('md:col-span-1', 'md:col-span-2', 'md:col-span-3');
    if (width === 'third') el.classList.add('md:col-span-1');
    if (width === 'half') el.classList.add('md:col-span-2');
    if (width === 'full') el.classList.add('md:col-span-3');
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
        <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 font-bold w-6 h-6 rounded hover:bg-red-50 transition">✕</button></td>
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
        fields.push(field);
    });

    const settings = {
        reqLogin: document.getElementById('reqLogin').checked,
        fillOnce: document.getElementById('fillOnce').checked,
        editable: document.getElementById('editable').checked
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