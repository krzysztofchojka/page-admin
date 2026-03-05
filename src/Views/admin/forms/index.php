<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Forms</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Forms</h1>
            <div>
                <a href="/admin" class="text-gray-600 hover:text-gray-900 mr-4">Back to Dashboard</a>
                <a href="/admin/forms/create" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    + Create New Form
                </a>
            </div>
        </div>

        <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
            <div class="bg-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-100 text-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-800 px-4 py-3 rounded mb-4 font-bold shadow-sm">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded my-6 overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created At</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($forms)): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center">No forms created yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                        <tr>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= $form['id'] ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-bold"><?= htmlspecialchars($form['title']) ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= $form['created_at'] ?></td>
                            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                <a href="/admin/forms/builder?id=<?= $form['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edytor Formularza</a>
                                <a href="/admin/forms/submissions?id=<?= $form['id'] ?>" class="text-green-600 hover:text-green-900 font-bold mr-3">Odpowiedzi</a>
                                <a href="/admin/forms/delete?id=<?= $form['id'] ?>" class="text-red-500 hover:text-red-700 font-bold" onclick="return confirm('Czy na pewno usunąć ten formularz?');">Usuń</a>
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