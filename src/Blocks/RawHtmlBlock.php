<?php
namespace CMS\Blocks;

class RawHtmlBlock implements BlockInterface {
    public function render(array $block, $db): string {
        return $block['content'] ?? '';
    }
}