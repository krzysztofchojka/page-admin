<?php
namespace CMS\Blocks;

class LinkedImageBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $img = $block['content']['url'] ?? '';
        $link = $block['content']['link'] ?? '#';
        if (!$img) return '';
        return '<div class="mb-8"><a href="'.htmlspecialchars($link).'"><img src="'.htmlspecialchars($img).'" class="w-full rounded-xl shadow-md hover:opacity-90 transition transform hover:scale-[1.01]"></a></div>';
    }
}