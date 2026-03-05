<?php
namespace CMS\Blocks;

class QuoteBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $text = $data['text'] ?? '';
        $author = $data['author'] ?? '';
        
        $html = '<blockquote class="border-l-4 border-yellow-500 bg-yellow-50 p-6 rounded-r-xl mb-8 shadow-sm">';
        $html .= '<p class="text-xl md:text-2xl italic font-serif text-gray-800 mb-4 leading-relaxed">"'.nl2br(htmlspecialchars($text)).'"</p>';
        if (!empty($author)) {
            $html .= '<footer class="text-gray-600 font-bold tracking-wide text-sm uppercase">— '.htmlspecialchars($author).'</footer>';
        }
        $html .= '</blockquote>';
        return $html;
    }
}