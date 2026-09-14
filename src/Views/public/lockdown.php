<!-- FILE: ./src/Views/public/lockdown.php -->
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strona Zabezpieczona</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl max-w-sm w-full border-t-4 border-blue-600">
        <div class="text-center mb-8">
            <div class="text-5xl mb-3">🚧</div>
            <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Strona Zabezpieczona</h1>
            <p class="text-sm text-gray-500 mt-2 leading-relaxed">Podaj hasło globalne, aby uzyskać dostęp do zawartości.</p>
        </div>
        
        <?php 
        $flash = \CMS\Core\Session::getFlash();
        if ($flash): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-5 py-3 rounded-lg mb-6 text-sm font-bold shadow-sm">
                <?= htmlspecialchars($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-6">
                <input type="password" name="site_pass" class="w-full border border-gray-300 p-3.5 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-gray-50 focus:bg-white text-gray-800" placeholder="Hasło dostępu..." required>
            </div>
            
            <?php 
            $ip = $_SERVER['REMOTE_ADDR'];
            $requireCaptcha = false;
            try {
                $db = \CMS\Core\Database::getInstance();
                $recentFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND username = '__lockdown__' AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetch()['c'];
                if ($recentFails >= 3) $requireCaptcha = true;
            } catch (\Exception $e) {}

            if ($requireCaptcha && class_exists('\Gregwar\Captcha\CaptchaBuilder')): 
                $builder = new \Gregwar\Captcha\CaptchaBuilder;
                $builder->build();
                \CMS\Core\Session::set('lockdown_captcha', $builder->getPhrase());
            ?>
                <div class="mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm">
                    <label class="block text-gray-700 text-sm font-bold mb-3">Weryfikacja Anty-Spam</label>
                    <div class="flex gap-4 items-center">
                        <img src="<?= $builder->inline() ?>" class="rounded-lg h-[50px] border border-gray-300 shadow-sm bg-white">
                        <input type="text" name="captcha_answer" required class="flex-1 w-full border border-gray-300 p-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition bg-white text-gray-800" placeholder="Kod...">
                    </div>
                </div>
            <?php endif; ?>

            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition transform hover:-translate-y-0.5 text-lg">
                Odblokuj dostęp
            </button>
        </form>
    </div>
</body>
</html>