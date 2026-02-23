<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Zarządzaj Szablonami</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Szablony Stron</h1>
            <div>
                <a href="/admin" class="text-gray-600 hover:text-gray-900 mr-4">Wróć do Dashboardu</a>
                <a href="/admin/templates/create" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    + Utwórz Nowy Szablon
                </a>
            </div>
        </div>

        <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
            <div class="<?= $flash['type'] === 'error' ? 'bg-red-100 text-red-800 border-red-300' : 'bg-green-100 text-green-800 border-green-300' ?> border p-3 rounded mb-4">
                <?= $flash['msg'] ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded my-6 overflow-hidden">
            <table class="min-w-full leading-normal">
            <tbody>
                    <?php if (empty($templates)): ?>
                        <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-5 py-5 border-b border-gray-200 text-sm text-gray-500"><?= $template['id'] ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm font-bold text-gray-800"><?= htmlspecialchars($template['title']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 text-sm">
                                <a href="/admin/templates/edit?id=<?= $template['id'] ?>" class="text-blue-600 hover:text-blue-900 font-bold bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded">Edytuj Kod HTML</a>
                                <a href="/admin/templates/delete?id=<?= $template['id'] ?>" onclick="return confirm('Jesteś pewien? Usunięcie jest nieodwracalne.');" class="text-red-500 hover:text-red-700 font-bold bg-red-50 hover:bg-red-100 px-3 py-1 rounded ml-2">Usuń</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>