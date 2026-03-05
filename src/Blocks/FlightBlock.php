<?php
namespace CMS\Blocks;

class FlightBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $html = '<div class="flight-container mb-8">';
        if (is_array($block['content']) && isset($block['content']['html'])) {
            $html .= $block['content']['html'];
        } else {
            $html .= $block['content'] ?? '';
        }
        $html .= '</div>';
        return $html;
    }
}