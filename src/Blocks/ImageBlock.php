<?php
namespace CMS\Blocks;

class ImageBlock implements BlockInterface {
    public function render(array $block, $db): string {
        if (empty($block['content'])) return '';
        return '<div class="mb-6"><img src="' . htmlspecialchars($block['content']) . '" class="w-full rounded-xl shadow-lg"></div>';
    }
}