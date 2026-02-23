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
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tytuł Strony</th>
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
                    <td class="px-6 py-4 text-sm text-gray-500"><?= $page['edit_date'] ?></td>
                    <td class="px-6 py-4 text-sm">
                        <a href="/admin/pages/edit?id=<?= $page['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold mr-3">Edytuj</a>
                        <a href="/admin/pages/delete?id=<?= $page['id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Czy na pewno usunąć tę stronę?')">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($pages)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">Brak stron. Utwórz pierwszą!</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>