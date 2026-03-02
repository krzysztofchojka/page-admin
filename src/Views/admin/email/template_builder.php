<div class="max-w-5xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Kreator Szablonu</h1>
        <a href="/admin/email/templates" class="text-gray-500 hover:text-black font-bold">← Wróć do listy</a>
    </div>

    <form action="/admin/email/templates/save" method="POST" id="emailForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if(isset($template)): ?>
            <input type="hidden" name="id" value="<?= $template['id'] ?>">
        <?php endif; ?>
        
        <div class="md:col-span-2 flex flex-col gap-6">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Tytuł Roboczy</label>
                    <input type="text" name="title" value="<?= htmlspecialchars($template['title'] ?? '') ?>" class="w-full border p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Temat Wiadomości</label>
                    <input type="text" name="subject" value="<?= htmlspecialchars($template['subject'] ?? '') ?>" class="w-full border p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none" required>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <label class="block text-sm font-bold text-gray-700 mb-2">Treść Wiadomości (HTML)</label>
                <div id="quill-editor" class="h-[400px] bg-gray-50 rounded-b-lg"><?= $template['body'] ?? '' ?></div>
                <textarea name="body" id="hiddenArea" class="hidden"></textarea>
            </div>
        </div>

        <div class="flex flex-col gap-6">
            <div class="bg-blue-50 p-6 rounded-xl shadow-sm border border-blue-100">
                <h3 class="font-bold text-blue-800 mb-3 text-sm uppercase">Zmienne / Tagi</h3>
                <p class="text-xs text-blue-600 mb-4">Kliknij tag, aby wstawić go w miejsce kursora w edytorze.</p>
                <div class="flex flex-col gap-2">
                    <button type="button" onclick="insertTag('{{uname}}')" class="bg-white border border-blue-200 hover:border-blue-400 text-blue-700 font-bold py-2 px-3 rounded shadow-sm transition text-left text-sm">
                        👤 {{uname}} <span class="font-normal text-gray-500 text-xs block">Imię / Nazwa użytkownika</span>
                    </button>
                    <button type="button" onclick="insertTag('{{email}}')" class="bg-white border border-blue-200 hover:border-blue-400 text-blue-700 font-bold py-2 px-3 rounded shadow-sm transition text-left text-sm">
                        📧 {{email}} <span class="font-normal text-gray-500 text-xs block">Adres email odbiorcy</span>
                    </button>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 sticky top-6">
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-lg shadow-md transition text-lg">
                    💾 Zapisz Szablon
                </button>
            </div>
        </div>
    </form>
</div>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill = new Quill('#quill-editor', { 
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ 'color': [] }, { 'background': [] }],
                [{ 'align': [] }],
                ['link', 'image', 'video'],
                ['clean']
            ]
        }
    });
    
    // Przed wysłaniem formularza przepisujemy zawartość Quilla do ukrytego pola
    document.getElementById('emailForm').onsubmit = function() {
        document.getElementById('hiddenArea').value = quill.root.innerHTML;
    };

    // Wstawianie tagów w miejsce kursora
    function insertTag(tag) {
        var range = quill.getSelection();
        if (range) {
            quill.insertText(range.index, tag);
            quill.setSelection(range.index + tag.length);
        } else {
            quill.insertText(quill.getLength(), tag);
        }
    }
</script>