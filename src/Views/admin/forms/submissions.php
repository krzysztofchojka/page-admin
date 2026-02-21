<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submissions: <?= htmlspecialchars($form['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold">Entries: <?= htmlspecialchars($form['title']) ?></h1>
            <a href="/admin/forms" class="text-blue-600 hover:underline">← Back to Forms</a>
        </div>

        <div class="bg-white shadow rounded overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left font-bold text-gray-500">ID / Date</th>
                        
                        <?php foreach ($fields as $field): ?>
                            <th class="px-4 py-3 text-left font-bold text-gray-700"><?= htmlspecialchars($field['label']) ?></th>
                        <?php endforeach; ?>

                        <th class="px-4 py-3 text-left font-bold text-gray-500">User</th>
                        
                        <th class="px-4 py-3 text-left font-bold text-gray-500">IP Address</th>

                        <th class="px-4 py-3 text-left font-bold text-gray-500">Akcje</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($decryptedRows as $row): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">
                            #<?= $row['id'] ?><br>
                            <span class="text-xs"><?= $row['date'] ?></span>
                        </td>

                        <?php foreach ($fields as $field): ?>
                            <td class="px-4 py-3">
                                <?php 
                                    // Generate Key used in saving (md5 of label)
                                    $key = $field['id'] ?? md5($field['label']); 
                                    if ($field['type'] === 'file') {
                                        if (isset($row['files'][$key])) {
                                            $f = $row['files'][$key];
                                            // Przekazujemy oryginalną nazwę bezpiecznie przez URL
                                            $origName = urlencode($f['original_name'] ?? 'plik');
                                            echo '<a href="/admin/forms/download?file='.$f['storage_name'].'&orig='.$origName.'" title="'.htmlspecialchars($f['original_name'] ?? '').'" class="text-blue-600 hover:underline flex items-center gap-1">
                                                📎 Pobierz ('.htmlspecialchars($f['original_name'] ?? '').')
                                            </a>';
                                        } else {
                                            echo '<span class="text-gray-300">-</span>';
                                        }
                                    } else {
                                        echo htmlspecialchars($row['data'][$key] ?? '-');
                                    }
                                ?>
                            </td>
                        <?php endforeach; ?>

                        <td class="px-4 py-3 text-gray-700 text-xs font-bold"><?= htmlspecialchars($row['user_email']) ?></td>

                        <td class="px-4 py-3 text-gray-500 text-xs"><?= $row['ip'] ?></td>

                        <td class="px-4 py-3 text-right">
    <a href="/admin/forms/submissions/delete?id=<?= $row['id'] ?>&form_id=<?= $form['id'] ?>" 
       onclick="return confirm('Czy na pewno usunąć to zgłoszenie? Użytkownik będzie mógł wypełnić formularz ponownie.');" 
       class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition">
       Usuń
    </a>
</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>