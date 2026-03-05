<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Kategorie Postów</h1>
        <a href="/admin/posts" class="text-gray-500 font-bold hover:text-black">← Wróć do wpisów</a>
    </div>

    <div class="flex flex-col md:flex-row gap-6">
        <div class="w-full md:w-1/3">
            <form action="/admin/categories/save" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h2 class="font-bold mb-4">Dodaj kategorię</h2>
                <input type="text" name="name" placeholder="Nazwa kategorii" required class="w-full border p-2 rounded mb-4 focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded">Zapisz</button>
            </form>
        </div>
        <div class="w-full md:w-2/3 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nazwa</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Slug</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                    <tr class="border-b border-gray-100">
                        <td class="px-6 py-3 font-bold text-gray-800"><?= htmlspecialchars($c['name']) ?></td>
                        <td class="px-6 py-3 text-gray-500 text-sm"><?= htmlspecialchars($c['slug']) ?></td>
                        <td class="px-6 py-3 text-right">
                            <a href="/admin/categories/delete?id=<?= $c['id'] ?>" class="text-red-500 text-sm font-bold" onclick="return confirm('Usunąć?');">Usuń</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>