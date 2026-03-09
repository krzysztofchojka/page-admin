<?php
namespace CMS\Blocks;

class CountdownBlock implements BlockInterface {
    public function render(array $block, $db): string {
        $data = is_array($block['content']) ? $block['content'] : [];
        $targetDate = $data['date'] ?? '';
        $title = htmlspecialchars($data['title'] ?? '');
        $cdId = 'cd_' . md5(uniqid('', true));

        // Jeśli nie wybrano daty, blok w ogóle się nie wyrenderuje
        if (empty($targetDate)) return '';

        $html = '<div class="mb-8 bg-gray-900 text-white p-8 rounded-2xl shadow-xl text-center">';
        $html .= '<h3 class="text-xl md:text-2xl font-bold mb-6 text-gray-300">'.$title.'</h3>';
        $html .= '<div id="'.$cdId.'" class="flex justify-center gap-4 md:gap-8 text-3xl md:text-5xl font-extrabold text-orange-500">';
        $html .= '<div><span class="days block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Dni</span></div><div class="text-gray-600">:</div>';
        $html .= '<div><span class="hours block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Godzin</span></div><div class="text-gray-600">:</div>';
        $html .= '<div><span class="minutes block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Minut</span></div><div class="text-gray-600">:</div>';
        $html .= '<div><span class="seconds block">00</span><span class="text-xs uppercase text-gray-400 font-normal">Sekund</span></div>';
        $html .= '</div></div>';

        $html .= "<script>
        (function() {
            var targetDateStr = '{$targetDate}';
            
            // Zabezpieczenie dla przeglądarek Safari / iOS, które źle odczytują standardowe daty z myślnikami
            var safeDateStr = targetDateStr.replace(/-/g, '/').replace('T', ' '); 
            var target = new Date(safeDateStr).getTime();
            
            // Fallback, jeśli modyfikacja zawiodła (dla najnowszych przeglądarek)
            if (isNaN(target)) {
                target = new Date(targetDateStr).getTime();
            }

            var el = document.getElementById('{$cdId}');
            if (!el || isNaN(target)) return;

            function updateTimer() {
                var now = new Date().getTime();
                var distance = target - now;

                // Jeśli data już minęła (lub wybrano datę w przeszłości)
                if (distance < 0) {
                    el.querySelector('.days').innerText = '00';
                    el.querySelector('.hours').innerText = '00';
                    el.querySelector('.minutes').innerText = '00';
                    el.querySelector('.seconds').innerText = '00';
                    return false; // Przerywa interwał
                }

                var d = Math.floor(distance / (1000 * 60 * 60 * 24));
                var h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                var m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                var s = Math.floor((distance % (1000 * 60)) / 1000);

                el.querySelector('.days').innerText = d.toString().padStart(2, '0');
                el.querySelector('.hours').innerText = h.toString().padStart(2, '0');
                el.querySelector('.minutes').innerText = m.toString().padStart(2, '0');
                el.querySelector('.seconds').innerText = s.toString().padStart(2, '0');
                
                return true;
            }

            // Pierwsze wykonanie od razu (aby użytkownik nie widział samych zer przez 1 sekundę)
            updateTimer();
            if (updateTimer()) {
                var interval = setInterval(function() {
                    if (!updateTimer()) {
                        clearInterval(interval);
                    }
                }, 1000);
            }
        })();
        </script>";

        return $html;
    }
}