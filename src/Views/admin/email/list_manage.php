<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Lista: <?= htmlspecialchars($list['name']) ?></h1>
        <a href="/admin/email/lists" class="text-gray-500 hover:text-black font-bold">← Wróć do list</a>
    </div>

    <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
        <div class="bg-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-100 text-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-800 px-4 py-3 rounded mb-6 font-bold shadow-sm">
            <?= htmlspecialchars($flash['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200 mb-8">
        <form action="/admin/email/lists/add-subscriber" method="POST" class="flex flex-wrap md:flex-nowrap gap-4 items-end">
            <input type="hidden" name="list_id" value="<?= $list['id'] ?>">
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Imię (Opcjonalnie)</label>
                <input type="text" name="name" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Jan Kowalski">
            </div>
            <div class="flex-1 w-full">
                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Email</label>
                <input type="email" name="email" class="w-full border p-2 rounded focus:ring-2 focus:ring-blue-500 outline-none" placeholder="jan@domena.pl" required>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded transition shadow w-full md:w-auto">
                + Dodaj Odbiorcę
            </button>
        </form>
    </div>

    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Email</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs">Imię</th>
                    <th class="px-6 py-3 font-bold text-gray-500 uppercase text-xs text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($subscribers as $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-gray-800 font-bold"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($s['name']) ?></td>
                        <td class="px-6 py-4 text-right">
                            <a href="/admin/email/lists/remove-subscriber?id=<?= $s['id'] ?>&list_id=<?= $list['id'] ?>" class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition">Usuń</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($subscribers)): ?>
                    <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">Brak dodanych adresów na tej liście.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>