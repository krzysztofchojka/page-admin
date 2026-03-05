<?php
namespace CMS\Blocks;
use CMS\Helpers\BlockRenderer;

class Columns3Block implements BlockInterface {
    public function render(array $block, $db): string {
        $html = '<div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">';
        $html .= '<div>' . (!empty($block['children']['left']) ? BlockRenderer::render($block['children']['left'], $db, false) : '') . '</div>';
        $html .= '<div>' . (!empty($block['children']['center']) ? BlockRenderer::render($block['children']['center'], $db, false) : '') . '</div>';
        $html .= '<div>' . (!empty($block['children']['right']) ? BlockRenderer::render($block['children']['right'], $db, false) : '') . '</div>';
        $html .= '</div>';
        return $html;
    }
}