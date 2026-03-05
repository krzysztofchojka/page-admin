<?php
namespace CMS\Blocks;

class BannerBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $bg = $data['bg'] ?? '';
        $title = $data['title'] ?? '';
        $subtitle = $data['subtitle'] ?? '';
        
        $html = '<div class="relative w-screen h-64 md:h-[500px] bg-cover bg-center mb-10" style="margin-left: calc(-50vw + 50%); background-image: url(\''.htmlspecialchars($bg).'\');">';
        if (!empty($title)) {
            $html .= '<div class="absolute bottom-8 left-0 w-11/12 md:w-2/3 bg-white/90 p-6 md:pl-16 backdrop-blur-sm shadow-xl rounded-r-2xl">';
            $html .= '<h1 class="text-3xl md:text-5xl font-extrabold text-orange-500 tracking-wide drop-shadow-sm">'.htmlspecialchars($title).'</h1>';
            $html .= '</div>';
        }
        $html .= '</div>';
        
        if (!empty($subtitle)) {
            $html .= '<div class="text-gray-800 font-medium mb-10 text-lg md:text-xl max-w-4xl border-l-4 border-orange-500 pl-5 leading-relaxed">'.nl2br(htmlspecialchars($subtitle)).'</div>';
        }
        return $html;
    }
}