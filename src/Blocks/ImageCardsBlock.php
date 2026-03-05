<?php
namespace CMS\Blocks;

class ImageCardsBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $cards = $data['cards'] ?? [];
        if (empty($cards)) return '';

        $html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">';
        foreach ($cards as $card) {
            $link = !empty($card['link']) ? htmlspecialchars($card['link']) : '#';
            $cSet = $card['settings'] ?? [];
            $cId = !empty($cSet['id']) ? ' id="'.htmlspecialchars($cSet['id']).'"' : '';
            $cCls = !empty($cSet['css']) ? ' ' . htmlspecialchars($cSet['css']) : '';
            $cStyle = !empty($cSet['style']) ? ' style="'.htmlspecialchars($cSet['style']).'"' : '';

            $html .= '<a href="'.$link.'"'.$cId.' class="block bg-white rounded-2xl shadow-sm hover:shadow-xl transition-shadow duration-300 overflow-hidden border border-gray-100 group flex flex-col h-full'.$cCls.'"'.$cStyle.'>';
            $html .= '<div class="h-48 w-full overflow-hidden bg-gray-100">';
            if (!empty($card['img'])) {
                $html .= '<img src="'.htmlspecialchars($card['img']).'" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">';
            }
            $html .= '</div><div class="p-6 flex-1 flex flex-col justify-center">';
            $html .= '<h3 class="text-orange-500 font-bold text-xl mb-2 leading-tight group-hover:text-orange-600 transition">'.htmlspecialchars($card['title']).'</h3>';
            if (!empty($card['subtitle'])) {
                $html .= '<p class="text-gray-600 text-sm">'.htmlspecialchars($card['subtitle']).'</p>';
            }
            $html .= '</div></a>';
        }
        $html .= '</div>';
        return $html;
    }
}