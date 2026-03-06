<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustaw nowe hasło</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-sm w-full">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Ustaw nowe hasło</h2>
        
        <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <form action="/reset-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'] ?? '') ?>">
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">Nowe hasło</label>
                <div class="relative">
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pr-10 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="pass1" name="pass1" type="password" required>
                    <button type="button" onclick="toggleVisibility('pass1')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2">Powtórz nowe hasło</label>
                <div class="relative">
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pr-10 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="pass2" name="pass2" type="password" required>
                    <button type="button" onclick="toggleVisibility('pass2')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            
            <button class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded w-full transition shadow" type="submit">
                Zapisz i zaloguj
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