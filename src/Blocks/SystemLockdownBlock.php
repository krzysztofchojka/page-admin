<?php
namespace CMS\Blocks;

class SystemLockdownBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $html = '<div class="max-w-md w-full mx-auto bg-gray-900 p-8 rounded-xl shadow-2xl border border-gray-700 text-center">';
        $html .= '<h1 class="text-3xl font-bold mb-4 text-white">Strona Zabezpieczona</h1>';
        $html .= '<p class="mb-6 text-gray-400">Podaj kod dostępu, aby kontynuować.</p>';
        $html .= '<form method="POST">';
        $html .= '<input type="password" name="site_pass" class="w-full p-3 rounded mb-4 text-black focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kod dostępu..." required>';
        $html .= '<button class="bg-blue-600 hover:bg-blue-700 text-white w-full p-3 rounded font-bold shadow transition">Wejdź na stronę</button>';
        $html .= '</form></div>';
        return $html;
    }
}