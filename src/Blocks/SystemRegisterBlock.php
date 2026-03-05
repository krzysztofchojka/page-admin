<?php
namespace CMS\Blocks;

class SystemRegisterBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $flash = \CMS\Core\Session::getFlash();
        $oldEmail = \CMS\Core\Session::get('old_email');
        \CMS\Core\Session::remove('old_email');

        $html = '<div class="max-w-md w-full mx-auto bg-white p-8 rounded-xl shadow-lg border border-gray-100">';
        if ($flash) {
            $html .= '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
        }
        $html .= '<form action="/register" method="POST">';
        $html .= '<input type="hidden" name="csrf_token" value="'.\CMS\Core\Session::generateCsrfToken().'">';
        $html .= '<div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Email</label><input class="border rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500" name="email" type="email" required value="'.htmlspecialchars($oldEmail ?? '').'"></div>';
        $html .= '<div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500" name="password" type="password" required></div>';
        $html .= '<div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Potwierdź hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-2 focus:ring-blue-500" name="confirm_password" type="password" required></div>';
        $html .= '<button class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded w-full transition shadow" type="submit">Utwórz konto</button>';
        $html .= '</form>';
        $html .= '<p class="text-center mt-4 text-sm text-gray-500">Masz już konto? <a href="/login" class="text-blue-600 font-bold hover:underline">Zaloguj</a></p>';
        $html .= '</div>';
        return $html;
    }
}