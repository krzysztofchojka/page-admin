<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edycja Wpisu</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.2/ace.js"></script>
    <style>
        .ghost { opacity: 0.5; border: 2px dashed #4f46e5; }
        .drop-zone { min-height: 100px; padding-bottom: 20px; }
        /* Wymuszenie czarnego koloru, ale z BEZWZGLĘDNYM WYKLUCZENIEM edytora Ace */
.block-item *:not(.ace_editor):not(.ace_editor *), 
.ql-editor, 
.ql-editor * { 
    color: #1a1a1a !important; 
}
.block-item span, .block-item label { color: inherit !important; }
        .ql-toolbar { background: white; border-top-left-radius: 0.5rem; border-top-right-radius: 0.5rem; }
        .ql-container { background: white; border-bottom-left-radius: 0.5rem; border-bottom-right-radius: 0.5rem; font-size: 16px; }
        .ql-editor { min-height: 200px; }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">
    
    <div class="bg-white shadow-sm p-4 flex justify-between items-center z-50 shrink-0 border-b border-gray-200">
        <div class="flex items-center gap-4 flex-1">
            <a href="/admin/posts" class="text-gray-500 hover:text-black font-bold">← Wróć</a>
            <input type="text" id="pageTitle" value="<?= htmlspecialchars($post['title']) ?>" class="text-2xl font-bold border-b border-transparent hover:border-gray-300 focus:outline-none px-2 text-gray-800 w-1/2" placeholder="Wpisz tytuł posta..." />
            <span class="text-sm font-bold <?= $post['status'] == 'published' ? 'text-green-600 bg-green-50' : 'text-gray-500 bg-gray-100' ?> px-3 py-1 rounded-full uppercase tracking-wider">
                Obecnie: <?= $post['status'] == 'published' ? 'Opublikowany' : 'Szkic' ?>
            </span>
        </div>
        <div class="flex gap-3 items-center">
            <button onclick="savePost('draft')" id="btn-draft" class="bg-gray-100 hover:bg-gray-200 border border-gray-300 text-gray-800 px-6 py-2.5 rounded-lg font-bold transition shadow-sm">
                💾 Zapisz jako Szkic
            </button>
            <button onclick="savePost('published')" id="btn-publish" class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-lg shadow-md font-bold transition">
                🚀 Opublikuj
            </button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        
        <div class="w-72 bg-white border-r shadow-lg flex flex-col shrink-0 z-40 overflow-y-auto">
            <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 text-sm uppercase tracking-wider">Bloki Treści</div>
            <div class="p-4 grid grid-cols-2 gap-3" id="block-sidebar">
                <div class="sidebar-block border bg-white hover:border-blue-500 p-3 rounded cursor-grab text-center text-sm font-bold flex flex-col items-center gap-1" data-type="text"><span class="text-lg text-blue-500">T</span>Tekst</div>
                <div class="sidebar-block border bg-white hover:border-green-500 p-3 rounded cursor-grab text-center text-sm font-bold flex flex-col items-center gap-1" data-type="image"><span class="text-lg text-green-500">🖼</span>Obrazek</div>
                <div class="sidebar-block border bg-white hover:border-pink-500 p-3 rounded cursor-grab text-center text-sm font-bold flex flex-col items-center gap-1" data-type="gallery"><span class="text-lg text-pink-500">📷</span>Galeria</div>
                <div class="sidebar-block border bg-white hover:border-red-500 p-3 rounded cursor-grab text-center text-sm font-bold flex flex-col items-center gap-1" data-type="video"><span class="text-lg text-red-500">▶️</span>Wideo</div>
                <div class="sidebar-block border bg-white hover:border-gray-800 p-3 rounded cursor-grab text-center text-sm font-bold flex flex-col items-center gap-1" data-type="raw_html"><span class="text-lg text-gray-800">&lt;/&gt;</span>HTML</div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 relative scroll-smooth" id="main-editor-scroll">
            <div id="editor-wrapper" class="max-w-4xl mx-auto">
                <div id="editor" class="drop-zone min-h-[600px] bg-white shadow-xl rounded-lg p-8 grid grid-cols-1 gap-6 border-2 border-transparent" data-zone-uid="editor"></div>
            </div>
        </div>

        <div class="w-80 bg-white border-l shadow-lg flex flex-col shrink-0 z-40 overflow-y-auto">
            <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 text-sm uppercase tracking-wider">Konfiguracja Wpisu</div>
            <div class="p-5 space-y-6">
                
                <div>
                    <label class="block text-sm font-bold mb-1 text-gray-800">Kategoria</label>
                    <select id="postCategory" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none bg-white shadow-sm">
                        <option value="">Brak kategorii</option>
                        <?php foreach($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $post['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="border-t pt-5">
                    <label class="block text-sm font-bold mb-1 text-gray-800">Miniaturka (Obrazek wyróżniający)</label>
                    <p class="text-[11px] text-gray-500 mb-2 leading-tight">Wyświetli się na liście postów. Zostaw puste, by nie pokazywać grafiki.</p>
                    <div class="flex">
                        <input type="text" id="postThumbnail" value="<?= htmlspecialchars($post['thumbnail'] ?? '') ?>" class="w-full border border-gray-300 rounded-l p-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="/uploads/media/obrazek.jpg" oninput="document.getElementById('thumb-preview').src = this.value || 'https://via.placeholder.com/400x250?text=Brak+Miniaturki'">
                        <button type="button" onclick="window.openMediaPicker('postThumbnail')" class="bg-purple-100 border border-purple-200 text-purple-800 px-3 font-bold hover:bg-purple-200 transition">📂</button>
                    </div>
                    <div class="mt-2 h-32 bg-gray-100 rounded-lg border border-gray-200 overflow-hidden flex items-center justify-center">
                        <img id="thumb-preview" src="<?= !empty($post['thumbnail']) ? htmlspecialchars($post['thumbnail']) : 'https://via.placeholder.com/400x250?text=Brak+Miniaturki' ?>" class="w-full h-full object-cover">
                    </div>
                </div>

                <div class="border-t pt-5">
                    <label class="block text-sm font-bold mb-1 text-gray-800">Zajawka (Skrócony Opis)</label>
                    <p class="text-[11px] text-gray-500 mb-2 leading-tight">Tekst wprowadzający widoczny na liście postów i używany w SEO.</p>
                    <textarea id="postExcerpt" class="w-full border border-gray-300 p-3 rounded-lg text-sm h-28 focus:ring-2 focus:ring-blue-500 outline-none shadow-sm placeholder-gray-400" placeholder="Krótkie streszczenie wpisu..."><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                </div>

                <div class="border-t pt-5">
                    <label class="block text-sm font-bold mb-1 text-gray-800">Tagi</label>
                    <p class="text-[11px] text-gray-500 mb-2 leading-tight">Oddziel przecinkami (np. wakacje, promocja)</p>
                    <input type="text" id="postTags" value="<?= htmlspecialchars($post['tags'] ?? '') ?>" class="w-full border border-gray-300 p-2.5 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none shadow-sm text-sm" placeholder="tag1, tag2...">
                </div>

                <div class="border-t pt-5">
                    <label class="block text-sm font-bold mb-1 text-gray-800">Bezpieczny URL (Slug)</label>
                    <p class="text-[11px] text-gray-500 mb-2 leading-tight">Generowany automatycznie, chyba że go nadpiszesz.</p>
                    <input type="text" id="postSlug" value="<?= htmlspecialchars($post['slug']) ?>" class="w-full border border-gray-300 bg-gray-50 p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm text-gray-500" placeholder="moj-nowy-wpis">
                </div>

            </div>
        </div>

    </div>

    <div id="mediaPickerModal" class="hidden fixed inset-0 bg-black/80 z-[110] flex justify-center items-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden">
            <div class="flex justify-between items-center p-4 border-b bg-gray-50">
                <h3 class="font-bold text-lg text-gray-800">Wybierz plik</h3>
                <button onclick="window.closeMediaPicker()" class="text-gray-400 hover:text-red-500 text-2xl font-bold">&times;</button>
            </div>
            <div class="flex-1">
                <iframe id="mediaPickerFrame" class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </div>

    <script>
        // Przekazanie danych do Buildera
        window.CMS_CONFIG = {
            pageId: <?= $post['id'] ?>,
            savedContent: JSON.parse(decodeURIComponent('<?= rawurlencode($post['contents'] ?: '[]') ?>')),
            availableForms: <?= json_encode($forms ?? []) ?>,
            availableGalleries: <?= json_encode($galleries ?? []) ?>,
            availableCategories: <?= json_encode($categories ?? []) ?>
        };

        // Główna funkcja zapisująca wpis
        function savePost(statusToSave) {
            // Zabezpieczenie przed kliknięciem
            const btnDraft = document.getElementById('btn-draft');
            const btnPublish = document.getElementById('btn-publish');
            
            const originalDraftText = btnDraft.innerHTML;
            const originalPublishText = btnPublish.innerHTML;

            if (statusToSave === 'draft') btnDraft.innerHTML = '⏳ Zapisywanie...';
            if (statusToSave === 'published') btnPublish.innerHTML = '⏳ Publikowanie...';
            
            btnDraft.disabled = true;
            btnPublish.disabled = true;

            // Wyciągamy bloki z edytora przy pomocy globalnej funkcji z page-builder.js
            const content = window.getBlocksFromContainer(document.getElementById('editor'));
            
            const payload = {
                id: window.CMS_CONFIG.pageId,
                title: document.getElementById('pageTitle').value,
                slug: document.getElementById('postSlug').value,
                category_id: document.getElementById('postCategory').value,
                excerpt: document.getElementById('postExcerpt').value,
                tags: document.getElementById('postTags').value,
                thumbnail: document.getElementById('postThumbnail').value,
                status: statusToSave, // 'draft' lub 'published'
                content: content
            };

            fetch('/admin/posts/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(res => res.json()).then(data => {
                if (data.status === 'success') {
                    // Pokaż, że się udało
                    if (statusToSave === 'draft') {
                        btnDraft.innerHTML = '✅ Zapisano Szkic!';
                        btnDraft.classList.add('bg-green-100', 'text-green-800', 'border-green-300');
                    } else {
                        btnPublish.innerHTML = '✅ Opublikowano!';
                        btnPublish.classList.replace('bg-green-600', 'bg-emerald-500');
                    }
                    
                    // Powrót do normy i odświeżenie strony po sekundzie, aby zaktualizować "Obecnie: status" na górze
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Wystąpił błąd podczas zapisywania.');
                    btnDraft.innerHTML = originalDraftText;
                    btnPublish.innerHTML = originalPublishText;
                    btnDraft.disabled = false;
                    btnPublish.disabled = false;
                }
            }).catch(err => {
                alert('Błąd połączenia z serwerem.');
                btnDraft.disabled = false;
                btnPublish.disabled = false;
            });
        }
    </script>
    <script src="<?= \CMS\Helpers\Asset::url('/assets/js/admin/page-builder.js') ?>"></script>
</body>
</html>