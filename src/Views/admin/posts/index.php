<div class="max-w-6xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Wpisy (Posty)</h1>
        <div>
            <a href="/admin/categories" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded mr-2">Kategorie</a>
            <a href="/admin/posts/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition"> + Nowy Wpis</a>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-xl border border-gray-200 overflow-hidden">
        <table class="min-w-full leading-normal">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Tytuł</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Kategoria</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Data</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($posts as $p): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <?php if($p['thumbnail']): ?>
                                <img src="<?= htmlspecialchars($p['thumbnail']) ?>" class="w-12 h-12 object-cover rounded border">
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center text-xl">📄</div>
                            <?php endif; ?>
                            <span class="font-bold text-gray-800"><?= htmlspecialchars($p['title']) ?></span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($p['category_name'] ?? 'Brak') ?></td>
                    <td class="px-6 py-4 text-sm">
                        <?= $p['status'] == 'published' ? '<span class="text-green-600 bg-green-50 px-2 py-1 rounded font-bold text-xs">Opublikowany</span>' : '<span class="text-gray-500 bg-gray-100 px-2 py-1 rounded font-bold text-xs">Szkic</span>' ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500"><?= date('Y-m-d', strtotime($p['created_at'])) ?></td>
                    <td class="px-6 py-4 text-sm text-right">
                        <a href="/post?slug=<?= $p['slug'] ?>" target="_blank" class="text-gray-500 hover:text-black mr-3">Podgląd</a>
                        <a href="/admin/posts/edit?id=<?= $p['id'] ?>" class="text-blue-600 font-bold mr-3">Edytuj</a>
                        <a href="/admin/posts/delete?id=<?= $p['id'] ?>" class="text-red-500" onclick="return confirm('Usunąć?');">Usuń</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>