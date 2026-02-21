<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Site Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-10">
    <div class="max-w-2xl mx-auto bg-white p-8 rounded shadow">
        <a href="/admin">Back</a>
        <h1 class="text-2xl font-bold mb-6">Site Identity & Settings</h1>
        
        <form action="/admin/settings/save" method="POST">
            
            <h3 class="font-bold text-gray-500 uppercase text-xs mb-4 border-b pb-2">Identity</h3>
            <div class="mb-4">
                <label class="block font-bold">Site Title</label>
                <input type="text" name="site_title" value="<?= $settings['site_title'] ?? '' ?>" class="w-full border p-2 rounded">
            </div>
            <div class="mb-4">
                <label class="block font-bold text-sm">Site Logo URL</label>
                <input type="text" name="site_logo" value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>" class="w-full border p-2 rounded text-sm" placeholder="/uploads/media/logo.png">
            </div>
            <div class="mb-4">
                <label class="block font-bold">Theme Color (Hex)</label>
                <input type="color" name="theme_color" value="<?= $settings['theme_color'] ?? '#3b82f6' ?>" class="w-full h-10 rounded cursor-pointer">
            </div>

            <h3 class="font-bold text-gray-500 uppercase text-xs mt-8 mb-4 border-b pb-2">Security (Incentive Mode)</h3>
            <div class="mb-4 bg-red-50 p-4 rounded border border-red-100">
                <label class="flex items-center gap-2 font-bold text-red-800">
                    <input type="hidden" name="lockdown_enabled" value="0">
                    <input type="checkbox" name="lockdown_enabled" value="1" <?= ($settings['lockdown_enabled']??0) == 1 ? 'checked' : '' ?>>
                    Enable Site Lockdown
                </label>
                <p class="text-xs text-red-600 mt-1">If enabled, visitors must enter the password below to see ANY content.</p>
                
                <label class="block font-bold mt-4">Global Access Password</label>
                <input type="text" name="lockdown_password" value="<?= $settings['lockdown_password'] ?? '' ?>" class="w-full border p-2 rounded">
            </div>

            <div class="mb-4 bg-orange-50 p-4 rounded border border-orange-100 mt-4">
                <label class="flex items-center gap-2 font-bold text-orange-800">
                    <input type="hidden" name="require_registration" value="0">
                    <input type="checkbox" name="require_registration" value="1" <?= ($settings['require_registration']??0) == 1 ? 'checked' : '' ?>>
                    Require User Registration
                </label>
                <p class="text-xs text-orange-600 mt-1">
                    If enabled, visitors must create an account (or login) to view content.
                    <br><strong>Logic:</strong> If Lockdown is ON, they enter password first, THEN register.
                </p>
            </div>

            <h3 class="font-bold text-gray-500 uppercase text-xs mt-8 mb-4 border-b pb-2">Globalna Stopka (Footer)</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div class="bg-gray-50 p-4 border rounded">
        <label class="flex items-center gap-2 font-bold text-gray-800 cursor-pointer">
            <input type="hidden" name="hide_footer" value="0">
            <input type="checkbox" name="hide_footer" value="1" <?= ($settings['hide_footer']??0) == 1 ? 'checked' : '' ?>>
            Ukryj stopkę całkowicie
        </label>
        <p class="text-xs text-gray-500 mt-1">Zaznacz, jeśli chcesz schować domyślną i własną stopkę.</p>
    </div>
    <div class="bg-gray-50 p-4 border rounded">
        <label class="block font-bold text-sm mb-2">Wybierz stronę jako edytor stopki</label>
        <select name="footer_page_id" class="w-full border p-2 rounded text-sm">
            <option value="">-- Domyślna prosta stopka --</option>
            <?php foreach ($pages as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($settings['footer_page_id']??'') == $p['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="text-xs text-gray-500 mt-1">Stwórz nową podstronę w Page Builderze, np. "Moja Stopka", i wskaż ją tutaj.</p>
    </div>
</div>

            <h3 class="font-bold text-gray-500 uppercase text-xs mt-8 mb-4 border-b pb-2">Session Policy</h3>
            <div class="mb-4">
                <label class="block font-bold">Session Lifetime (Days)</label>
                <input type="number" name="session_days" value="<?= $settings['session_days'] ?? '7' ?>" class="w-full border p-2 rounded">
                <p class="text-xs text-gray-500 mt-1">How long before a user (or admin) is forced to log in again.</p>
            </div>

            <h3 class="font-bold text-gray-500 uppercase text-xs mt-8 mb-4 border-b pb-2">Wygląd i SEO</h3>
<div class="grid grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block font-bold text-sm">Favicon URL (Ikonka na karcie przeglądarki)</label>
        <input type="text" name="site_favicon" value="<?= htmlspecialchars($settings['site_favicon'] ?? '') ?>" class="w-full border p-2 rounded text-sm media-input" placeholder="/uploads/media/icon.png">
    </div>
    <div>
        <label class="block font-bold text-sm">Meta Description</label>
        <input type="text" name="meta_description" value="<?= htmlspecialchars($settings['meta_description'] ?? '') ?>" class="w-full border p-2 rounded text-sm">
    </div>
</div>
<div class="grid grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block font-bold text-sm text-orange-600">Główny Kolor Akcentu (np. Hex)</label>
        <input type="text" name="color_primary" value="<?= htmlspecialchars($settings['color_primary'] ?? '#f97316') ?>" class="w-full border p-2 rounded text-sm">
        <p class="text-xs text-gray-500 mt-1">Stworzy zmienną var(--color-primary)</p>
    </div>
    <div>
        <label class="block font-bold text-sm text-blue-600">Dodatkowy Kolor Akcentu</label>
        <input type="text" name="color_secondary" value="<?= htmlspecialchars($settings['color_secondary'] ?? '#1e3a8a') ?>" class="w-full border p-2 rounded text-sm">
        <p class="text-xs text-gray-500 mt-1">Stworzy zmienną var(--color-secondary)</p>
    </div>
</div>

<h3 class="font-bold text-gray-500 uppercase text-xs mt-8 mb-4 border-b pb-2">Serwer SMTP (Wysyłka Formularzy)</h3>
<div class="grid grid-cols-2 gap-4 mb-4">
    <div>
        <label class="block font-bold text-sm">Serwer (Host)</label>
        <input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" class="w-full border p-2 rounded text-sm" placeholder="smtp.gmail.com">
    </div>
    <div>
        <label class="block font-bold text-sm">Port</label>
        <input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" class="w-full border p-2 rounded text-sm">
    </div>
    <div>
        <label class="block font-bold text-sm">Użytkownik (Login)</label>
        <input type="text" name="smtp_user" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>" class="w-full border p-2 rounded text-sm">
    </div>
    <div>
        <label class="block font-bold text-sm">Hasło</label>
        <input type="password" name="smtp_pass" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>" class="w-full border p-2 rounded text-sm">
    </div>
</div>

<h3 class="font-bold text-gray-500 uppercase text-xs mt-8 mb-4 border-b pb-2">Zaawansowane</h3>
<div class="mb-4">
    <label class="block font-bold text-sm">Custom Head (Skrypty, Pixele, Google Analytics)</label>
    <textarea name="custom_head" class="w-full border p-2 rounded text-sm font-mono h-32" placeholder="<script>...</script>"><?= htmlspecialchars($settings['custom_head'] ?? '') ?></textarea>
</div>

            <button class="bg-blue-600 text-white px-6 py-2 rounded font-bold w-full mt-4">Save Settings</button>
        </form>
    </div>
</body>
</html>