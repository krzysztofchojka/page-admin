<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockStyle;

/**
 * Pasek wiadomości (NEWS) – przewijane komunikaty, następca <marquee>.
 * Zatrzymuje się po najechaniu myszą, respektuje prefers-reduced-motion.
 */
class TickerBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : [];

        $label = trim((string)($d['label'] ?? 'NEWS'));
        $items = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string)($d['items'] ?? '')))));
        $speed = max(10, min(300, (int)($d['speed'] ?? 60)));       // px / s
        $bg = BlockStyle::color($d['bg'] ?? '') ?: '#1e40af';
        $bg2 = BlockStyle::color($d['bg2'] ?? '');
        $color = BlockStyle::color($d['color'] ?? '') ?: '#ffffff';
        $shape = in_array($d['shape'] ?? 'bar', ['bar', 'tab', 'pill'], true) ? $d['shape'] : 'bar';
        $sep = htmlspecialchars(mb_substr((string)($d['separator'] ?? '•'), 0, 3));

        $background = $bg2 ? "linear-gradient(90deg,{$bg},{$bg2})" : $bg;
        $id = 'tk_' . substr(md5(json_encode($d) . $label), 0, 8);

        $html = '<div id="' . $id . '" class="pa-ticker pa-ticker-' . $shape . '" style="background:' . $background . ';color:' . $color . '" data-speed="' . $speed . '">';
        if ($label !== '') {
            $html .= '<div class="pa-ticker-label">' . htmlspecialchars($label) . '</div>';
        }
        $html .= '<div class="pa-ticker-viewport" aria-live="off">';
        if ($items) {
            $line = '';
            foreach ($items as $i => $it) {
                // dozwolone proste formatowanie i linki
                $line .= '<span class="pa-ticker-item">' . strip_tags($it, '<b><strong><i><em><a><span>') . '</span>';
                $line .= '<span class="pa-ticker-sep" aria-hidden="true">' . $sep . '</span>';
            }
            // Treść zdublowana -> płynna pętla (animacja przesuwa o -50%)
            $html .= '<div class="pa-ticker-track"><div class="pa-ticker-group">' . $line . '</div><div class="pa-ticker-group" aria-hidden="true">' . $line . '</div></div>';
        }
        $html .= '</div></div>';

        if ($items) {
            $html .= "<script>(function(){var r=document.getElementById('{$id}');if(!r)return;var t=r.querySelector('.pa-ticker-track');var g=r.querySelector('.pa-ticker-group');
function set(){var w=g.scrollWidth||600;t.style.animationDuration=(w/{$speed})+'s';}
if(document.readyState==='complete')set();else window.addEventListener('load',set);window.addEventListener('resize',set);})();</script>";
        }
        return $html;
    }
}
