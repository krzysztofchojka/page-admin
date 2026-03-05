<?php
// Pobieranie aktualnej ścieżki do podświetlania menu
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function isActive($path, $current) {
    return strpos($current, $path) === 0 ? 'bg-gray-800 text-white border-l-4 border-blue-500' : 'hover:bg-gray-800 text-gray-300 border-l-4 border-transparent';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CMS Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <style>
        #sidebar { transition: width 0.3s ease; }
        .collapsed { width: 4rem !important; }
        .collapsed .menu-text { display: none; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <aside id="sidebar" class="w-64 bg-gray-900 flex flex-col shadow-xl z-20 shrink-0">
        <div class="p-4 flex justify-between items-center border-b border-gray-800 text-white">
            <span class="font-bold text-lg menu-text truncate">CMS Admin</span>
            <button onclick="document.getElementById('sidebar').classList.toggle('collapsed')" class="text-gray-400 hover:text-white">☰</button>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            <a href="/admin" class="block px-4 py-3 transition flex items-center gap-3 <?= $currentPath == '/admin' ? 'bg-gray-800 text-white border-l-4 border-blue-500' : 'hover:bg-gray-800 text-gray-300 border-l-4 border-transparent' ?>">
                <span>📊</span> <span class="menu-text">Dashboard</span>
            </a>
            <a href="/admin/pages" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/pages', $currentPath) ?>">
                <span>📄</span> <span class="menu-text">Strony</span>
            </a>
            <a href="/admin/forms" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/forms', $currentPath) ?>">
                <span>📝</span> <span class="menu-text">Formularze</span>
            </a>
            <a href="/admin/galleries" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/galleries', $currentPath) ?>">
                <span>🖼️</span> <span class="menu-text">Galerie</span>
            </a>
            <a href="/admin/templates" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/templates', $currentPath) ?>">
                <span>📐</span> <span class="menu-text">Szablony</span>
            </a>
            <a href="/admin/media" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/media', $currentPath) ?>">
                <span>📂</span> <span class="menu-text">Pliki (Media)</span>
            </a>
            <a href="/admin/menu" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/menu', $currentPath) ?>">
                <span>🍔</span> <span class="menu-text">Menu Strony</span>
            </a>
            <a href="/admin/posts" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/posts', $currentPath) ?>">
                <span>📰</span> <span class="menu-text">Wpisy (Blog)</span>
            </a>
            <a href="/admin/users" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/users', $currentPath) ?>">
                <span>👥</span> <span class="menu-text">Użytkownicy</span>
            </a>
            <a href="/admin/email" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/email', $currentPath) ?>">
                <span>✉️</span> <span class="menu-text">Email</span>
            </a>
            <a href="/admin/settings" class="block px-4 py-3 transition flex items-center gap-3 <?= isActive('/admin/settings', $currentPath) ?>">
                <span>⚙️</span> <span class="menu-text">Ustawienia</span>
            </a>
            <a href="/admin/help" class="block px-4 py-3 hover:bg-gray-800 text-green-400 transition flex items-center gap-3 mt-4 border-t border-gray-800">
                <span>💡</span> <span class="menu-text font-bold">Pomoc</span>
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800 text-sm">
            <div class="menu-text mb-2 text-gray-400">User: <b><?= htmlspecialchars(\CMS\Core\Session::get('user_name')) ?></b></div>
            <a href="/logout" class="text-red-400 hover:text-red-300 flex items-center gap-3">
                <span>🚪</span> <span class="menu-text">Wyloguj</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-auto bg-gray-100 p-8">
        <?= $content ?? '' ?>
    </main>

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
        // --- LOGIKA GLOBALNEGO MEDIA PICKERA ---
        let activeMediaInput = null;

        function closeGlobalMediaPicker() {
            document.getElementById('globalMediaModal').classList.add('hidden');
            document.getElementById('globalMediaFrame').src = '';
            activeMediaInput = null;
        }

        // Nasłuchiwanie wyboru z iFrame
        window.addEventListener('message', function(e) {
            if (!e.data || !activeMediaInput) return;
            
            let url = null;
            if (e.data.type === 'media_selected') url = e.data.url;
            if (e.data.type === 'media_selected_multiple') url = e.data.urls[0]; // Bierzemy pierwsze z zaznaczonych

            if (url) {
                activeMediaInput.value = url;
                activeMediaInput.dispatchEvent(new Event('change', { bubbles: true }));
                closeGlobalMediaPicker();
            }
        });

        // Automatyczne dodawanie przycisku 📂 do każdego inputa z klasą 'media-input'
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('input.media-input').forEach(input => {
                // Jeśli już ma guzik (np. w dynamicznych blokach), pomiń
                if (input.dataset.pickerInit) return;
                input.dataset.pickerInit = 'true';

                const wrapper = document.createElement('div');
                wrapper.className = 'flex w-full';
                input.parentNode.insertBefore(wrapper, input);
                wrapper.appendChild(input);

                input.classList.remove('rounded');
                input.classList.add('rounded-l', 'border-r-0');

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bg-purple-100 hover:bg-purple-200 border border-purple-200 text-purple-800 px-3 text-sm font-bold transition rounded-r';
                btn.innerHTML = '📂';
                btn.title = 'Wybierz z menedżera mediów';
                
                btn.onclick = () => {
                    activeMediaInput = input;
                    document.getElementById('globalMediaModal').classList.remove('hidden');
                    document.getElementById('globalMediaFrame').src = '/admin/media?picker=1';
                };
                
                wrapper.appendChild(btn);
            });
        });
    </script>
</body>
</html>