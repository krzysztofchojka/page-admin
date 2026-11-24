<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockRenderer;
use CMS\Helpers\BlockStyle;

/**
 * Przełącznik wariantów (np. "Wariant A / Wariant B") – przyciski przełączające
 * treść. Wybór jest zapamiętywany w przeglądarce; przełączniki z tym samym
 * "kluczem" synchronizują się (wybór w jednej sekcji = ten sam wybór w pozostałych).
 */
class SwitcherBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : [];
        $tabs = array_values(array_filter($d['tabs'] ?? [], 'is_array'));
        if (!$tabs) return '';

        $color = BlockStyle::color($d['color'] ?? '') ?: '#0064fa';
        $active = BlockStyle::color($d['activeColor'] ?? '') ?: '#1e2e65';
        $text = BlockStyle::color($d['textColor'] ?? '') ?: '#ffffff';
        $align = in_array($d['align'] ?? 'center', ['left', 'center', 'right'], true) ? $d['align'] : 'center';
        $justify = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$align];
        $remember = !isset($d['remember']) || !empty($d['remember']);
        $key = preg_replace('/[^a-z0-9_\-]/i', '', (string)($d['key'] ?? ''));

        $id = 'sw_' . (!empty($block['settings']['id']) ? preg_replace('/[^a-z0-9_\-]/i', '', $block['settings']['id']) : substr(md5(json_encode(array_column($tabs, 'label'))), 0, 8));
        $storeKey = 'pa_switch_' . ($key !== '' ? $key : $id);

        $html = '<div class="pa-switcher" id="' . $id . '" data-store="' . htmlspecialchars($storeKey) . '" data-remember="' . ($remember ? '1' : '0') . '" style="--sw-c:' . $color . ';--sw-a:' . $active . ';--sw-t:' . $text . '">';
        $html .= '<div class="pa-switcher-btns" role="tablist" style="justify-content:' . $justify . '">';
        foreach ($tabs as $i => $t) {
            $html .= '<button type="button" role="tab" class="pa-switcher-btn" data-i="' . $i . '" aria-selected="' . ($i === 0 ? 'true' : 'false') . '" aria-controls="' . $id . '_p' . $i . '">' . htmlspecialchars($t['label'] ?? ('Wariant ' . ($i + 1))) . '</button>';
        }
        $html .= '</div>';
        foreach ($tabs as $i => $t) {
            $html .= '<div class="pa-switcher-panel" role="tabpanel" id="' . $id . '_p' . $i . '"' . ($i === 0 ? '' : ' hidden') . '>';
            $html .= BlockRenderer::render(is_array($t['children'] ?? null) ? $t['children'] : [], $db, false);
            $html .= '</div>';
        }
        $html .= '</div>';

        $html .= "<script>(function(){var r=document.getElementById('{$id}');if(!r)return;
var btns=r.querySelectorAll(':scope>.pa-switcher-btns>.pa-switcher-btn'),panels=r.querySelectorAll(':scope>.pa-switcher-panel'),key=r.dataset.store,rem=r.dataset.remember==='1';
function show(i,save){if(i<0||i>=btns.length)i=0;btns.forEach(function(b,j){b.setAttribute('aria-selected',j===i?'true':'false');});panels.forEach(function(p,j){p.hidden=j!==i;});
if(save&&rem){try{localStorage.setItem(key,String(i));}catch(e){}document.querySelectorAll('.pa-switcher[data-store=\"'+key+'\"]').forEach(function(o){if(o!==r&&o.__paShow)o.__paShow(i,false);});}}
r.__paShow=show;btns.forEach(function(b){b.addEventListener('click',function(e){e.stopPropagation();show(parseInt(b.dataset.i,10),true);});});
if(rem){try{var s=localStorage.getItem(key);if(s!==null)show(parseInt(s,10),false);}catch(e){}}})();</script>";

        return $html;
    }
}
