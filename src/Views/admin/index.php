<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Prosta animacja zwijania paska */
        #sidebar { transition: width 0.3s ease; }
        .collapsed { width: 4rem !important; }
        .collapsed .menu-text { display: none; }
    </style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">

    <aside id="sidebar" class="w-64 bg-gray-900 text-white flex flex-col shadow-xl z-20">
        <div class="p-4 flex justify-between items-center border-b border-gray-800">
            <span class="font-bold text-lg menu-text truncate">CMS Admin</span>
            <button onclick="document.getElementById('sidebar').classList.toggle('collapsed')" class="text-gray-400 hover:text-white">
                ☰
            </button>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            <a href="/admin/pages" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>📄</span> <span class="menu-text">Strony</span>
            </a>
            <a href="/admin/forms" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>📝</span> <span class="menu-text">Formularze</span>
            </a>
            <a href="/admin/galleries" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>🖼️</span> <span class="menu-text">Galerie</span>
            </a>
            <a href="/admin/media" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>📂</span> <span class="menu-text">Pliki (Media)</span>
            </a>
            <a href="/admin/menu" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>🍔</span> <span class="menu-text">Menu Strony</span>
            </a>
            <a href="/admin/users" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>👥</span> <span class="menu-text">Użytkownicy</span>
            </a>
            <a href="/admin/settings" class="block px-4 py-3 hover:bg-gray-800 transition flex items-center gap-3">
                <span>⚙️</span> <span class="menu-text">Ustawienia</span>
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800 text-sm">
            <div class="menu-text mb-2 text-gray-400">Zalogowano: <b><?= htmlspecialchars(\CMS\Core\Session::get('user_name')) ?></b></div>
            <a href="/logout" class="text-red-400 hover:text-red-300 flex items-center gap-3">
                <span>🚪</span> <span class="menu-text">Wyloguj</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 flex flex-col bg-gray-50 relative">
        <div class="bg-white p-3 shadow-sm flex justify-between items-center z-10">
            <span class="text-gray-500 font-bold text-sm uppercase tracking-wide">Podgląd na żywo</span>
            <button onclick="document.getElementById('preview-frame').contentWindow.location.reload();" class="text-blue-600 text-sm hover:underline">🔄 Odśwież podgląd</button>
        </div>
        <div class="flex-1 p-4">
            <div class="w-full h-full bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <iframe id="preview-frame" src="/" class="w-full h-full border-0"></iframe>
            </div>
        </div>
    </main>

</body>
</html>