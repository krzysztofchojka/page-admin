<div class="max-w-6xl mx-auto">
<div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Listy Mailingowe</h1>
        <form action="/admin/email/lists/create" method="POST" class="flex gap-2">
            <input type="text" name="name" placeholder="Nazwa nowej listy..." required class="border p-2 rounded text-sm outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">+ Dodaj</button>
        </form>
    </div>
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">ID</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Nazwa Listy</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Typ</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($lists as $l): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-500"><?= $l['id'] ?></td>
                        <td class="px-6 py-4 font-bold text-gray-800"><?= htmlspecialchars($l['name']) ?></td>
                        <td class="px-6 py-4 text-gray-600">
                            <?= $l['is_default'] ? '<span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold uppercase">Systemowa</span>' : 'Niestandardowa' ?>
                        </td>
                        <!--td class="px-6 py-4"><a href="#" class="text-blue-600 font-bold hover:underline">Zarządzaj userami</a></td-->
                        <td class="px-6 py-4">
                            <?php if($l['is_default']): ?>
                                <span class="text-gray-400 text-xs italic">Użytkownicy z bazy</span>
                            <?php else: ?>
                                <a href="/admin/email/lists/manage?id=<?= $l['id'] ?>" class="text-blue-600 font-bold hover:underline bg-blue-50 px-3 py-1.5 rounded transition">Zarządzaj (Dodaj/Usuń)</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>