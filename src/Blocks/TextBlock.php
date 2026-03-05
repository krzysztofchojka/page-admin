<?php
namespace CMS\Blocks;

class TextBlock implements BlockInterface {
    public function render(array $block, $db): string {
        return '<div class="prose max-w-none mb-0">' . ($block['content'] ?? '') . '</div>';
    }
}