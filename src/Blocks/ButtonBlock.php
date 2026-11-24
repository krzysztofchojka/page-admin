<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockStyle;

class ButtonBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $label = $data['label'] ?? 'Kliknij tutaj';
        $url = BlockStyle::url($data['url'] ?? '') ?: '#';
        $style = in_array($data['style'] ?? 'primary', ['primary', 'secondary', 'link', 'pill', 'tab'], true) ? $data['style'] : 'primary';
        $color = BlockStyle::color($data['color'] ?? '') ?: 'var(--pa-primary, #1e40af)';
        $text = BlockStyle::color($data['textColor'] ?? '');
        $size = in_array($data['size'] ?? 'md', ['sm', 'md', 'lg'], true) ? ($data['size'] ?? 'md') : 'md';
        $full = !empty($data['full']);
        $target = !empty($data['newTab']) ? ' target="_blank" rel="noopener"' : '';

        $css = "--btn-c:{$color};" . ($text ? "--btn-t:{$text};" : '');
        $cls = 'pa-btn pa-btn-' . $style . ' pa-btn-' . $size . ($full ? ' pa-btn-full' : '');

        // Pozycja pozioma w % szerokości kontenera (np. 58 = nad logo w banerze).
        // translateX(-X%) przesuwa przycisk o ten sam % własnej szerokości – zawsze mieści się w ekranie.
        $wrap = 'mb-0';
        $wrapStyle = '';
        if (isset($data['offsetX']) && $data['offsetX'] !== '' && is_numeric($data['offsetX'])) {
            $x = max(0, min(100, (float)$data['offsetX']));
            $css .= "position:relative;left:{$x}%;transform:translateX(-{$x}%);white-space:nowrap;";
            $wrapStyle = ' style="text-align:left"';
        }

        return '<div class="'.$wrap.'"'.$wrapStyle.'><a href="'.htmlspecialchars($url).'" class="'.$cls.'" style="'.$css.'"'.$target.'>'.htmlspecialchars($label).'</a></div>';
    }
}
