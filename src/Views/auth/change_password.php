<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-yellow-50 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-lg shadow-lg max-w-sm w-full border-t-4 border-yellow-500">
        <h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Security Check</h2>
        <p class="text-sm text-gray-600 mb-6 text-center">You must change your password to continue.</p>

        <?php 
        $flash = \CMS\Core\Session::getFlash();
        if ($flash): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <form action="/change-password" method="POST">
        <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">New Password</label>
                <div class="relative">
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pr-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="change_pass1" name="pass1" type="password" required>
                    <button type="button" onclick="toggleVisibility('change_pass1')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                <div class="relative">
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pr-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="change_pass2" name="pass2" type="password" required>
                    <button type="button" onclick="toggleVisibility('change_pass2')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            
            <button class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded w-full" type="submit">
                Update Password
            </button>
        </form>
    </div>
    <script>
    function toggleVisibility(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>