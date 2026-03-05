<?php
namespace CMS\Blocks;

class CountdownBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $targetDate = $data['date'] ?? '';
        $title = htmlspecialchars($data['title'] ?? '');
        $cdId = 'cd_' . uniqid();
        
        if (!$targetDate) return '';

        $html = '<div class="mb-8 bg-gray-900 text-white p-8 rounded-2xl shadow-xl text-center">';
        $html .= '<h3 class="text-xl md:text-2xl font-bold mb-6 text-gray-300">'.$title.'</h3>';
        $html .= '<div id="'.$cdId.'" class="flex justify-center gap-4 md:gap-8 text-3xl md:text-5xl font-extrabold text-orange-500">';
        $html .= '<div><span class="days block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Dni</span></div><div class="text-gray-600">:</div>';
        $html .= '<div><span class="hours block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Godzin</span></div><div class="text-gray-600">:</div>';
        $html .= '<div><span class="minutes block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Minut</span></div><div class="text-gray-600">:</div>';
        $html .= '<div><span class="seconds block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Sekund</span></div>';
        $html .= '</div></div>';
        
        $html .= "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var target = new Date('{$targetDate}').getTime();
            var el = document.getElementById('{$cdId}');
            var interval = setInterval(function() {
                var now = new Date().getTime();
                var distance = target - now;
                if (distance < 0) { clearInterval(interval); return; }
                el.querySelector('.days').innerText = Math.floor(distance / (1000 * 60 * 60 * 24)).toString().padStart(2, '0');
                el.querySelector('.hours').innerText = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)).toString().padStart(2, '0');
                el.querySelector('.minutes').innerText = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)).toString().padStart(2, '0');
                el.querySelector('.seconds').innerText = Math.floor((distance % (1000 * 60)) / 1000).toString().padStart(2, '0');
            }, 1000);
        });
        </script>";
        return $html;
    }
}