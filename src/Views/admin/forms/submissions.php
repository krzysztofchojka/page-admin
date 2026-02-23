<div class="max-w-7xl mx-auto" style="max-width:100rem">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Zgłoszenia: <?= htmlspecialchars($form['title']) ?></h1>
        <a href="/admin/forms" class="text-gray-500 hover:text-gray-800 font-bold">← Wróć do formularzy</a>
    </div>

    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-x-auto">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs">ID / Data</th>
                    <?php foreach ($fields as $field): ?>
                        <?php if(($field['type'] ?? '') === 'html') continue; ?>
                        <th class="px-6 py-4 font-bold text-gray-700"><?= htmlspecialchars($field['label']) ?></th>
                    <?php endforeach; ?>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs">Użytkownik</th>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs">Adres IP</th>
                    <th class="px-6 py-4 font-bold text-gray-500 uppercase text-xs text-right">Akcje</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($decryptedRows)): ?>
                    <tr>
                        <td colspan="100%" class="px-6 py-8 text-center text-gray-500">Brak zgłoszeń dla tego formularza.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($decryptedRows as $row): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                            <span class="font-bold text-gray-700">#<?= $row['id'] ?></span><br>
                            <span class="text-xs"><?= $row['date'] ?></span>
                        </td>
                        <?php foreach ($fields as $field): ?>
                            <?php if(($field['type'] ?? '') === 'html') continue; ?>
                            <td class="px-6 py-4 text-gray-700">
                                <?php 
                                $key = $field['custom_id'] ?? $field['id'] ?? md5($field['label']); 
                                if ($field['type'] === 'file') {
                                    if (isset($row['files'][$key])) {
                                        $f = $row['files'][$key];
                                        $origName = urlencode($f['original_name'] ?? 'plik');
                                        echo '<a href="/admin/forms/download?file='.$f['storage_name'].'&orig='.$origName.'" title="'.htmlspecialchars($f['original_name'] ?? '').'" class="text-blue-600 hover:text-blue-800 hover:underline font-bold flex items-center gap-1">
                                            📎 Pobierz ('.htmlspecialchars($f['original_name'] ?? '').')
                                        </a>';
                                    } else {
                                        echo '<span class="text-gray-300">-</span>';
                                    }
                                } else {
                                    $val = $row['data'][$key] ?? '-';
                                    if (is_array($val)) {
                                        echo htmlspecialchars(implode(', ', $val));
                                    } else {
                                        echo htmlspecialchars($val);
                                    }
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                        <td class="px-6 py-4 text-gray-700 text-xs font-bold"><?= htmlspecialchars($row['user_email']) ?></td>
                        <td class="px-6 py-4 text-gray-500 text-xs"><?= $row['ip'] ?></td>
                        <td class="px-6 py-4 text-right">
                            <a href="/admin/forms/submissions/delete?id=<?= $row['id'] ?>&form_id=<?= $form['id'] ?>" onclick="return confirm('Czy na pewno usunąć to zgłoszenie?');" class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded transition">
                                Usuń
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>