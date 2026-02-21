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
        
        <?php if ($msg = \CMS\Core\Session::getFlash()): ?>
            <div class="bg-red-100 text-red-700 p-2 mb-4 rounded text-sm"><?= $msg['msg'] ?></div>
        <?php endif; ?>

        <form action="/register" method="POST">
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Email</label>
                <input type="email" name="email" required class="w-full border p-2 rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-bold mb-1">Password</label>
                <input type="password" name="password" required class="w-full border p-2 rounded">
            </div>
            <div class="mb-6">
                <label class="block text-sm font-bold mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" required class="w-full border p-2 rounded">
            </div>
            <button class="bg-green-600 text-white font-bold w-full py-2 rounded hover:bg-green-700">Register</button>
        </form>
        
        <p class="text-center mt-4 text-sm text-gray-500">
            Already have an account? <a href="/login" class="text-blue-600">Login</a>
        </p>
    </div>
</body>
</html>