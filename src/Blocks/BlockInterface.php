<?php
namespace CMS\Blocks;

interface BlockInterface {
    public function render(array $block, $db): string;
}