<?php
namespace CMS\Blocks;
use CMS\Helpers\BlockRenderer;

class CarouselBlock implements BlockInterface{
    public function render(array $block, $db): string{
        $data = is_array($block['content']) ? $block['content'] : [];
        $rawTabs = $data['tabs'] ?? [];
        $arrows = $data['arrows'] ?? false;
        
        // 1. MECHANIZM FILTRUJĄCY: Wyszukuje zakładki należące do wewnętrznych karuzel
        $nestedTabLabels = [];
        $extractTabs = function($blocks) use (&$extractTabs, &$nestedTabLabels) {
            if (!is_array($blocks)) return;
            foreach ($blocks as $b) {
                if (($b['type'] ?? '') === 'carousel') {
                    foreach ($b['content']['tabs'] ?? [] as $ct) {
                        $nestedTabLabels[] = $ct['label'] ?? '';
                    }
                }
                // Sprawdzamy zagnieżdżenia w kolumnach i innych blokach
                if (!empty($b['children']) && is_array($b['children'])) {
                    if (isset($b['children'][0])) {
                        $extractTabs($b['children']);
                    } else {
                        foreach ($b['children'] as $colBlocks) {
                            if (is_array($colBlocks)) $extractTabs($colBlocks);
                        }
                    }
                }
            }
        };

        // Uruchamiamy wyszukiwanie wewnętrznych zakładek
        foreach ($rawTabs as $tab) {
            if (!empty($tab['children'])) {
                $extractTabs($tab['children']);
            }
        }

        // 2. Filtrujemy główne zakładki - usuwamy te zagnieżdżone przez błąd Page Buildera
        $tabs = [];
        foreach ($rawTabs as $tab) {
            if (in_array($tab['label'] ?? '', $nestedTabLabels)) {
                continue; // Pomijamy zakładki karuzeli wewnętrznej
            }
            $tabs[] = $tab;
        }
        
        // Przeindeksowanie tablicy (kluczowe, aby skrypt przesuwający slajdy działał poprawnie)
        $tabs = array_values($tabs);

        // Stabilne i unikalne ID dzięki zdekodowanym i przefiltrowanym zakładkom (wymagane dla LocalStorage)
        $cid = 'c_' . (!empty($block['settings']['id']) ? htmlspecialchars($block['settings']['id']) : substr(md5(json_encode($tabs)), 0, 8));

        if (empty($tabs)) return '';

        $html = '<div class="mb-10 carousel-wrapper mt-10" id="'.$cid.'">';
        $html .= '<div class="flex flex-wrap gap-3 justify-center mb-8 items-center">';

        if ($arrows) {
            $html .= '<button class="c-arrow-'.$cid.' bg-blue-900 text-white w-10 h-10 rounded-lg font-bold hover:bg-blue-800 transition shadow flex items-center justify-center" data-dir="-1"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>';
        }

        foreach ($tabs as $idx => $tab) {
            $activeClass = $idx === 0 ? 'bg-blue-900 scale-105' : 'bg-blue-700 hover:bg-blue-800';
            
            $tSet = $tab['settings'] ?? [];
            $tId = !empty($tSet['id']) ? ' id="'.htmlspecialchars($tSet['id']).'"' : '';
            $tCls = !empty($tSet['css']) ? ' ' . htmlspecialchars($tSet['css']) : '';
            $tStyle = !empty($tSet['style']) ? ' style="'.htmlspecialchars($tSet['style']).'"' : '';

            $html .= '<button'.$tId.' data-index="'.$idx.'" class="c-btn-'.$cid.' flex flex-col items-center justify-center p-4 rounded-xl w-32 md:w-40 text-white shadow-lg transition-all duration-300 transform '.$activeClass.$tCls.'"'.$tStyle.'>';
            
            if (strpos($tab['icon'] ?? '', 'http') === 0 || strpos($tab['icon'] ?? '', '/') === 0) {
                $html .= '<img src="'.htmlspecialchars($tab['icon'] ?? '').'" class="h-8 w-8 mb-2 invert">';
            } else {
                $html .= '<span class="text-3xl mb-2 block">'.htmlspecialchars($tab['icon'] ?? '').'</span>';
            }
            
            $html .= '<span class="text-xs md:text-sm font-bold text-center leading-tight uppercase">'.htmlspecialchars($tab['label'] ?? '').'</span>';
            $html .= '</button>';
        }

        if ($arrows) {
            $html .= '<button class="c-arrow-'.$cid.' bg-blue-900 text-white w-10 h-10 rounded-lg font-bold hover:bg-blue-800 transition shadow flex items-center justify-center" data-dir="1"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>';
        }
        $html .= '</div>';

        $html .= '<div class="bg-white rounded-xl shadow border-t-4 border-blue-900 relative overflow-hidden">';
        $html .= '<div id="track-'.$cid.'" class="flex" style="transform: translateX(0%);">';
        foreach ($tabs as $idx => $tab) {
            $html .= '<div class="c-content-'.$cid.' p-6 md:p-10 shrink-0" data-index="'.$idx.'" style="flex: 0 0 100%; max-width: 100%; width: 100%;">';
            if (!empty($tab['children'])) {
                $html .= BlockRenderer::render($tab['children'], $db, false);
            } else if (!empty($tab['content'])) {
                $html .= '<div class="prose max-w-none">' . ($tab['content'] ?? '') . '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div></div>';

        // 3. W PEŁNI IZOLOWANY JAVASCRIPT
        $html .= "<script>
        (function() {
            const cid = '{$cid}';
            const wrapper = document.getElementById(cid);
            if (!wrapper) return;

            const track = wrapper.querySelector('#track-' + cid);
            const buttons = wrapper.querySelectorAll('.c-btn-' + cid);
            const arrows = wrapper.querySelectorAll('.c-arrow-' + cid);
            const total = " . count($tabs) . ";

            function goToSlide(index) {
                if (track) track.style.transform = 'translateX(-' + (index * 100) + '%)';
                
                buttons.forEach(btn => {
                    if (parseInt(btn.dataset.index) === index) {
                        btn.classList.remove('bg-blue-700', 'hover:bg-blue-800');
                        btn.classList.add('bg-blue-900', 'scale-105');
                    } else {
                        btn.classList.add('bg-blue-700', 'hover:bg-blue-800');
                        btn.classList.remove('bg-blue-900', 'scale-105');
                    }
                });
            }

            buttons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Kluczowe dla zagnieżdżeń: blokuje zdarzenie przed wyciekaniem \"w górę\"
                    goToSlide(parseInt(this.dataset.index));
                });
            });

            arrows.forEach(arrow => {
                arrow.addEventListener('click', function(e) {
                    e.stopPropagation();
                    let currentIndex = 0;
                    buttons.forEach(btn => {
                        if (btn.classList.contains('bg-blue-900')) currentIndex = parseInt(btn.dataset.index);
                    });
                    
                    let newIndex = currentIndex + parseInt(this.dataset.dir);
                    if (newIndex < 0) newIndex = total - 1;
                    if (newIndex >= total) newIndex = 0;
                    
                    goToSlide(newIndex);
                });
            });

            if (track) {
                requestAnimationFrame(() => {
                    setTimeout(() => {
                        track.classList.add('transition-transform', 'duration-500', 'ease-in-out');
                    }, 50);
                });
            }
        })();
        </script></div>";

        return $html;
    }
}