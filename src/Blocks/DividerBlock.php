<?php
namespace CMS\Blocks;

class DividerBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $height = $data['height'] ?? '8';
        return '<hr class="border-t border-gray-200 my-'.htmlspecialchars($height).' w-full">';
    }
}