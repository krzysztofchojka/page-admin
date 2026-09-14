<?php
namespace CMS\Blocks;

class SystemLoginBlock implements BlockInterface
{
    public function render(array $block, $db): string
    {
        $flash = \CMS\Core\Session::getFlash();
        $oldLogin = \CMS\Core\Session::get('old_login');
        \CMS\Core\Session::remove('old_login');

        $setRows = $db->query("SELECT setting_key, setting_value FROM pa_settings")->fetchAll();
        $s = [];
        foreach($setRows as $r) $s[$r['setting_key']] = $r['setting_value'];

        // SPRAWDZENIE CZY WYMAGANA JEST CAPTCHA (Ochrona Anty-Bruteforce)
        $ip = $_SERVER['REMOTE_ADDR'];
        $requireCaptcha = false;
        try {
            $recentFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetch()['c'];
            if ($recentFails >= 3) {
                $requireCaptcha = true;
            }
        } catch (\Exception $e) {
            // Tabela może jeszcze nie istnieć przy pierwszym uruchomieniu
        }

        $html = '<div class="max-w-md w-full mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-100">';
        
        if ($flash) {
            $html .= '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
        }

        $html .= '<form action="/login" method="POST">';
        $html .= '<input type="hidden" name="csrf_token" value="'.\CMS\Core\Session::generateCsrfToken().'">';
        
        $html .= '<div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Email lub Login</label><input class="border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" name="login" type="text" required value="'.htmlspecialchars($oldLogin ?? '').'"></div>';
        
        $html .= '<div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Hasło</label><input class="border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500" name="password" type="password" required></div>';
        
        // WSTRZYKNIĘCIE CAPTCHY
        if ($requireCaptcha && class_exists('\Gregwar\Captcha\CaptchaBuilder')) {
            $builder = new \Gregwar\Captcha\CaptchaBuilder;
            $builder->build();
            \CMS\Core\Session::set('login_captcha', $builder->getPhrase());
            
            $html .= '<div class="mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200 shadow-sm">';
            $html .= '<label class="block text-gray-800 text-sm font-bold mb-3">Wykryto nietypowy ruch. Przepisz kod z obrazka:</label>';
            $html .= '<div class="flex gap-4 items-center">';
            $html .= '<img src="'.$builder->inline().'" class="rounded-lg border border-gray-300 h-[50px] shadow-sm">';
            $html .= '<input type="text" name="captcha_answer" required class="flex-1 border rounded-lg py-2.5 px-3 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold uppercase" placeholder="Kod...">';
            $html .= '</div></div>';
        }

        $html .= '<button class="bg-blue-600 hover:bg-blue-800 text-white font-bold py-3 px-4 rounded w-full transition shadow" type="submit">Zaloguj się</button>';
        $html .= '<div class="text-right mb-2 mt-2"> <a href="/forgot-password" class="text-sm font-bold text-blue-600 hover:underline">Zapomniałeś hasła?</a> </div>';
        $html .= '</form>';

        $regMode = $s['reg_mode'] ?? 'disabled';
        $isSecretUnlocked = \CMS\Core\Session::get('secret_reg_unlocked') === true;

        if ($regMode === 'open' || ($regMode === 'secret' && $isSecretUnlocked)) {
            $html .= '<p class="text-center mt-5 text-sm text-gray-600 border-t pt-4">Nie masz konta? <br><a href="/register" class="text-blue-600 font-bold hover:underline">Zarejestruj się</a></p>';
        }

        $html .= '</div>';
        return $html;
    }
}