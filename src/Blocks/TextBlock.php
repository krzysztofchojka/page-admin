<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockStyle;

class TextBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $content = (string)($block['content'] ?? '');
        return BlockStyle::fontLinksFor($content) . '<div class="prose pa-prose max-w-none mb-0">' . $content . '</div>';
    }
}
