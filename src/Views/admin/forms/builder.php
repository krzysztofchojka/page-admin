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
                <option value="half">Dwie trzecie ekranu (2/3)</option>
                <option value="third">Jedna trzecia (1/3)</option>
            </select>
        </div>

        <div id="form-sidebar" class="flex-1 overflow-y-auto p-4 grid grid-cols-2 gap-3 content-start">
            <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-2 mb-1 border-b pb-1">Podstawowe</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="text"><span class="text-lg">T</span>Tekst</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="email"><span class="text-lg">📧</span>Email</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="phone"><span class="text-lg">📞</span>Telefon</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="textarea"><span class="text-lg">📄</span>Długi tekst</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="date"><span class="text-lg">📅</span>Data</div>
            
            <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Wybór</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="select"><span class="text-lg">🔽</span>Lista (Select)</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="radio"><span class="text-lg">🔘</span>Pojedynczy (Radio)</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="checkbox"><span class="text-lg">☑️</span>Wielokrotny</div>

            <div class="col-span-2 text-xs font-bold text-gray-400 uppercase mt-4 mb-1 border-b pb-1">Zaawansowane</div>
            <div class="sidebar-block border bg-white hover:border-blue-500 hover:bg-blue-50 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="file"><span class="text-lg">📎</span>Plik</div>
            <div class="sidebar-block border bg-white hover:border-gray-800 hover:bg-gray-100 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="html"><span class="text-lg">&lt;/&gt;</span>Custom HTML</div>

            <div class="col-span-2 text-xs font-bold text-red-400 uppercase mt-4 mb-1 border-b border-red-100 pb-1">Anty-Spam (Boty)</div>
            <div class="sidebar-block border border-red-200 bg-red-50 hover:border-red-500 hover:bg-red-100 text-red-700 p-3 rounded shadow-sm cursor-grab text-center text-xs font-bold transition flex flex-col items-center gap-1" data-type="honeypot"><span class="text-lg">🍯</span>Honeypot</div>
            <div class="sidebar-block border border-red-200 bg-red-50 hover:border-red-500 hover:bg-red-100 text-red-700 p-3 rounded shadow-sm cursor-grab text-center text-[11px] font-bold transition flex flex-col items-center gap-1" data-type="captcha_image"><span class="text-lg">🖼️</span>Obrazek</div>
            <div class="sidebar-block border border-red-200 bg-red-50 hover:border-red-500 hover:bg-red-100 text-red-700 p-3 rounded shadow-sm cursor-grab text-center text-[11px] font-bold transition flex flex-col items-center gap-1" data-type="captcha_turnstile"><span class="text-lg">🛡️</span>Turnstile</div>
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

            <label class="flex items-center gap-2 text-sm mb-4 cursor-pointer border-t border-gray-200 pt-3">
                <input type="checkbox" id="allowMultipleFiles" class="rounded text-blue-600"> Wiele plików w inputach (Multi-upload)
            </label>
            <button type="button" onclick="openEmailModal()" class="w-full bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-700 font-bold py-2.5 rounded shadow-sm transition text-sm flex items-center justify-center gap-2">
                <span>✉️</span> Powiadomienia Email
            </button>
        </div>
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

    <div id="email-settings-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col">
            <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Powiadomienia Email i Integracje</h3>
                <button type="button" onclick="closeEmailModal()" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
            </div>
            <div class="p-5 overflow-y-auto max-h-[75vh] flex flex-col gap-6">
                
                <div class="bg-blue-50 text-blue-800 p-3 rounded text-sm border border-blue-200 shadow-sm flex items-start gap-2">
                    <span class="text-xl leading-none">💡</span>
                    <span><strong>Wskazówka:</strong> Pamiętaj, by przed konfiguracją przypisań tagów kliknąć <b>"Zapisz formularz"</b> na stronie głównej – tylko wtedy nowo dodane pola pojawią się w selektach. System automatycznie odczyta tagi z wybranych szablonów (np. <code>{{twoj_tag}}</code>) i zapyta Cię, które pole chcesz pod nie podpiąć.</span>
                </div>

                <div class="border border-gray-200 rounded-lg p-5 bg-gray-50/50">
                    <label class="flex items-center gap-3 font-bold text-gray-800 mb-4 cursor-pointer">
                        <input type="checkbox" id="email_send_user" class="w-5 h-5 text-blue-600 rounded">
                        Wyślij automatyczne potwierdzenie do użytkownika (Klienta)
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Skąd pobrać email użytkownika?</label>
                            <select id="email_user_field" class="w-full border p-2 rounded focus:ring-blue-500 text-sm"></select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Szablon powiadomienia</label>
                            <div class="flex gap-2">
                                <select id="email_user_template" onchange="updateTagRows()" class="w-full border p-2 rounded focus:ring-blue-500 text-sm">
                                    <option value="">-- Wybierz szablon --</option>
                                    <?php foreach($emailTemplates ?? [] as $tpl): ?>
                                        <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="previewTemplate('email_user_template')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-2 rounded shadow-sm text-sm font-bold transition">👁️</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-5 bg-gray-50/50">
                    <label class="flex items-center gap-3 font-bold text-gray-800 mb-4 cursor-pointer">
                        <input type="checkbox" id="email_send_admin" class="w-5 h-5 text-blue-600 rounded">
                        Wyślij powiadomienie o nowym zgłoszeniu do Administratora
                    </label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Odbiorcy / Lista mailingowa</label>
                            <select id="email_admin_list" class="w-full border p-2 rounded focus:ring-blue-500 text-sm">
                                <option value="">-- Wybierz listę odbiorców --</option>
                                <option value="admins">Systemowa: Wszyscy Administratorzy</option>
                                <?php foreach($mailingLists ?? [] as $ml): ?>
                                    <option value="<?= $ml['id'] ?>"><?= htmlspecialchars($ml['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Szablon powiadomienia</label>
                            <div class="flex gap-2">
                                <select id="email_admin_template" onchange="updateTagRows()" class="w-full border p-2 rounded focus:ring-blue-500 text-sm">
                                    <option value="">-- Wybierz szablon --</option>
                                    <?php foreach($emailTemplates ?? [] as $tpl): ?>
                                        <option value="<?= $tpl['id'] ?>"><?= htmlspecialchars($tpl['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" onclick="previewTemplate('email_admin_template')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-3 py-2 rounded shadow-sm text-sm font-bold transition">👁️</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm">
                    <h4 class="font-bold text-gray-800 text-lg mb-2">Automatyczne Przypisanie Zmiennych (Tagów)</h4>
                    <p class="text-sm text-gray-500 mb-4 leading-relaxed">
                        Zmienne znalezione w przypiętych powyżej szablonach. Wskaż, które z pól formularza ma zostać użyte w miejscu danego tagu.
                    </p>
                    <div id="tag-mapping-container" class="flex flex-col gap-2"></div>
                </div>

            </div>
            <div class="bg-gray-50 border-t p-4 flex justify-end gap-3">
                <button type="button" onclick="closeEmailModal()" class="px-5 py-2 text-gray-600 font-bold hover:bg-gray-200 rounded transition">Anuluj</button>
                <button type="button" onclick="saveEmailModal()" class="px-6 py-2 bg-indigo-600 text-white font-bold rounded shadow-sm hover:bg-indigo-700 transition">💾 Zapisz konfigurację emaili</button>
            </div>
        </div>
    </div>

    <div id="email-preview-modal" class="hidden fixed inset-0 bg-black/80 z-[60] flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col h-[80vh]">
            <div class="bg-gray-50 border-b p-4 flex justify-between items-center">
                <h3 class="font-bold text-gray-800">Podgląd szablonu HTML</h3>
                <button type="button" onclick="document.getElementById('email-preview-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
            </div>
            <div class="flex-1 overflow-y-auto p-6 bg-gray-100" id="email-preview-content"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                if (window.CMS_CONFIG && window.CMS_CONFIG.savedSettings) {
                    if (window.CMS_CONFIG.savedSettings.allowMultipleFiles) {
                        document.getElementById('allowMultipleFiles').checked = true;
                    }
                }
            }, 300); // Małe opóźnienie dla pewności, że główny skrypt załadował ustawienia
        });

        window.CMS_CONFIG = {
            formId: <?= $form['id'] ?>,
            savedFields: <?= $form['form_json'] ?: '[]' ?>,
            canvas: document.getElementById('form-canvas'),
            savedSettings: <?= $form['settings'] ?: '{}' ?>
        };

        const emailTemplatesData = <?= json_encode($emailTemplates ?? []) ?>;

        let currentEmailSettings = window.CMS_CONFIG.savedSettings.email || {
            sendUser: false, userTemplate: '', userField: '',
            sendAdmin: false, adminTemplate: '', adminList: '',
            tags: []
        };

        function getFieldOptions() {
            const fields = window.CMS_CONFIG.savedFields || [];
            const isLoginReq = document.getElementById('reqLogin').checked;
            
            let options = '<option value="">-- Wybierz powiązane pole --</option>';
            if (isLoginReq) {
                options += '<option value="system_user_email" class="font-bold text-blue-600">🌍 Pobierz z konta systemowego zalogowanego usera</option>';
            } else {
                options += '<option value="system_user_email" disabled class="text-gray-400">🌍 Konto systemowe (Wymaga zaznaczenia "Wymaga logowania")</option>';
            }

            fields.forEach(f => {
                const fieldId = f.custom_id || f.id;
                if(fieldId && f.type !== 'html' && f.type !== 'honeypot' && f.type !== 'captcha_math') {
                    options += `<option value="${fieldId}">P: ${f.label} [${f.type}]</option>`;
                }
            });
            return options;
        }

        function openEmailModal() {
            document.getElementById('email_send_user').checked = currentEmailSettings.sendUser;
            document.getElementById('email_send_admin').checked = currentEmailSettings.sendAdmin;
            
            document.getElementById('email_user_field').innerHTML = getFieldOptions();
            
            document.getElementById('email_user_template').value = currentEmailSettings.userTemplate || '';
            document.getElementById('email_admin_template').value = currentEmailSettings.adminTemplate || '';
            document.getElementById('email_admin_list').value = currentEmailSettings.adminList || '';
            document.getElementById('email_user_field').value = currentEmailSettings.userField || '';

            updateTagRows(); 
            document.getElementById('email-settings-modal').classList.remove('hidden');
        }

        function closeEmailModal() {
            document.getElementById('email-settings-modal').classList.add('hidden');
        }

        function getTagsFromTemplate(id) {
            if(!id) return [];
            const tpl = emailTemplatesData.find(t => t.id == id);
            if(!tpl) return [];
            const content = (tpl.subject || '') + " " + (tpl.body || '');
            const matches = content.match(/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/g) || [];
            return [...new Set(matches.map(m => m.replace(/[{}]/g, '').trim()))];
        }

        function updateTagRows() {
            const t1 = document.getElementById('email_user_template').value;
            const t2 = document.getElementById('email_admin_template').value;

            let neededTags = [...new Set([...getTagsFromTemplate(t1), ...getTagsFromTemplate(t2)])];
            neededTags = neededTags.filter(t => t !== 'email'); 

            let newTags = [];
            neededTags.forEach(tag => {
                let existing = (currentEmailSettings.tags || []).find(t => t.tag === tag);
                newTags.push({
                    tag: tag,
                    field: existing ? existing.field : ''
                });
            });

            currentEmailSettings.tags = newTags;
            renderTags();
        }

        function renderTags() {
            const container = document.getElementById('tag-mapping-container');
            container.innerHTML = '';
            
            if(!currentEmailSettings.tags || currentEmailSettings.tags.length === 0) {
                container.innerHTML = '<div class="text-sm text-gray-500 italic p-4 text-center border border-dashed rounded bg-gray-50">Brak wykrytych tagów w wybranych powyżej szablonach.</div>';
                return;
            }

            const fieldOptionsHtml = getFieldOptions();

            currentEmailSettings.tags.forEach((t, index) => {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-3 mb-2 p-2 bg-gray-50 border rounded-lg hover:border-blue-300 transition';
                row.innerHTML = `
                    <div class="flex-[0.8] flex items-center pl-3">
                        <span class="text-gray-400 font-bold text-lg leading-none mr-1">{{</span>
                        <input type="text" class="w-full p-1.5 outline-none bg-transparent font-bold text-gray-700 pointer-events-none" value="${t.tag}" readonly>
                        <span class="text-gray-400 font-bold text-lg leading-none">}}</span>
                    </div>
                    <span class="text-gray-300 text-xl font-bold">🡒</span>
                    <select class="flex-[1.2] border border-gray-300 bg-white p-2.5 rounded shadow-sm text-sm font-medium focus:ring-2 focus:ring-blue-500 outline-none" onchange="updateTagData(${index}, 'field', this.value)">
                        ${fieldOptionsHtml}
                    </select>
                `;
                container.appendChild(row);
                setTimeout(() => { row.querySelector('select').value = t.field || ''; }, 0);
            });
        }

        function updateTagData(index, key, val) {
            currentEmailSettings.tags[index][key] = val;
        }

        function saveEmailModal() {
            currentEmailSettings.sendUser = document.getElementById('email_send_user').checked;
            currentEmailSettings.userTemplate = document.getElementById('email_user_template').value;
            currentEmailSettings.userField = document.getElementById('email_user_field').value;
            currentEmailSettings.sendAdmin = document.getElementById('email_send_admin').checked;
            currentEmailSettings.adminTemplate = document.getElementById('email_admin_template').value;
            currentEmailSettings.adminList = document.getElementById('email_admin_list').value;
            closeEmailModal();
        }

        function previewTemplate(selectId) {
            const id = document.getElementById(selectId).value;
            if(!id) return alert('Wybierz najpierw szablon z listy rozwijanej obok, aby móc go podejrzeć.');
            const tpl = emailTemplatesData.find(t => t.id == id);
            if(tpl) {
                const box = document.getElementById('email-preview-content');
                box.innerHTML = `<div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200 max-w-2xl mx-auto text-sm text-gray-800">${tpl.body}</div>`;
                document.getElementById('email-preview-modal').classList.remove('hidden');
            }
        }

        const originalFetch = window.fetch;
        window.fetch = async function() {
            if (arguments[0] === '/admin/forms/save') {
                try {
                    let payload = JSON.parse(arguments[1].body);
                    window.CMS_CONFIG.savedFields = payload.fields;
                    payload.settings = payload.settings || {};
                    payload.settings.email = currentEmailSettings;

                    // WSTRZYKNIĘCIE NOWEGO USTAWIENIA:
                    const allowMult = document.getElementById('allowMultipleFiles');
                    if (allowMult) {
                        payload.settings.allowMultipleFiles = allowMult.checked;
                    }

                    arguments[1].body = JSON.stringify(payload);
                } catch (e) {
                    console.error('Błąd przesyłu danych formularza:', e);
                }
            }
            return originalFetch.apply(this, arguments);
        };
    </script>
    <script src="<?= \CMS\Helpers\Asset::url('/assets/js/admin/form-builder.js') ?>"></script>
</body>
</html>