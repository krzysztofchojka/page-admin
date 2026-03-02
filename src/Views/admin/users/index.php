<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Zarządzanie Użytkownikami</h1>
        <a href="/admin" class="text-gray-600 hover:text-black">← Wróć do Dashboardu</a>
    </div>

    <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 font-bold shadow-sm">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div class="md:col-span-1">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="text-lg font-bold mb-4 text-gray-800">Nowy Administrator</h2>
                <form action="/admin/users/create" method="POST">
                    <div class="mb-4">
                        <label class="block text-xs font-bold mb-2 text-gray-500 uppercase">Login</label>
                        <input type="text" name="username" required class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-bold mb-2 text-gray-500 uppercase">Email (Powiadomienia)</label>
                        <input type="email" name="email" required class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <div class="mb-6">
                        <label class="block text-xs font-bold mb-2 text-gray-500 uppercase">Hasło</label>
                        <input type="password" name="password" required class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none text-sm">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded shadow transition">
                        Utwórz Admina
                    </button>
                </form>
            </div>
        </div>

        <div class="md:col-span-3">
            <div class="bg-white shadow-sm rounded-xl overflow-hidden border border-gray-200">
                
                <div class="flex border-b border-gray-200 bg-gray-50">
                    <button onclick="switchTab('users')" id="btn-users" class="flex-1 py-4 font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition">
                        Zwykli Użytkownicy (<?= count($regularUsers) ?>)
                    </button>
                    <button onclick="switchTab('admins')" id="btn-admins" class="flex-1 py-4 font-bold text-gray-500 hover:text-blue-600 transition border-b-2 border-transparent">
                        Administratorzy (<?= count($admins) ?>)
                    </button>
                </div>

                <div id="tab-users" class="block">
                    <table class="min-w-full leading-normal text-left">
                        <thead class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                            <tr>
                                <th class="px-5 py-3">ID</th>
                                <th class="px-5 py-3">Login</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($regularUsers as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4 text-sm text-gray-500"><?= $user['id'] ?></td>
                                <td class="px-5 py-4 text-sm font-bold text-gray-800"><?= htmlspecialchars($user['uname']) ?></td>
                                <td class="px-5 py-4 text-sm text-gray-500"><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <a href="/admin/users/edit?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold mr-3">Edytuj</a>
                                    <a href="/admin/users/delete?id=<?= $user['id'] ?>" class="text-red-500 hover:text-red-700 font-bold" onclick="return confirm('Usunąć tego użytkownika?');">Usuń</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($regularUsers)): ?>
                                <tr><td colspan="4" class="p-6 text-center text-gray-500">Brak użytkowników.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="tab-admins" class="hidden">
                    <table class="min-w-full leading-normal text-left">
                        <thead class="bg-gray-50 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase">
                            <tr>
                                <th class="px-5 py-3">ID</th>
                                <th class="px-5 py-3">Login</th>
                                <th class="px-5 py-3">Email</th>
                                <th class="px-5 py-3">Akcje</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($admins as $user): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-5 py-4 text-sm text-gray-500"><?= $user['id'] ?></td>
                                <td class="px-5 py-4 text-sm font-bold text-gray-800">
                                    <?= htmlspecialchars($user['uname']) ?>
                                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="ml-2 text-[10px] bg-green-100 text-green-800 px-2 py-0.5 rounded-full uppercase">To Ty</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-500"><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <a href="/admin/users/edit?id=<?= $user['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold mr-3">Edytuj</a>
                                    <?php if($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="/admin/users/delete?id=<?= $user['id'] ?>" class="text-red-500 hover:text-red-700 font-bold" onclick="return confirm('Usunąć administratora?');">Usuń</a>
                                    <?php else: ?>
                                        <span class="text-gray-300">Usuń</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName) {
        document.getElementById('tab-users').classList.add('hidden');
        document.getElementById('tab-admins').classList.add('hidden');
        document.getElementById('btn-users').className = 'flex-1 py-4 font-bold text-gray-500 hover:text-blue-600 transition border-b-2 border-transparent bg-gray-50';
        document.getElementById('btn-admins').className = 'flex-1 py-4 font-bold text-gray-500 hover:text-blue-600 transition border-b-2 border-transparent bg-gray-50';
        
        document.getElementById('tab-' + tabName).classList.remove('hidden');
        document.getElementById('btn-' + tabName).className = 'flex-1 py-4 font-bold text-blue-600 border-b-2 border-blue-600 bg-white transition';
    }
</script>