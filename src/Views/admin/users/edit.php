<div class="max-w-xl mx-auto mt-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Edycja Użytkownika #<?= $user['id'] ?></h1>
        <a href="/admin/users" class="text-gray-500 hover:text-black font-bold">← Wróć</a>
    </div>

    <form action="/admin/users/update" method="POST" class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 border-t-4 border-t-blue-600">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="mb-5">
            <label class="block text-sm font-bold text-gray-700 mb-2">Login (Nazwa Użytkownika)</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['uname'] ?? '') ?>" required class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="mb-5">
            <label class="block text-sm font-bold text-gray-700 mb-2">Adres Email (np. do powiadomień)</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none">
        </div>

        <div class="mb-5">
            <label class="block text-sm font-bold text-gray-700 mb-2">Zmień hasło (zostaw puste, aby nie zmieniać)</label>
            <input type="password" name="password" class="w-full border border-gray-300 p-2.5 rounded focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nowe hasło...">
        </div>

        <div class="mb-8 bg-orange-50 p-4 border border-orange-200 rounded-lg">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="force_change" value="1" <?= $user['pass_expired'] ? 'checked' : '' ?> class="w-5 h-5 text-orange-600 rounded">
                <div>
                    <span class="font-bold text-orange-800 block">Wymuś zmianę hasła po zalogowaniu</span>
                    <span class="text-xs text-orange-600">Przy kolejnej próbie wejścia na stronę użytkownik zobaczy ekran wymuszający ustawienie własnego, bezpiecznego hasła.</span>
                </div>
            </label>
        </div>

        <div class="mb-8">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_admin" value="1" <?= $user['admin'] ? 'checked' : '' ?> class="w-5 h-5 text-blue-600 rounded">
                <span class="font-bold text-gray-700">Nadaj uprawnienia Administratora (Dostęp do panelu CMS)</span>
            </label>
        </div>

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded shadow-md transition">
            Zapisz Zmiany
        </button>
    </form>
</div>