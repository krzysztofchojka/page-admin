<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Builder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style>
        .ghost { opacity: 0.5; background: #e0e7ff; border: 2px dashed #4f46e5; }
        .sidebar-ghost { opacity: 0.5; }
        .drag-handle { cursor: grab; }
        .drag-handle:active { cursor: grabbing; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

<div class="w-80 bg-white border-r shadow-lg flex flex-col shrink-0 z-40">
    <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 uppercase tracking-wide text-sm flex justify-between items-center">
        <span>Pola Formularza</span>
        <span class="text-xl">📝</span>
    </div>
    
    <div class="px-4 pt-4">
        <label class="block text-xs font-bold text-gray-500 mb-1">Domyślna szerokość bloków</label>
        <select id="defaultFieldWidth" class="w-full border p-2 rounded text-sm bg-gray-50 focus:ring-2 focus:ring-blue-500 outline-none">
            <option value="full">Pełna szerokość (Full)</option>
            <option value="half">Połowa ekranu (1/2)</option>
            <option value="third">Jedna trzecia (1/3)</option>
        </select>
    </div>

    <div id="form-sidebar" class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3 content-start">
        <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-2 mb-1 border-b pb-1">Podstawowe</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="text"><span class="text-lg">T</span>Tekst</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="email"><span class="text-lg">📧</span>Email</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="phone"><span class="text-lg">📞</span>Telefon</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="textarea"><span class="text-lg">📄</span>Długi tekst</div>
        
        <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Wybór</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="select"><span class="text-lg">🔽</span>Lista (Select)</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="radio"><span class="text-lg">🔘</span>Pojedynczy (Radio)</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="checkbox"><span class="text-lg">☑️</span>Wielokrotny</div>
        
        <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Zaawansowane</div>
        <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="file"><span class="text-lg">📎</span>Plik</div>
        <div class="sidebar-block border bg-white hover:border-gray-800 hover:bg-gray-100 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="html"><span class="text-lg">&lt;/&gt;</span>Custom HTML</div>
    </div>

    <div class="p-4 border-t bg-gray-50">
        <h3 class="font-bold mb-3 text-sm text-gray-700">Ustawienia Formularza</h3>
        <label class="flex items-center gap-2 text-sm mb-2 cursor-pointer">
            <input type="checkbox" id="reqLogin" class="rounded text-blue-600"> Wymaga logowania
        </label>
        <label class="flex items-center gap-2 text-sm mb-2 cursor-pointer">
            <input type="checkbox" id="fillOnce" class="rounded text-blue-600"> Wypełnij tylko raz
        </label>
        <label class="flex items-center gap-2 text-sm mb-4 cursor-pointer">
            <input type="checkbox" id="editable" class="rounded text-blue-600"> Edytowalne po wysłaniu
        </label>
    </div>
</div>

<div class="flex-1 flex flex-col overflow-hidden bg-gray-50">
    <div class="bg-white shadow-sm p-4 flex justify-between items-center z-10">
        <div class="flex items-center gap-4">
            <a href="/admin/forms" class="text-gray-500 hover:text-black font-bold">← Wróć</a>
            <input type="text" id="formTitle" value="<?= htmlspecialchars($form['title']) ?>" class="text-2xl font-bold border-b border-transparent hover:border-gray-300 focus:outline-none px-2 text-gray-800 w-96" placeholder="Tytuł Formularza">
        </div>
        <div>
            <span class="text-sm text-gray-400 mr-4 hidden md:inline">Przeciągnij bloki z paska po lewej →</span>
            <button onclick="saveForm()" class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-lg shadow font-bold transition">Zapisz Formularz</button>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto p-8 relative">
        <div id="form-canvas" class="max-w-4xl mx-auto min-h-[500px] border-2 border-dashed border-gray-300 bg-gray-50/50 p-6 rounded-xl grid grid-cols-1 md:grid-cols-3 gap-4 content-start pb-24 drop-zone">
        </div>
    </div>
</div>

<div id="options-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
        <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Kreator Opcji Wyboru</h3>
            <button type="button" onclick="closeOptionsModal()" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
        </div>
        <div class="p-4 overflow-y-auto max-h-[60vh]">
            <table class="w-full text-left text-sm mb-4">
                <thead>
                    <tr>
                        <th class="pb-2 font-bold text-gray-500">Etykieta opcji</th>
                        <th class="pb-2 font-bold text-gray-500 w-24" title="Ile razy opcja może zostać wybrana przez użytkowników?">Limit (miejsc)</th>
                        <th class="pb-2 w-10"></th>
                    </tr>
                </thead>
                <tbody id="options-modal-list">
                    </tbody>
            </table>
            <button type="button" onclick="addOptionRow()" class="text-sm bg-gray-100 hover:bg-gray-200 border text-gray-700 font-bold px-3 py-1.5 rounded shadow-sm w-full transition">+ Dodaj kolejną opcję</button>
        </div>
        <div class="bg-gray-50 border-t p-4 flex justify-end gap-2">
            <button type="button" onclick="closeOptionsModal()" class="px-4 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded transition">Anuluj</button>
            <button type="button" onclick="saveOptionsModal()" class="px-6 py-2 bg-blue-600 text-white font-bold rounded shadow-sm hover:bg-blue-700 transition">Zapisz opcje</button>
        </div>
    </div>
</div>

<script>
const formId = <?= $form['id'] ?>;
const savedFields = <?= $form['form_json'] ?: '[]' ?>;
const canvas = document.getElementById('form-canvas');
const savedSettings = <?= $form['settings'] ?: '{}' ?>;

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
</script>
</body>
</html>