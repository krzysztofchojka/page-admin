<?php
namespace CMS\Blocks;

class VideoBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $url = $block['content'] ?? '';
        if (!$url) return '';
        
        $html = '<div class="w-full aspect-video mb-8 overflow-hidden rounded-xl shadow-lg bg-black">';
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            $embedUrl = preg_replace('/watch\?v=([a-zA-Z0-9_-]+)/', 'embed/$1', $url);
            $embedUrl = str_replace('youtu.be/', 'youtube.com/embed/', $embedUrl);
            $html .= '<iframe src="'.htmlspecialchars($embedUrl).'" class="w-full h-full border-0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
        } elseif (strpos($url, 'vimeo.com') !== false) {
            $vimeoId = substr(parse_url($url, PHP_URL_PATH), 1);
            $html .= '<iframe src="https://player.vimeo.com/video/'.htmlspecialchars($vimeoId).'" class="w-full h-full border-0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
        } else {
            $html .= '<video controls class="w-full h-full outline-none"><source src="'.htmlspecialchars($url).'" type="video/mp4">Twoja przeglądarka nie obsługuje tagu video.</video>';
        }
        $html .= '</div>';
        return $html;
    }
}