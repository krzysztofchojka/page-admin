<?php
namespace CMS\Blocks;
use CMS\Helpers\BlockRenderer;

class AccordionBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $title = $block['content']['title'] ?? 'Kliknij, aby rozwinąć';
        
        $html = '<details class="group mb-4 bg-white border border-gray-200 rounded-xl shadow-sm cursor-pointer overflow-hidden transition-all">';
        $html .= '<summary class="p-5 font-bold text-lg text-purple-800 bg-purple-50 list-none flex justify-between items-center focus:outline-none focus:ring-2 focus:ring-purple-300">';
        $html .= '<span>'.htmlspecialchars($title).'</span>';
        $html .= '<span class="transition-transform duration-300 group-open:rotate-180 text-xl font-normal">▼</span>';
        $html .= '</summary><div class="p-6 border-t border-purple-100 bg-white">';
        
        if (!empty($block['children'])) {
            $html .= BlockRenderer::render($block['children'], $db, false);
        }
        
        $html .= '</div></details>';
        return $html;
    }
}