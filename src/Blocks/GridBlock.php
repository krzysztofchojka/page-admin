<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockRenderer;

/**
 * Siatka – elastyczny układ kolumn z osobną liczbą kolumn
 * dla telefonu, tabletu i desktopu. Dzieci: {col_0: [...], col_1: [...], ...}
 */
class GridBlock implements BlockInterface {

    private const GAP = ['none' => '0', 'sm' => '0.5rem', 'md' => '1rem', 'lg' => 'clamp(1rem,3vw,2rem)', 'xl' => 'clamp(1.5rem,4vw,3rem)'];
    private const VALIGN = ['stretch', 'start', 'center', 'end'];

    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : [];
        $children = is_array($block['children'] ?? null) ? $block['children'] : [];

        $count = max(1, min(12, (int)($d['count'] ?? 2)));
        $cd = max(1, min(12, (int)($d['cols'] ?? $count)));
        $ct = max(1, min(12, (int)($d['colsTablet'] ?? min($cd, 2))));
        $cm = max(1, min(12, (int)($d['colsMobile'] ?? 1)));
        $gap = self::GAP[$d['gap'] ?? 'md'] ?? self::GAP['md'];
        $valign = in_array($d['valign'] ?? 'stretch', self::VALIGN, true) ? $d['valign'] : 'stretch';

        $style = "--pa-cd:{$cd};--pa-ct:{$ct};--pa-cm:{$cm};gap:{$gap};align-items:{$valign}";
        $html = '<div class="pa-grid" style="' . $style . '">';
        for ($i = 0; $i < $count; $i++) {
            $cell = $children['col_' . $i] ?? [];
            $html .= '<div class="pa-grid-cell min-w-0">' . BlockRenderer::render(is_array($cell) ? $cell : [], $db, false) . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
