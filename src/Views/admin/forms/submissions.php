<?php
$showDrafts = isset($_GET['drafts']) && $_GET['drafts'] == '1';
$draftsUrl = $showDrafts ? "/admin/forms/submissions?id={$form['id']}" : "/admin/forms/submissions?id={$form['id']}&drafts=1";
$draftsText = $showDrafts ? "Ukryj Wersje Robocze" : "Pokaż Wersje Robocze";
$draftsIcon = $showDrafts ? "👁️‍🗨️" : "📝";
?>
<div class="max-w-7xl mx-auto" style="max-width:100rem">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zgłoszenia: <?= htmlspecialchars($form['title']) ?></h1>
        <div class="flex gap-3">
            <a href="<?= $draftsUrl ?>" class="bg-yellow-50 hover:bg-yellow-100 text-yellow-700 border border-yellow-200 font-bold py-2 px-4 rounded-lg shadow-sm transition flex items-center gap-2">
                <span><?= $draftsIcon ?></span> <?= $draftsText ?>
            </a>
            
            <button onclick="document.getElementById('export-files-modal').classList.remove('hidden')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition flex items-center gap-2" title="Pobierz wszystkie załączniki jako ZIP">
                <span>📦</span> Pobierz Pliki (ZIP)
            </button>
            <button onclick="document.getElementById('export-modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm transition flex items-center gap-2">
                <span>⬇️</span> Eksportuj Dane
            </button>
            <a href="/admin/forms" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg shadow-sm transition">Wróć do formularzy</a>
        </div>
    </div>
    
  

    <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-100 border border-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-400 text-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-700 px-4 py-3 rounded mb-6 font-bold shadow-sm">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs">ID / Data</th>
                    <?php foreach ($fields as $field): ?>
                        <?php if(($field['type'] ?? '') === 'html') continue; ?>
                        <th class="px-6 py-4 font-bold text-gray-700"><?= htmlspecialchars($field['label']) ?></th>
                    <?php endforeach; ?>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs">Użytkownik</th>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs">Adres IP</th>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($decryptedRows)): ?>
                    <tr>
                        <td colspan="100%" class="px-6 py-8 text-center text-gray-500">Brak zgłoszeń dla tego formularza.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($decryptedRows as $row): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                                <span class="font-bold text-gray-700">#<?= $row['id'] ?></span><br>
                                <span class="text-xs"><?= $row['date'] ?></span>
                            </td>
                            
                            <?php foreach ($fields as $field): ?>
                                <?php if(($field['type'] ?? '') === 'html') continue; ?>
                                <td class="px-6 py-4 text-gray-700">
                                    <?php
                                    $key = $field['custom_id'] ?? $field['id'] ?? md5($field['label']);
                                    if ($field['type'] === 'file') {
                                        if (!empty($row['files'][$key])) {
                                            $f = $row['files'][$key];
                                            
                                            // Normalizacja struktury do tablicy (aby obsłużyć stare i nowe wpisy identycznie)
                                            if (isset($f['original_name'])) {
                                                $f = [$f];
                                            }
                                            
                                            if (is_array($f) && count($f) > 0) {
                                                if (count($f) === 1) {
                                                    // Przypadek 1: Jeden plik - klasyczny link
                                                    $singleFile = reset($f);
                                                    if (!empty($singleFile['storage_name'])) {
                                                        $origName = urlencode($singleFile['original_name'] ?? 'plik');
                                                        echo '<a href="/admin/forms/download?file='.$singleFile['storage_name'].'&orig='.$origName.'" title="'.htmlspecialchars($singleFile['original_name'] ?? '').'" class="text-blue-600 hover:text-blue-800 hover:underline font-bold flex items-center gap-1.5"><span class="text-base">📎</span> <span class="truncate max-w-[150px] inline-block">'.htmlspecialchars($singleFile['original_name'] ?? '').'</span></a>';
                                                    } else {
                                                        echo '<span class="text-gray-300">-</span>';
                                                    }
                                                } else {
                                                    // Przypadek 2: Wiele plików - nested list 📦
                                                    echo '<div class="bg-gray-50 p-3 rounded-lg border border-gray-100 min-w-[200px]">';
                                                    echo '<span class="text-xs font-bold text-gray-500 uppercase mb-2 block tracking-wider">📦 Pliki (' . count($f) . '):</span>';
                                                    echo '<ul class="space-y-2">';
                                                    foreach ($f as $singleFile) {
                                                        if (empty($singleFile['storage_name'])) continue;
                                                        $origName = urlencode($singleFile['original_name'] ?? 'plik');
                                                        echo '<li><a href="/admin/forms/download?file='.$singleFile['storage_name'].'&orig='.$origName.'" title="'.htmlspecialchars($singleFile['original_name'] ?? '').'" class="text-blue-600 hover:text-blue-800 hover:underline font-medium flex items-start gap-1.5 text-sm leading-tight"><span class="text-gray-400 mt-0.5">↳</span> <span class="truncate flex-1">'.htmlspecialchars($singleFile['original_name'] ?? '').'</span></a></li>';
                                                    }
                                                    echo '</ul>';
                                                    echo '</div>';
                                                }
                                            } else {
                                                echo '<span class="text-gray-300">-</span>';
                                            }
                                        } else {
                                            echo '<span class="text-gray-300">-</span>';
                                        }
                                    } else {
                                        $val = $row['data'][$key] ?? '-';
                                        if (is_array($val)) {
                                            echo htmlspecialchars(implode(', ', $val));
                                        } else {
                                            echo htmlspecialchars($val);
                                        }
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                            
                            <td class="px-6 py-4 text-gray-700 text-xs font-bold"><?= htmlspecialchars($row['user_email']) ?></td>
                            <td class="px-6 py-4 text-gray-500 text-xs"><?= $row['ip'] ?></td>
                            <td class="px-6 py-4 text-right">
                                <a href="/admin/forms/submissions/delete?id=<?= $row['id'] ?>&form_id=<?= $form['id'] ?>" onclick="return confirm('Czy na pewno usunąć to zgłoszenie?');" class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition">
                                    Usuń
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="export-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-gray-50 border-b p-5 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-gray-800 text-lg">Konfigurator Eksportu Danych</h3>
                <p class="text-xs text-gray-500 mt-1">Wybierz, co chcesz zawrzeć w pliku</p>
            </div>
            <button type="button" onclick="document.getElementById('export-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>
        </div>
        
        <form action="/admin/forms/submissions/export" method="POST" class="flex-1 overflow-y-auto p-6">
        <input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
            <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-3">Format Pliku</label>
                <div class="flex gap-4">
                    <label class="flex-1 border-2 border-indigo-100 bg-indigo-50/50 p-4 rounded-xl cursor-pointer hover:border-indigo-400 transition-all focus-within:ring-2 focus-within:ring-indigo-500">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="format" value="csv" checked class="w-5 h-5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <span class="block font-bold text-indigo-900">CSV (Zgodny z Excelem)</span>
                                <span class="text-xs text-indigo-700 block">Separator średnik (;) i kodowanie BOM.</span>
                            </div>
                        </div>
                    </label>
                    <label class="flex-1 border-2 border-gray-100 bg-gray-50 p-4 rounded-xl cursor-pointer hover:border-gray-300 transition-all focus-within:ring-2 focus-within:ring-gray-500">
                        <div class="flex items-center gap-3">
                            <input type="radio" name="format" value="json" class="w-5 h-5 text-gray-600 focus:ring-gray-500">
                            <div>
                                <span class="block font-bold text-gray-800">Czysty JSON</span>
                                <span class="text-xs text-gray-500 block">Do integracji z innymi systemami API.</span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div class="mb-2 flex justify-between items-end border-b pb-2">
                <label class="block text-sm font-bold text-gray-700">Zawartość Kolumn (Pola)</label>
                <button type="button" onclick="toggleAllCheckboxes()" id="toggle-btn" class="text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 px-2 py-1 rounded transition">Odznacz wszystkie</button>
            </div>

            <div class="space-y-6 mt-4">
                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Dane Systemowe</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="export_fields[]" value="sys_id" checked class="export-cb w-4 h-4 text-blue-600 rounded border-gray-300"> ID Zgłoszenia
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="export_fields[]" value="sys_date" checked class="export-cb w-4 h-4 text-blue-600 rounded border-gray-300"> Data wysłania
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="export_fields[]" value="sys_email" checked class="export-cb w-4 h-4 text-blue-600 rounded border-gray-300"> Email systemowy (Użytkownika)
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="export_fields[]" value="sys_ip" class="export-cb w-4 h-4 text-blue-600 rounded border-gray-300"> Adres IP
                        </label>
                    </div>
                </div>

                <div>
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Dane z Pól Formularza</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($fields as $field): ?>
                            <?php 
                            if (($field['type'] ?? '') === 'html') continue; 
                            $key = $field['custom_id'] ?? $field['id'] ?? md5($field['label']);
                            ?>
                            <label class="flex items-center gap-2 cursor-pointer text-sm bg-gray-50 border border-gray-200 p-2 rounded hover:border-blue-300 transition-colors">
                                <input type="checkbox" name="export_fields[]" value="<?= $key ?>" checked class="export-cb w-4 h-4 text-blue-600 rounded border-gray-300"> 
                                <span class="truncate font-medium text-gray-700"><?= htmlspecialchars($field['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 pt-4 border-t flex justify-end gap-3 sticky bottom-0 bg-white">
                <button type="button" onclick="document.getElementById('export-modal').classList.add('hidden')" class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-100 rounded-lg transition">Anuluj</button>
                <button type="submit" onclick="setTimeout(()=>document.getElementById('export-modal').classList.add('hidden'), 500)" class="px-6 py-2.5 bg-indigo-600 text-white font-bold rounded-lg shadow-sm hover:bg-indigo-700 transition flex items-center gap-2">
                    ⬇️ Pobierz Plik
                </button>
            </div>
        </form>
    </div>
</div>

<div id="export-files-modal" class="hidden fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
        <div class="bg-green-50 border-b border-green-100 p-5 flex justify-between items-center">
            <div>
                <h3 class="font-bold text-green-900 text-lg flex items-center gap-2"><span>📦</span> Pobieranie Masowe Plików</h3>
                <p class="text-xs text-green-700 mt-1">Zdefiniuj nazewnictwo dla wypakowywanych plików z archiwum ZIP.</p>
            </div>
            <button type="button" onclick="document.getElementById('export-files-modal').classList.add('hidden')" class="text-gray-400 hover:text-red-500 text-2xl font-bold leading-none">&times;</button>
        </div>
        
        <form action="/admin/forms/submissions/export-files" method="POST" class="flex-1 overflow-y-auto p-6 bg-gray-50">
        <input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
            <input type="hidden" name="form_id" value="<?= $form['id'] ?>">
            
            <div class="bg-white p-5 border border-gray-200 rounded-xl shadow-sm mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Wzorzec nazewnictwa (bez rozszerzenia)</label>
                <input type="text" id="name-pattern-input" name="name_pattern" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none text-gray-800 font-mono text-sm shadow-inner" value="{{sys_id}}_{{original_name}}" required>
                <p class="text-xs text-gray-500 mt-2">Domyślnie rozszerzenie (np. .pdf) zostanie dodane automatycznie na samym końcu.</p>
            </div>

            <div>
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Kliknij tag, aby wstawić go do wzorca:</h4>
                
                <div class="space-y-4">
                    <div class="bg-white p-4 border border-gray-200 rounded-xl shadow-sm">
                        <span class="text-xs font-bold text-indigo-800 block mb-2">Zmienne Systemowe:</span>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="insertTag('{{sys_id}}')" class="bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded transition shadow-sm">{{sys_id}}</button>
                            <button type="button" onclick="insertTag('{{original_name}}')" class="bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded transition shadow-sm">{{original_name}}</button>
                            <button type="button" onclick="insertTag('{{sys_date}}')" class="bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded transition shadow-sm">{{sys_date}}</button>
                            <button type="button" onclick="insertTag('{{sys_email}}')" class="bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 text-xs font-mono px-3 py-1.5 rounded transition shadow-sm">{{sys_email}}</button>
                        </div>
                    </div>

                    <div class="bg-white p-4 border border-gray-200 rounded-xl shadow-sm">
                        <span class="text-xs font-bold text-green-800 block mb-2">Zmienne z Formularza:</span>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($fields as $field): ?>
                                <?php 
                                if (($field['type'] ?? '') === 'html' || ($field['type'] ?? '') === 'file') continue; 
                                $key = $field['custom_id'] ?? $field['id'] ?? md5($field['label']);
                                ?>
                                <button type="button" onclick="insertTag('{{<?= $key ?>}}')" class="bg-green-50 border border-green-200 hover:bg-green-100 text-green-800 text-xs font-mono px-3 py-1.5 rounded transition shadow-sm" title="<?= htmlspecialchars($field['label']) ?>">
                                    {{<?= htmlspecialchars(strlen($field['label']) > 15 ? substr($field['label'], 0, 15) . '...' : $field['label']) ?>}}
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 pt-4 flex justify-end gap-3 sticky bottom-0">
                <button type="button" onclick="document.getElementById('export-files-modal').classList.add('hidden')" class="px-5 py-2.5 text-gray-600 font-bold hover:bg-gray-200 rounded-lg transition bg-white border border-gray-300 shadow-sm">Anuluj</button>
                <button type="submit" onclick="setTimeout(()=>document.getElementById('export-files-modal').classList.add('hidden'), 500)" class="px-6 py-2.5 bg-green-600 text-white font-bold rounded-lg shadow-sm hover:bg-green-700 transition flex items-center gap-2">
                    ⬇️ Pobierz ZIP
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Skrypt dla checkboxów do eksportu tabelarycznego CSV
    let allChecked = true;
    function toggleAllCheckboxes() {
        allChecked = !allChecked;
        const checkboxes = document.querySelectorAll('.export-cb');
        checkboxes.forEach(cb => cb.checked = allChecked);
        
        const btn = document.getElementById('toggle-btn');
        if (allChecked) {
            btn.innerText = 'Odznacz wszystkie';
            btn.classList.replace('text-red-600', 'text-blue-600');
            btn.classList.replace('bg-red-50', 'bg-blue-50');
        } else {
            btn.innerText = 'Zaznacz wszystkie';
            btn.classList.replace('text-blue-600', 'text-red-600');
            btn.classList.replace('bg-blue-50', 'bg-red-50');
        }
    }

    // Skrypt dla przycisków wstawiających Tagi we wzorcu nazw ZIP
    function insertTag(tag) {
        const input = document.getElementById('name-pattern-input');
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const text = input.value;
        
        // Wstaw tag w miejsce kursora
        input.value = text.substring(0, start) + tag + text.substring(end, text.length);
        
        // Przesuń kursor po tagu i zrób focus
        input.selectionStart = input.selectionEnd = start + tag.length;
        input.focus();
    }
</script>