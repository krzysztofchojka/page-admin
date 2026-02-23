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
    // Iniekcja zmiennych z PHP do globalnego obiektu JS (lub zmiennych)
    window.CMS_CONFIG = {
        formId: <?= $form['id'] ?>,
        savedFields: <?= $form['form_json'] ?: '[]' ?>,
        canvas: document.getElementById('form-canvas'),
        savedSettings: <?= $form['settings'] ?: '{}' ?>
    };
</script>
<script src="<?= \CMS\Helpers\Asset::url('/assets/js/admin/form-builder.js') ?>"></script>
</body>
</html>