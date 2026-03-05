<?php
namespace CMS\Blocks;

class ButtonBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $label = $data['label'] ?? 'Kliknij tutaj';
        $url = $data['url'] ?? '#';
        $style = $data['style'] ?? 'primary';
        
        $btnClass = 'inline-block px-8 py-3 rounded-lg font-bold transition shadow hover:shadow-lg text-center ';
        $btnClass .= ($style === 'primary') ? 'bg-primary text-white hover:opacity-90' : 'border-2 border-primary text-primary hover:bg-primary hover:text-white';
        
        return '<div class="mb-8"><a href="'.htmlspecialchars($url).'" class="'.$btnClass.'">'.htmlspecialchars($label).'</a></div>';
    }
}