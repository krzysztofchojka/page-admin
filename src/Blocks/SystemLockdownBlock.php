<?php
namespace CMS\Blocks;

class SystemLockdownBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $flash = \CMS\Core\Session::getFlash();
        $ip = $_SERVER['REMOTE_ADDR'];
        $requireCaptcha = false;
        
        try {
            $recentFails = $db->query("SELECT COUNT(*) as c FROM pa_login_attempts WHERE ip_address = ? AND username = '__lockdown__' AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)", [$ip])->fetch()['c'];
            if ($recentFails >= 3) $requireCaptcha = true;
        } catch (\Exception $e) {}

        $html = '<div class="max-w-md w-full mx-auto bg-gray-900 p-8 rounded-xl shadow-2xl border border-gray-700 text-center">';
        $html .= '<h1 class="text-3xl font-bold mb-4 text-white">Strona Zabezpieczona</h1>';
        $html .= '<p class="mb-6 text-gray-400">Podaj kod dostępu, aby kontynuować.</p>';
        
        if ($flash) {
            $html .= '<div class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded mb-6 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
        }

        $html .= '<form method="POST">';
        $html .= '<input type="password" name="site_pass" class="w-full p-3 rounded mb-4 text-black focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kod dostępu..." required>';
        
        if ($requireCaptcha && class_exists('\Gregwar\Captcha\CaptchaBuilder')) {
            $builder = new \Gregwar\Captcha\CaptchaBuilder;
            $builder->build();
            \CMS\Core\Session::set('lockdown_captcha', $builder->getPhrase());
            
            $html .= '<div class="mb-6 bg-gray-800 p-4 rounded-lg border border-gray-600 text-left">';
            $html .= '<label class="block text-gray-300 text-sm font-bold mb-3">Weryfikacja Anty-Spam:</label>';
            $html .= '<div class="flex gap-4 items-center">';
            $html .= '<img src="'.$builder->inline().'" class="rounded border border-gray-500 h-[50px] shadow-sm">';
            $html .= '<input type="text" name="captcha_answer" required class="flex-1 w-full p-3 rounded text-black focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kod...">';
            $html .= '</div></div>';
        }

        $html .= '<button class="bg-blue-600 hover:bg-blue-700 text-white w-full p-3 rounded font-bold shadow transition">Wejdź na stronę</button>';
        $html .= '</form></div>';
        
        return $html;
    }
}