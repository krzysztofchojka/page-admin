<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Zarządzanie Stronami</h1>
        <div>
            <a href="/admin" class="text-gray-600 hover:text-gray-900 mr-4 font-medium">Wróć do Dashboardu</a>
            <a href="/admin/pages/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow-sm transition">
                + Utwórz Nową Stronę
            </a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden my-6">
        <?php 
            // Pobranie ustawień przypisanych stron
            $footerSetting = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'footer_page_id'")->fetch();
            $footerPageId = $footerSetting ? $footerSetting['setting_value'] : null;

            $loginSetting = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'login_page_id'")->fetch();
            $loginPageId = $loginSetting ? $loginSetting['setting_value'] : null;

            $registerSetting = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'register_page_id'")->fetch();
            $registerPageId = $registerSetting ? $registerSetting['setting_value'] : null;

            $pwdSetting = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'change_password_page_id'")->fetch();
            $changePwdPageId = $pwdSetting ? $pwdSetting['setting_value'] : null;

            $ldSetting = $db->query("SELECT setting_value FROM pa_settings WHERE setting_key = 'lockdown_page_id'")->fetch();
            $lockdownPageId = $ldSetting ? $ldSetting['setting_value'] : null;
        ?>
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tytuł Strony</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">URL</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Ostatnia Edycja</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($pages as $page): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-500"><?= $page['id'] ?></td>
                    <td class="px-6 py-4 text-sm font-bold text-gray-800">
                        <?= htmlspecialchars($page['title']) ?>
                        <?php if (!empty($page['template_id'])): ?>
                            <span class="ml-2 text-[10px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full uppercase">Szablon</span>
                        <?php endif; ?>
                    </td>
                    
                    <td class="px-6 py-4 text-sm text-gray-500">
    <?php if ($page['id'] == $footerPageId): ?>
        <span class="text-[10px] bg-cyan-100 text-cyan-800 border border-cyan-200 px-2 py-0.5 rounded-full uppercase font-bold shadow-sm">Globalna Stopka</span>
    <?php elseif ($page['id'] == $loginPageId): ?>
        <span class="text-[10px] bg-red-100 text-red-800 border border-red-200 px-2 py-0.5 rounded-full uppercase font-bold shadow-sm">Logowanie</span>
        <?php elseif ($page['id'] == $changePwdPageId): ?>
        <span class="text-[10px] bg-yellow-100 text-yellow-800 border border-yellow-200 px-2 py-0.5 rounded-full uppercase font-bold shadow-sm">Zmiana Hasła</span>
    <?php elseif ($page['id'] == $lockdownPageId): ?>
        <span class="text-[10px] bg-gray-800 text-gray-200 border border-gray-600 px-2 py-0.5 rounded-full uppercase font-bold shadow-sm">Lockdown</span>
    <?php elseif ($page['id'] == $registerPageId): ?>
        <span class="text-[10px] bg-orange-100 text-orange-800 border border-orange-200 px-2 py-0.5 rounded-full uppercase font-bold shadow-sm">Rejestracja</span>
    <?php else: ?>
        <?php $url = !empty($page['slug']) ? '/' . ltrim($page['slug'], '/') : '/page?id=' . $page['id']; ?>
        <a href="<?= htmlspecialchars($url) ?>" target="_blank" class="text-blue-500 hover:text-blue-700 hover:underline inline-flex items-center gap-1" title="Otwórz w nowej karcie">
            <?= htmlspecialchars($url) ?> <span class="text-xs">↗</span>
        </a>
    <?php endif; ?>
</td>

                    <td class="px-6 py-4 text-sm text-gray-500"><?= $page['edit_date'] ?></td>
                    <td class="px-6 py-4 text-sm">
                        <a href="/admin/pages/edit?id=<?= $page['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold mr-3">Edytuj</a>
                        <a href="/admin/pages/delete?id=<?= $page['id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Czy na pewno usunąć tę stronę?')">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">Brak stron. Utwórz pierwszą!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>