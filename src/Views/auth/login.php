<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Login</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-sm w-full">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Logowanie</h2>

        <?php 
        $flash = \CMS\Core\Session::getFlash();
        $oldLogin = \CMS\Core\Session::get('old_login'); // Pobierz
        \CMS\Core\Session::remove('old_login'); // Wyczyść od razu, by nie wisiało w sesji
        
        if ($flash): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="login">
                    Email lub Login
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="login" name="login" type="text" placeholder="Wpisz login..." value="<?= htmlspecialchars($oldLogin ?? '') ?>" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Hasło
                </label>
                <div class="relative">
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 pr-10 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" name="password" type="password" placeholder="******************" required>
                    <button type="button" onclick="toggleVisibility('password')" class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-500 hover:text-blue-600">👁️</button>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full" type="submit">
                    Zaloguj się
                </button>
                <div class="text-right mb-2 mt-2">
                    <a href="/forgot-password" class="text-sm font-bold text-blue-600 hover:underline">Zapomniałeś hasła?</a>
                </div>
            </div>
        </form>

        <?php 
        $regMode = $settings['reg_mode'] ?? 'disabled';
        // Ścisłe wymuszenie wartości logicznej (boolean)
        $isSecretUnlocked = \CMS\Core\Session::get('secret_reg_unlocked') === true;

        // Pokaż link TYLKO gdy jest otwarta LUB gdy user złamał barierę
        if ($regMode === 'open' || ($regMode === 'secret' && $isSecretUnlocked)): ?>
            <p class="text-center mt-5 text-sm text-gray-600 border-t pt-4">
                Nie masz jeszcze konta? <br>
                <a href="/register" class="text-blue-600 font-bold hover:underline inline-block mt-1">Zarejestruj się tutaj</a>
            </p>
        <?php endif; ?>
    </div>
    <script>
    function toggleVisibility(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>