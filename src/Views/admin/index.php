<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        #sidebar { transition: width 0.3s ease; }
        .collapsed { width: 4rem !important; }
        .collapsed .menu-text { display: none; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">
    
    <aside id="sidebar" class="w-64 bg-gray-900 text-white flex flex-col shadow-xl z-20 shrink-0">
        <div class="p-4 flex justify-between items-center border-b border-gray-800">
            <span class="font-bold text-lg menu-text truncate">CMS Admin</span>
            <button onclick="document.getElementById('sidebar').classList.toggle('collapsed')" class="text-gray-400 hover:text-white">☰</button>
        </div>
        <nav class="flex-1 overflow-y-auto py-4 space-y-1">
            <a href="/admin" class="block px-4 py-3 bg-gray-800 text-white border-l-4 border-blue-500 flex items-center gap-3">
                <span>📊</span> <span class="menu-text">Dashboard</span>
            </a>
            <a href="/admin/pages" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>📄</span> <span class="menu-text">Strony</span>
            </a>
            <a href="/admin/forms" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>📝</span> <span class="menu-text">Formularze</span>
            </a>
            <a href="/admin/galleries" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>🖼️</span> <span class="menu-text">Galerie</span>
            </a>
            <a href="/admin/templates" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>📐</span> <span class="menu-text">Szablony</span>
            </a>
            <a href="/admin/media" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>📂</span> <span class="menu-text">Pliki (Media)</span>
            </a>
            <a href="/admin/menu" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>🍔</span> <span class="menu-text">Menu Strony</span>
            </a>
            <a href="/admin/users" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>👥</span> <span class="menu-text">Użytkownicy</span>
            </a>
            <a href="/admin/email" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>✉️</span> <span class="menu-text">Email</span>
            </a>
            <a href="/admin/settings" class="block px-4 py-3 hover:bg-gray-800 text-gray-300 border-l-4 border-transparent transition flex items-center gap-3">
                <span>⚙️</span> <span class="menu-text">Ustawienia</span>
            </a>
            <a href="/admin/help" class="block px-4 py-3 hover:bg-gray-800 text-green-400 border-l-4 border-transparent transition flex items-center gap-3 mt-4 border-t border-gray-800">
                <span>💡</span> <span class="menu-text font-bold">Pomoc</span>
            </a>
        </nav>
        <div class="p-4 border-t border-gray-800 text-sm">
            <div class="menu-text mb-2 text-gray-400">Zalogowano: <b><?= htmlspecialchars(\CMS\Core\Session::get('user_name')) ?></b></div>
            <a href="/logout" class="text-red-400 hover:text-red-300 flex items-center gap-3">
                <span>🚪</span> <span class="menu-text">Wyloguj</span>
            </a>
        </div>
    </aside>

    <main class="flex-1 overflow-auto bg-gray-100 p-8">
        <div class="max-w-6xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Cześć, <?= htmlspecialchars(\CMS\Core\Session::get('user_name')) ?>! 👋</h1>
            <p class="text-gray-500 mb-8">Oto podsumowanie Twojej witryny na dziś.</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4 border-l-4 border-l-blue-500">
                    <div class="bg-blue-100 p-4 rounded-full text-blue-600 text-2xl">📄</div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">Podstrony</p>
                        <p class="text-3xl font-extrabold text-gray-800"><?= $stats['pages'] ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4 border-l-4 border-l-green-500">
                    <div class="bg-green-100 p-4 rounded-full text-green-600 text-2xl">📝</div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">Formularze</p>
                        <p class="text-3xl font-extrabold text-gray-800"><?= $stats['forms'] ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4 border-l-4 border-l-purple-500">
                    <div class="bg-purple-100 p-4 rounded-full text-purple-600 text-2xl">👥</div>
                    <div>
                        <p class="text-gray-500 text-sm font-bold uppercase">Użytkownicy</p>
                        <p class="text-3xl font-extrabold text-gray-800"><?= $stats['users'] ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden mb-8">
                <div class="bg-gray-50 border-b border-gray-200 p-4 flex justify-between items-center">
                    <h2 class="font-bold text-gray-700 text-lg">🔔 Ostatnie wiadomości z formularzy</h2>
                </div>
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs">Data</th>
                            <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs">Formularz</th>
                            <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs">Użytkownik</th>
                            <th class="px-6 py-3 text-gray-500 font-bold uppercase text-xs">Akcja</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($recentSubmissions)): ?>
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Brak nowych zgłoszeń.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentSubmissions as $sub): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 text-gray-600"><?= $sub['created_at'] ?></td>
                                    <td class="px-6 py-4 font-bold text-gray-800"><?= htmlspecialchars($sub['form_title']) ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($sub['user_email'] ?? 'Gość') ?></td>
                                    <td class="px-6 py-4">
                                        <a href="/admin/forms/submissions?id=<?= $sub['form_id'] ?>" class="text-blue-600 font-bold hover:underline">Otwórz folder zgłoszeń</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="text-center">
                <a href="/" target="_blank" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition">
                    🌍 Otwórz podgląd witryny w nowej karcie
                </a>
            </div>

        </div>
    </main>
</body>
</html>