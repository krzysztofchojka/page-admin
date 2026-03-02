<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Szablony Email</h1>
        <a href="/admin/email/templates/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">+ Nowy Szablon</a>
    </div>
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">ID</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Tytuł Roboczy</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Temat Emaila</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Data utworzenia</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($templates as $t): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500"><?= $t['id'] ?></td>
                        <td class="px-6 py-4 font-bold text-gray-800"><?= htmlspecialchars($t['title']) ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($t['subject']) ?></td>
                        <td class="px-6 py-4 text-gray-500"><?= $t['created_at'] ?></td>
                        <td class="px-6 py-4 text-right">
                            <a href="/admin/email/templates/edit?id=<?= $t['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold bg-blue-50 px-3 py-1.5 rounded transition">Edytuj</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($templates)): ?>
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">Brak szablonów.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>