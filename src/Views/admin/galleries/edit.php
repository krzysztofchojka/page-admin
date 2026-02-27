<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edycja Galerii</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <div class="w-80 bg-white border-r shadow-lg flex flex-col shrink-0 z-40 overflow-y-auto">
        <div class="p-4 bg-gray-50 border-b font-bold text-gray-700 uppercase tracking-wide text-sm flex justify-between items-center">
            <span>Konfiguracja</span>
            <span class="text-xl">⚙️</span>
        </div>
        
        <div class="p-5 space-y-5">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Tytuł Galerii</label>
                <input type="text" id="title" value="<?= htmlspecialchars($gallery['title']) ?>" class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Pre-konfiguracja (Typ)</label>
                <select id="type" class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none bg-gray-50" onchange="toggleSettings()">
                    <option value="grid" <?= $gallery['type']=='grid'?'selected':'' ?>>Siatka Grid (Styl Facebook)</option>
                    <option value="swiper_default" <?= $gallery['type']=='swiper_default'?'selected':'' ?>>Swiper: Domyślny Slider</option>
                    <option value="swiper_coverflow" <?= $gallery['type']=='swiper_coverflow'?'selected':'' ?>>Swiper: 3D Coverflow</option>
                    <option value="swiper_fade" <?= $gallery['type']=='swiper_fade'?'selected':'' ?>>Swiper: Przenikanie (Fade)</option>
                    <option value="swiper_cards" <?= $gallery['type']=='swiper_cards'?'selected':'' ?>>Swiper: Karty (Cards)</option>
                </select>
            </div>

            <div id="swiper-settings" class="space-y-4 border-t pt-4 hidden">
                <h4 class="font-bold text-gray-500 uppercase text-xs">Opcje Swiper JS</h4>
                
                <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                    <input type="checkbox" id="set_loop" class="w-4 h-4 text-blue-600 rounded">
                    Zapetlaj slajdy (Loop)
                </label>
                
                <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                    <input type="checkbox" id="set_nav" class="w-4 h-4 text-blue-600 rounded">
                    Strzałki nawigacji
                </label>
                
                <label class="flex items-center gap-2 cursor-pointer text-sm font-medium">
                    <input type="checkbox" id="set_pag" class="w-4 h-4 text-blue-600 rounded">
                    Kropki paginacji
                </label>

                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1">Autoplay (ms, 0 = wyłączone)</label>
                    <input type="number" id="set_autoplay" value="3500" class="w-full border border-gray-300 p-2 rounded text-sm focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>
    </div>

    <div class="flex-1 flex flex-col overflow-hidden bg-gray-50">
        <div class="bg-white shadow-sm p-4 flex justify-between items-center z-10">
            <div>
                <a href="/admin/galleries" class="text-gray-500 hover:text-black font-bold mr-4">← Wróć</a>
                <span class="text-xl font-bold text-gray-800">Zdjęcia w galerii</span>
            </div>
            <div class="flex gap-2">
                <button onclick="openMediaPicker()" class="bg-purple-100 hover:bg-purple-200 text-purple-700 border border-purple-200 px-4 py-2 rounded-lg font-bold transition flex items-center gap-2">
                    📂 Wybierz z Media
                </button>
                <button onclick="document.getElementById('fileInput').click()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg font-bold transition flex items-center gap-2">
                    ☁️ Wgraj Nowe
                </button>
                <button id="btn-save" onclick="saveGallery()" class="ml-4 bg-green-600 hover:bg-green-700 text-white px-8 py-2 rounded-lg shadow font-bold transition">
                    💾 Zapisz Galerie
                </button>
            </div>
            <input type="file" id="fileInput" multiple accept="image/*" class="hidden">
        </div>

        <div class="flex-1 overflow-y-auto p-8 relative">
            <div id="image-grid" class="max-w-5xl mx-auto grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 content-start min-h-[400px] border-2 border-dashed border-gray-300 rounded-xl p-6 bg-gray-50/50">
                </div>
        </div>
    </div>

    <div id="globalMediaModal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-[100] flex justify-center items-center p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl h-[80vh] flex flex-col overflow-hidden">
        <div class="flex justify-between items-center p-4 border-b bg-gray-50">
            <h3 class="font-bold text-lg">Wybierz plik</h3>
            <button onclick="closeGlobalMediaPicker()" class="text-red-500 font-bold text-xl">&times;</button>
        </div>
        <div class="flex-1">
            <iframe id="globalMediaFrame" class="w-full h-full border-0"></iframe>
        </div>
    </div>
</div>

<script>
    const galleryId = <?= $gallery['id'] ?>;
    const savedImages = <?= $gallery['images_json'] ?: '[]' ?>;
    const savedSettings = <?= $gallery['settings'] ?: '{}' ?>;
    const grid = document.getElementById('image-grid');

    // Inicjalizacja Sortable
    Sortable.create(grid, { animation: 150, ghostClass: 'opacity-50' });
    savedImages.forEach(url => renderImage(url));

    // Wczytanie ustawień
    if(savedSettings.loop) document.getElementById('set_loop').checked = true;
    if(savedSettings.nav) document.getElementById('set_nav').checked = true;
    if(savedSettings.pag) document.getElementById('set_pag').checked = true;
    if(savedSettings.autoplay !== undefined) document.getElementById('set_autoplay').value = savedSettings.autoplay;

    function toggleSettings() {
        const type = document.getElementById('type').value;
        const panel = document.getElementById('swiper-settings');
        if (type.startsWith('swiper_')) {
            panel.classList.remove('hidden');
        } else {
            panel.classList.add('hidden');
        }
    }
    toggleSettings(); // Wywołanie na start

    // --- INTEGRACJA Z MEDIA PICKEREM ---
 // --- INTEGRACJA Z MEDIA PICKEREM ---
function openMediaPicker() {
    window.activeMediaInput = true; // Wystarczy ustawić zwykłą flagę
    document.getElementById('globalMediaModal').classList.remove('hidden');
    document.getElementById('globalMediaFrame').src = '/admin/media?picker=1';
}

function closeGlobalMediaPicker() {
    document.getElementById('globalMediaModal').classList.add('hidden');
    document.getElementById('globalMediaFrame').src = '';
    window.activeMediaInput = null;
}

window.addEventListener('message', function(e) {
    if (!e.data || !window.activeMediaInput) return;

    // Odbiór pojedynczego pliku (podwójne kliknięcie)
    if (e.data.type === 'media_selected') {
        renderImage(e.data.url);
        closeGlobalMediaPicker();
    }

    // Odbiór wielu plików naraz (użycie nowego przycisku)
    if (e.data.type === 'media_selected_multiple') {
        e.data.urls.forEach(url => renderImage(url)); // Pętla renderująca wszystkie wybrane zdjęcia!
        closeGlobalMediaPicker();
    }
});

    // --- STANDARDOWY UPLOAD ---
    document.getElementById('fileInput').addEventListener('change', function() {
        for (let i = 0; i < this.files.length; i++) {
            uploadOne(this.files[i]);
        }
    });

    function uploadOne(file) {
        const formData = new FormData();
        formData.append('file', file);
        fetch('/admin/media/upload', {
            method: 'POST', body: formData
        })
        .then(res => res.json())
        .then(data => {
            if(data.url) renderImage(data.url);
        });
    }

    function renderImage(url) {
        const div = document.createElement('div');
        div.className = "relative group aspect-square bg-gray-200 rounded-xl overflow-hidden cursor-move shadow-sm border border-gray-200";
        div.innerHTML = `
            <img src="${url}" class="w-full h-full object-cover">
            <button onclick="this.closest('div').remove()" class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white w-8 h-8 rounded-full opacity-0 group-hover:opacity-100 transition shadow font-bold text-sm">✕</button>
            <input type="hidden" class="img-url" value="${url}">
        `;
        grid.appendChild(div);
    }

    function saveGallery() {
        const btn = document.getElementById('btn-save');
        const originalText = btn.innerHTML;
        
        // Zabezpieczenie przed wielokrotnym kliknięciem
        btn.innerHTML = '⏳ Zapisywanie...';
        btn.disabled = true;
        btn.classList.add('opacity-75');

        const images = [];
        grid.querySelectorAll('.img-url').forEach(input => images.push(input.value));
        
        const settings = {
            loop: document.getElementById('set_loop').checked,
            nav: document.getElementById('set_nav').checked,
            pag: document.getElementById('set_pag').checked,
            autoplay: parseInt(document.getElementById('set_autoplay').value) || 0
        };

        fetch('/admin/galleries/save', {
            method: 'POST',
            body: JSON.stringify({
                id: galleryId,
                title: document.getElementById('title').value,
                type: document.getElementById('type').value,
                settings: settings,
                images: images
            })
        }).then(() => {
            // Efekt sukcesu
            btn.innerHTML = '✅ Zapisano!';
            btn.classList.remove('bg-green-600', 'hover:bg-green-700', 'opacity-75');
            btn.classList.add('bg-emerald-500', 'hover:bg-emerald-600');
            
            // Powrót do normy po 2 sekundach
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.classList.remove('bg-emerald-500', 'hover:bg-emerald-600');
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
                btn.disabled = false;
            }, 2000);
        });
    }
</script>
</body>
</html>