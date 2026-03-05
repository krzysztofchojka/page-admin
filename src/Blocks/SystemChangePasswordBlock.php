<?php
namespace CMS\Blocks;

class SystemChangePasswordBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $flash = \CMS\Core\Session::getFlash();
        $html = '<div class="max-w-sm w-full mx-auto bg-white p-8 rounded-lg shadow-lg border-t-4 border-yellow-500">';
        $html .= '<h2 class="text-2xl font-bold mb-2 text-center text-gray-800">Zmiana Hasła</h2>';
        $html .= '<p class="text-sm text-gray-600 mb-6 text-center">Wymagana jest zmiana hasła w celach bezpieczeństwa.</p>';
        if ($flash) {
            $html .= '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 text-sm font-bold">' . htmlspecialchars($flash['msg']) . '</div>';
        }
        $html .= '<form action="/change-password" method="POST">';
        $html .= '<input type="hidden" name="csrf_token" value="'.\CMS\Core\Session::generateCsrfToken().'">';
        $html .= '<div class="mb-4"><label class="block text-gray-700 text-sm font-bold mb-2">Nowe Hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-blue-500" name="pass1" type="password" required></div>';
        $html .= '<div class="mb-6"><label class="block text-gray-700 text-sm font-bold mb-2">Potwierdź Hasło</label><input class="border rounded w-full py-2 px-3 focus:ring-blue-500" name="pass2" type="password" required></div>';
        $html .= '<button class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded w-full shadow transition" type="submit">Zaktualizuj Hasło</button>';
        $html .= '</form></div>';
        return $html;
    }
}