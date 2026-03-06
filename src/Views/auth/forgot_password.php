<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Przypomnij hasło</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-sm w-full">
        <h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Reset hasła</h2>
        <p class="text-sm text-gray-500 text-center mb-6">Podaj adres e-mail przypisany do konta, a wyślemy Ci link do zmiany hasła.</p>
        
        <?php $flash = \CMS\Core\Session::getFlash(); if ($flash): ?>
            <div class="bg-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-100 border border-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-400 text-<?= $flash['type'] === 'error' ? 'red' : 'green' ?>-700 px-4 py-3 rounded mb-4 text-sm font-bold">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <form action="/forgot-password" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \CMS\Core\Session::generateCsrfToken() ?>">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Adres E-mail</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="email" name="email" type="email" placeholder="jan@domena.pl" required>
            </div>
            <button class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded w-full transition shadow" type="submit">
                Wyślij link
            </button>
        </form>
        <p class="text-center mt-5 text-sm text-gray-600 border-t pt-4">
            Pamiętasz hasło? <a href="/login" class="text-blue-600 font-bold hover:underline">Zaloguj się</a>
        </p>
    </div>
</body>
</html>