<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockRenderer;
use CMS\Helpers\BlockStyle;

/**
 * Sekcja / Kontener – blok układu z własnym tłem (kolor, zdjęcie, nakładka),
 * szerokością wnętrza, wysokością i wyrównaniem. Dzieci: lista bloków.
 */
class SectionBlock implements BlockInterface {

    private const INNER = ['full' => '', 'sm' => '640px', 'md' => '768px', 'lg' => '1024px', 'xl' => '1200px', '2xl' => '1400px'];
    private const PAD_Y = ['none' => '0', 'sm' => 'clamp(0.75rem,2vw,1rem)', 'md' => 'clamp(1.5rem,4vw,2.5rem)', 'lg' => 'clamp(2.5rem,6vw,4rem)', 'xl' => 'clamp(3.5rem,9vw,6rem)'];
    private const PAD_X = ['none' => '0', 'sm' => '0.75rem', 'md' => 'clamp(1rem,3vw,1.5rem)', 'lg' => 'clamp(1.25rem,5vw,3rem)'];
    private const BG_SIZE = ['cover', 'contain', 'auto', '100% auto'];
    private const BG_POS = ['center', 'top', 'bottom', 'left', 'right', 'top left', 'top right', 'bottom left', 'bottom right'];

    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : [];

        $outer = [];
        $bgColor = BlockStyle::color($d['bgColor'] ?? '');
        if ($bgColor) $outer[] = 'background-color:' . $bgColor;

        $bgImage = BlockStyle::url($d['bgImage'] ?? '');
        if ($bgImage) {
            $size = in_array($d['bgSize'] ?? 'cover', self::BG_SIZE, true) ? ($d['bgSize'] ?? 'cover') : 'cover';
            $pos = in_array($d['bgPos'] ?? 'center', self::BG_POS, true) ? ($d['bgPos'] ?? 'center') : 'center';
            // Pozycja pozioma w % (np. 58%) – pozwala trzymać logo w kadrze na telefonie
            if (!empty($d['bgPosX']) && preg_match('/^\d{1,3}%$/', $d['bgPosX'])) $pos = $d['bgPosX'] . ' center';
            $outer[] = 'background-image:' . BlockStyle::cssUrl($bgImage);
            $outer[] = 'background-size:' . $size;
            $outer[] = 'background-position:' . $pos;
            $outer[] = 'background-repeat:no-repeat';
            if (!empty($d['bgFixed'])) $outer[] = 'background-attachment:fixed';
        }

        if ($c = BlockStyle::color($d['textColor'] ?? '')) $outer[] = 'color:' . $c;

        $minH = BlockStyle::length($d['minHeight'] ?? '');
        if ($minH) $outer[] = 'min-height:' . $minH;

        // Proporcje (np. baner 1680x268) – wysokość skaluje się z szerokością ekranu
        if (!empty($d['ratio']) && preg_match('/^\d+(\.\d+)?\s*\/\s*\d+(\.\d+)?$/', $d['ratio'])) {
            $outer[] = 'aspect-ratio:' . str_replace(' ', '', $d['ratio']);
            // bez jawnej szerokości aspect-ratio + min-height poszerza blok zamiast go wydłużyć
            if (empty($d['fullBleed'])) $outer[] = 'width:100%';
        }

        $padY = self::PAD_Y[$d['padY'] ?? 'md'] ?? self::PAD_Y['md'];
        $padX = self::PAD_X[$d['padX'] ?? 'md'] ?? self::PAD_X['md'];
        $outer[] = "padding:{$padY} {$padX}";

        $classes = ['pa-section', 'relative'];
        if (!empty($d['fullBleed'])) $classes[] = 'pa-full-bleed';
        if (!empty($d['vCenter'])) $classes[] = 'pa-section-vcenter';

        $html = '<section class="' . implode(' ', $classes) . '" style="' . htmlspecialchars(implode(';', $outer)) . '">';

        // Nakładka kolorystyczna na zdjęcie
        $overlay = BlockStyle::color($d['overlay'] ?? '');
        if ($overlay) {
            $op = max(0, min(100, (int)($d['overlayOpacity'] ?? 40))) / 100;
            $html .= '<div aria-hidden="true" style="position:absolute;inset:0;background:' . htmlspecialchars($overlay) . ';opacity:' . $op . ';pointer-events:none"></div>';
        }

        $inner = self::INNER[$d['inner'] ?? 'xl'] ?? '1200px';
        $innerStyle = 'position:relative;width:100%;' . ($inner ? "max-width:{$inner};margin-left:auto;margin-right:auto;" : '');
        $html .= '<div class="pa-section-inner" style="' . $innerStyle . '">';
        $html .= BlockRenderer::render(is_array($block['children'] ?? null) ? $block['children'] : [], $db, false);
        $html .= '</div></section>';

        return $html;
    }
}
