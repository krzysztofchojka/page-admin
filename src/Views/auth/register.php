<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-sm w-full">
        <h2 class="text-2xl font-bold mb-6 text-center">Create Account</h2>
        
        <?php 
        $msg = \CMS\Core\Session::getFlash();
        $oldEmail = \CMS\Core\Session::get('old_email');
        \CMS\Core\Session::remove('old_email');
        
        if ($msg): ?>
            <div class="bg-red-100 text-red-700 p-2 mb-4 rounded text-sm"><?= $msg['msg'] ?></div>
        <?php endif; ?>

        <form action="/register" method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($oldEmail ?? '') ?>" required class="w-full border p-2 rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Password</label>
                <div class="relative">
                    <input type="password" id="reg_pass" name="password" required class="w-full border p-2 pr-10 rounded">
                    <button type="button" onclick="toggleVisibility('reg_pass')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-sm font-bold mb-1">Confirm Password</label>
                <div class="relative">
                    <input type="password" id="reg_pass2" name="confirm_password" required class="w-full border p-2 pr-10 rounded">
                    <button type="button" onclick="toggleVisibility('reg_pass2')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            <button class="bg-green-600 text-white font-bold w-full py-2 rounded hover:bg-green-700">Register</button>
        </form>
        
        <p class="text-center mt-4 text-sm text-gray-500">
            Already have an account? <a href="/login" class="text-blue-600">Login</a>
        </p>
    </div>
    <script>
    function toggleVisibility(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>