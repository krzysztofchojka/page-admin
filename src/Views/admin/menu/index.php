<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-4xl mx-auto flex gap-6">
        
        <div class="w-1/3 bg-white p-6 rounded shadow">
            <h2 class="font-bold text-xl mb-4">Add Item</h2>
            
            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Label</label>
                <input type="text" id="label" class="w-full border p-2 rounded" placeholder="e.g. Contact Us">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-bold mb-2">Link To:</label>
                <select id="pageSelect" onchange="updateUrlFromSelect()" class="w-full border p-2 rounded mb-2">
                    <option value="">-- Select Page --</option>
                    <?php foreach ($pages as $p): ?>
                        <option value="/<?= htmlspecialchars($p['slug'] ?: 'page?id='.$p['id']) ?>">
                            <?= htmlspecialchars($p['title']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" id="url" class="w-full border p-2 rounded bg-gray-50" placeholder="https:// or /slug">
            </div>

            <button onclick="addItem()" class="bg-blue-600 text-white px-4 py-2 rounded w-full font-bold">Add to Menu</button>
            <a href="/admin" class="block text-center mt-4 text-gray-500">Back to Dashboard</a>
        </div>

        <div class="w-2/3">
            <h2 class="font-bold text-xl mb-4">Current Menu (Drag to Reorder)</h2>
            <div id="menu-list" class="space-y-2">
                <?php foreach ($menuItems as $item): ?>
                    <div class="menu-item bg-white p-3 rounded shadow flex justify-between items-center cursor-move group">
                        <div>
                            <span class="font-bold block item-label"><?= htmlspecialchars($item['label']) ?></span>
                            <span class="text-xs text-gray-500 item-url"><?= htmlspecialchars($item['url']) ?></span>
                        </div>
                        <button onclick="this.closest('.menu-item').remove()" class="text-red-400 hover:text-red-600">✕</button>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button onclick="saveMenu()" class="mt-6 bg-green-600 text-white px-6 py-3 rounded shadow font-bold float-right">Save Changes</button>
        </div>

    </div>

    <script>
        const list = document.getElementById('menu-list');
        Sortable.create(list, { animation: 150 });

        function updateUrlFromSelect() {
            const val = document.getElementById('pageSelect').value;
            if(val) document.getElementById('url').value = val;
        }

        function addItem() {
            const label = document.getElementById('label').value;
            const url = document.getElementById('url').value;

            if(!label || !url) return alert("Fill in Label and URL");

            const div = document.createElement('div');
            div.className = "menu-item bg-white p-3 rounded shadow flex justify-between items-center cursor-move group";
            div.innerHTML = `
                <div>
                    <span class="font-bold block item-label">${label}</span>
                    <span class="text-xs text-gray-500 item-url">${url}</span>
                </div>
                <button onclick="this.closest('.menu-item').remove()" class="text-red-400 hover:text-red-600">✕</button>
            `;
            list.appendChild(div);
            
            // Clear inputs
            document.getElementById('label').value = '';
            document.getElementById('url').value = '';
            document.getElementById('pageSelect').value = '';
        }

        function saveMenu() {
            const items = [];
            list.querySelectorAll('.menu-item').forEach(el => {
                items.push({
                    label: el.querySelector('.item-label').textContent,
                    url: el.querySelector('.item-url').textContent
                });
            });

            fetch('/admin/menu/save', {
                method: 'POST',
                body: JSON.stringify({ items: items })
            })
            .then(() => alert('Menu Saved! Location reload required to see changes on frontend.'));
        }
    </script>
</body>
</html>