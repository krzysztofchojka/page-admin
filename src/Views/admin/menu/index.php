<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Manager</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style>
        .sub-menu-list {
            min-height: 15px;
            padding-left: 24px;
            margin-top: 8px;
            border-left: 2px dashed #cbd5e1;
        }
        .nested-sortable-ghost {
            opacity: 0.4;
            background-color: #f8fafc;
            border: 2px dashed #94a3b8;
        }
    </style>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-4xl mx-auto flex flex-col md:flex-row gap-6">
        
        <div class="w-full md:w-1/3 bg-white p-6 rounded shadow h-fit sticky top-6">
            <h2 class="font-bold text-xl mb-4">Dodaj Pozycję</h2>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Etykieta (Nazwa)</label>
                <input type="text" id="label" class="w-full border p-2 rounded" placeholder="np. Kontakt">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Link do:</label>
                <select id="pageSelect" onchange="updateUrlFromSelect()" class="w-full border p-2 rounded mb-2 bg-gray-50">
                    <option value="">-- Wybierz Stronę --</option>
                    <?php foreach ($pages as $p): ?>
                        <option value="/<?= htmlspecialchars($p['slug'] ?: 'page?id='.$p['id']) ?>">
                            <?= htmlspecialchars($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="url" class="w-full border p-2 rounded bg-gray-50" placeholder="https://... lub /slug">
            </div>
            <button onclick="addItem()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded w-full font-bold shadow transition">Dodaj do Menu</button>
            <a href="/admin" class="block text-center mt-4 text-gray-500 font-bold hover:text-gray-800">Wróć do Dashboardu</a>
        </div>

        <div class="w-full md:w-2/3">
            <h2 class="font-bold text-xl mb-4">Struktura Menu (Przeciągnij, aby poukładać)</h2>
            <p class="text-sm text-gray-500 mb-4">Przeciągaj elementy w prawo, aby tworzyć podkategorie (zagnieżdżone pozycje).</p>
            
            <div id="menu-list" class="space-y-2 pb-24">
                <?php
                if (!function_exists('renderAdminMenu')) {
                    function renderAdminMenu($items) {
                        foreach ($items as $item) {
                            echo '<div class="menu-item bg-gray-50 p-2 rounded-lg border border-gray-200 cursor-move group mb-2">';
                            echo '  <div class="bg-white p-3 rounded shadow-sm flex justify-between items-center hover:border-blue-400 border border-transparent transition">';
                            echo '      <div>';
                            echo '          <span class="font-bold block item-label text-gray-800">'.htmlspecialchars($item['label']).'</span>';
                            echo '          <span class="text-xs text-gray-400 item-url">'.htmlspecialchars($item['url']).'</span>';
                            echo '      </div>';
                            echo '      <button onclick="this.closest(\'.menu-item\').remove()" class="text-red-400 hover:text-red-600 font-bold p-2 bg-red-50 hover:bg-red-100 rounded transition">✕</button>';
                            echo '  </div>';
                            echo '  <div class="sub-menu-list space-y-2">';
                            if (!empty($item['children'])) {
                                renderAdminMenu($item['children']);
                            }
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                }
                renderAdminMenu($menuItems ?? []);
                ?>
            </div>
            
            <button onclick="saveMenu()" class="fixed bottom-10 right-10 bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-xl shadow-2xl font-bold text-lg transition z-50">💾 Zapisz Zmiany</button>
        </div>
    </div>

    <script>
        // Inicjalizacja zagnieżdżonego SortableJS
        function initNestedSortable() {
            const config = {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'nested-sortable-ghost'
            };

            Sortable.create(document.getElementById('menu-list'), config);

            document.querySelectorAll('.sub-menu-list').forEach(function(el) {
                Sortable.create(el, config);
            });
        }

        document.addEventListener("DOMContentLoaded", initNestedSortable);

        function updateUrlFromSelect() {
            const val = document.getElementById('pageSelect').value;
            if(val) document.getElementById('url').value = val;
        }

        function addItem() {
            const label = document.getElementById('label').value;
            const url = document.getElementById('url').value;

            if(!label || !url) return alert("Uzupełnij etykietę i adres URL!");

            const div = document.createElement('div');
            div.className = "menu-item bg-gray-50 p-2 rounded-lg border border-gray-200 cursor-move group mb-2";
            div.innerHTML = `
                <div class="bg-white p-3 rounded shadow-sm flex justify-between items-center hover:border-blue-400 border border-transparent transition">
                    <div>
                        <span class="font-bold block item-label text-gray-800">${label}</span>
                        <span class="text-xs text-gray-400 item-url">${url}</span>
                    </div>
                    <button onclick="this.closest('.menu-item').remove()" class="text-red-400 hover:text-red-600 font-bold p-2 bg-red-50 hover:bg-red-100 rounded transition">✕</button>
                </div>
                <div class="sub-menu-list space-y-2"></div>
            `;

            document.getElementById('menu-list').appendChild(div);

            // Inicjalizuj nową strefę drop
            Sortable.create(div.querySelector('.sub-menu-list'), {
                group: 'nested',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                ghostClass: 'nested-sortable-ghost'
            });

            // Wyczyszczenie wejść
            document.getElementById('label').value = '';
            document.getElementById('url').value = '';
            document.getElementById('pageSelect').value = '';
        }

        function saveMenu() {
            // Rekurencyjnie zbieramy całe zagnieżdżenie menu z UI
            const getItems = (container) => {
                const items = [];
                Array.from(container.children).forEach(el => {
                    if (el.classList.contains('menu-item')) {
                        const subList = el.querySelector('.sub-menu-list');
                        items.push({
                            label: el.querySelector('.item-label').textContent,
                            url: el.querySelector('.item-url').textContent,
                            children: subList ? getItems(subList) : []
                        });
                    }
                });
                return items;
            };

            const items = getItems(document.getElementById('menu-list'));

            fetch('/admin/menu/save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ items: items })
            })
            .then(() => alert('Sukces! Zapisano układ menu. Odśwież stronę główną by zobaczyć efekt.'));
        }
    </script>
</body>
</html>