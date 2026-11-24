<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockStyle;

/**
 * Harmonogram. Treść wpisywana liniami:
 *   09:00 | Rejestracja uczestników
 *   Sesja popołudniowa               (linia bez "|" = śródtytuł)
 */
class ScheduleBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : ['rows' => (string)($block['content'] ?? '')];
        $title = trim((string)($d['title'] ?? ''));
        $lines = preg_split('/\r?\n/', (string)($d['rows'] ?? ''));
        $accent = BlockStyle::color($d['accent'] ?? '') ?: '#1e40af';
        $striped = !empty($d['striped']);

        $allowed = '<b><strong><i><em><u><a><br><span>';
        $html = '<div class="pa-schedule' . ($striped ? ' pa-schedule-striped' : '') . '" style="--sch-accent:' . $accent . '">';
        if ($title !== '') {
            $html .= '<h4 class="pa-schedule-title">' . htmlspecialchars($title) . '</h4>';
        }
        $html .= '<dl class="pa-schedule-list">';
        foreach ($lines as $line) {
            if (trim($line) === '') continue;
            if (strpos($line, '|') === false) {
                $html .= '<div class="pa-schedule-sub">' . strip_tags(trim($line), $allowed) . '</div>';
                continue;
            }
            [$time, $desc] = array_map('trim', explode('|', $line, 2));
            $html .= '<div class="pa-schedule-row"><dt>' . htmlspecialchars($time) . '</dt><dd>' . strip_tags($desc, $allowed) . '</dd></div>';
        }
        $html .= '</dl></div>';
        return $html;
    }
}
