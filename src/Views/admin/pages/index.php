<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Pages</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Pages</h1>
            <div>
                <a href="/admin" class="text-gray-600 hover:text-gray-900 mr-4">Back to Dashboard</a>
                <a href="/admin/pages/create" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                    + Create New Page
                </a>
            </div>
        </div>

        <div class="bg-white shadow-md rounded my-6 overflow-hidden">
            <table class="min-w-full leading-normal">
                <thead>
                    <tr>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Last Edit</th>
                        <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= $page['id'] ?></td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-bold"><?= htmlspecialchars($page['title']) ?></td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm"><?= $page['edit_date'] ?></td>
                        <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                            <a href="/admin/pages/edit?id=<?= $page['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                            <a href="/admin/pages/delete?id=<?= $page['id'] ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>