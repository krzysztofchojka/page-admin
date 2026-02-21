<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">

    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
            <a href="/admin" class="text-gray-600 hover:text-black">← Back to Dashboard</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
            <div class="md:col-span-1">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h2 class="text-xl font-bold mb-4">Add New Admin</h2>
                    <form action="/admin/users/create" method="POST">
                        <div class="mb-4">
                            <label class="block text-sm font-bold mb-2 text-gray-700">Username</label>
                            <input type="text" name="username" required class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:border-blue-500" placeholder="jdoe">
                        </div>
                        <div class="mb-6">
                            <label class="block text-sm font-bold mb-2 text-gray-700">Password</label>
                            <input type="password" name="password" required class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:border-blue-500" placeholder="********">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                            Create User
                        </button>
                    </form>
                </div>
            </div>

            <div class="md:col-span-2">
                <div class="bg-white shadow rounded-lg overflow-hidden">
                    <table class="min-w-full leading-normal">
                        <thead>
                            <tr>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ID</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Username</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                                <th class="px-5 py-3 border-b-2 border-gray-200 bg-gray-50 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?= $user['id'] ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm font-bold text-gray-800">
                                    <?= htmlspecialchars($user['uname']) ?>
                                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                                        <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">You</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-gray-500">
                                    <?= $user['reg_date'] ?>
                                </td>
                                <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm">
                                    <?php if($user['id'] != $_SESSION['user_id']): // Prevent deleting self ?>
                                        <a href="/admin/users/delete?id=<?= $user['id'] ?>" 
                                           class="text-red-600 hover:text-red-900 font-bold text-xs uppercase"
                                           onclick="return confirm('Are you sure you want to delete this user?');">
                                            Delete
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs uppercase cursor-not-allowed">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if (empty($users)): ?>
                        <div class="p-6 text-center text-gray-500">No users found.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

</body>
</html>