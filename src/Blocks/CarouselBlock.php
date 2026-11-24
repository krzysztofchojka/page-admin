<?php
namespace CMS\Blocks;
use CMS\Helpers\BlockRenderer;
use CMS\Helpers\BlockStyle;

/**
 * Karuzela / Zakładki z kafelkami.
 *
 * Opcje (content):
 *  - arrows       bool   strzałki poprzedni/następny
 *  - style        'default' | 'tiles'  (tiles = duże kafelki z ikoną i gradientem)
 *  - tileFrom/tileTo/activeColor/textColor  kolory kafelków
 *  - invertIcons  bool   odwraca kolory ikon (domyślnie true – zgodność wstecz)
 *  - panel        'card' | 'plain'  wygląd panelu z treścią
 *  - intro        [bloki] ekran startowy widoczny zanim użytkownik kliknie kafelek
 *  - tabs[]       {icon, label, link?, anchor?, children}
 *
 * Każda zakładka ma kotwicę (#agenda, #formularz-rejestracyjny…) – link do niej
 * z dowolnego miejsca strony otwiera właściwą zakładkę.
 */
class CarouselBlock implements BlockInterface{
    public function render(array $block, $db): string{
        $data = is_array($block['content']) ? $block['content'] : [];
        $rawTabs = $data['tabs'] ?? [];
        $arrows = !empty($data['arrows']);

        // 1. Zgodność wstecz: stare zapisy (sprzed poprawki edytora) mogły dublować
        //    zakładki wewnętrznych karuzel w karuzeli nadrzędnej – odfiltrowujemy je.
        $nestedTabLabels = [];
        $extractTabs = function($blocks) use (&$extractTabs, &$nestedTabLabels) {
            if (!is_array($blocks)) return;
            foreach ($blocks as $b) {
                if (!is_array($b)) continue;
                if (($b['type'] ?? '') === 'carousel') {
                    foreach ($b['content']['tabs'] ?? [] as $ct) {
                        $nestedTabLabels[] = $ct['label'] ?? '';
                    }
                }
                if (($b['type'] ?? '') === 'switcher') {
                    foreach ($b['content']['tabs'] ?? [] as $st) $extractTabs($st['children'] ?? []);
                }
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
        foreach ($rawTabs as $tab) {
            if (!empty($tab['children'])) $extractTabs($tab['children']);
        }
        $tabs = [];
        foreach ($rawTabs as $tab) {
            if (!is_array($tab) || in_array($tab['label'] ?? '', $nestedTabLabels, true)) continue;
            $tabs[] = $tab;
        }
        $tabs = array_values($tabs);
        if (empty($tabs)) return '';

        $cid = 'c_' . (!empty($block['settings']['id']) ? preg_replace('/[^a-z0-9_\-]/i', '', $block['settings']['id']) : substr(md5(json_encode($tabs)), 0, 8));

        $style = ($data['style'] ?? 'default') === 'tiles' ? 'tiles' : 'default';
        $invert = !isset($data['invertIcons']) || !empty($data['invertIcons']);
        $panel = ($data['panel'] ?? 'card') === 'plain' ? 'plain' : 'card';
        $intro = is_array($data['intro'] ?? null) ? $data['intro'] : [];
        $hasIntro = !empty($intro);

        $tileFrom = BlockStyle::color($data['tileFrom'] ?? '') ?: ($style === 'tiles' ? '#02228d' : '#1d4ed8');
        $tileTo = BlockStyle::color($data['tileTo'] ?? '') ?: ($style === 'tiles' ? '#1338b5' : '#1d4ed8');
        $activeColor = BlockStyle::color($data['activeColor'] ?? '') ?: '#1e3a8a';
        $textColor = BlockStyle::color($data['textColor'] ?? '') ?: '#ffffff';

        // Kotwice zakładek (unikalne)
        $anchors = [];
        foreach ($tabs as $idx => $tab) {
            $a = BlockStyle::slug((string)($tab['anchor'] ?? '')) ?: BlockStyle::slug((string)($tab['label'] ?? '')) ?: ('tab-' . ($idx + 1));
            while (in_array($a, $anchors, true)) $a .= '-' . ($idx + 1);
            $anchors[$idx] = $a;
        }

        $vars = "--c-from:{$tileFrom};--c-to:{$tileTo};--c-active:{$activeColor};--c-text:{$textColor}";
        $html = '<div class="mb-10 carousel-wrapper pa-carousel pa-carousel-' . $style . ($invert ? ' pa-carousel-invert' : '') . ' mt-10" id="' . $cid . '" style="' . $vars . '" data-intro="' . ($hasIntro ? '1' : '0') . '">';
        $html .= '<div class="pa-carousel-nav" role="tablist">';

        if ($arrows) {
            $html .= '<button type="button" class="c-arrow-'.$cid.' pa-carousel-arrow" data-dir="-1" aria-label="Poprzednia"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></button>';
        }

        foreach ($tabs as $idx => $tab) {
            $isActive = !$hasIntro && $idx === 0;
            $tSet = is_array($tab['settings'] ?? null) ? $tab['settings'] : [];
            $tId = !empty($tSet['id']) ? ' id="'.htmlspecialchars($tSet['id']).'"' : '';
            $tCls = !empty($tSet['css']) ? ' ' . htmlspecialchars($tSet['css']) : '';
            $tStyle = !empty($tSet['style']) ? ' style="'.htmlspecialchars($tSet['style']).'"' : '';

            $link = BlockStyle::url($tab['link'] ?? '');
            $inner = '';
            $icon = (string)($tab['icon'] ?? '');
            if ($icon !== '' && (strpos($icon, 'http') === 0 || strpos($icon, '/') === 0)) {
                $inner .= '<img src="'.htmlspecialchars($icon).'" alt="" class="pa-carousel-icon" loading="lazy">';
            } elseif ($icon !== '') {
                $inner .= '<span class="pa-carousel-emoji">'.htmlspecialchars($icon).'</span>';
            }
            $inner .= '<span class="pa-carousel-label">'.htmlspecialchars($tab['label'] ?? '').'</span>';

            $common = ' data-index="'.$idx.'" data-anchor="'.$anchors[$idx].'" class="c-btn-'.$cid.' pa-carousel-btn'.($isActive ? ' is-active' : '').$tCls.'"'.$tStyle;
            if ($link !== '') {
                $html .= '<a'.$tId.' href="'.htmlspecialchars($link).'"'.$common.' data-link="1">'.$inner.'</a>';
            } else {
                $html .= '<button type="button" role="tab" aria-selected="'.($isActive ? 'true' : 'false').'"'.$tId.$common.'>'.$inner.'</button>';
            }
        }

        if ($arrows) {
            $html .= '<button type="button" class="c-arrow-'.$cid.' pa-carousel-arrow" data-dir="1" aria-label="Następna"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></button>';
        }
        $html .= '</div>';

        // Ekran startowy
        if ($hasIntro) {
            $html .= '<div class="pa-carousel-intro" id="intro-'.$cid.'">' . BlockRenderer::render($intro, $db, false) . '</div>';
        }

        $panelCls = $panel === 'card' ? 'pa-carousel-panel pa-carousel-panel-card' : 'pa-carousel-panel';
        $html .= '<div class="'.$panelCls.'" id="panel-'.$cid.'"'.($hasIntro ? ' hidden' : '').'>';
        $html .= '<div id="track-'.$cid.'" class="pa-carousel-track">';
        foreach ($tabs as $idx => $tab) {
            $html .= '<div class="c-content-'.$cid.' pa-carousel-slide" role="tabpanel" data-index="'.$idx.'" id="'.$anchors[$idx].'-panel"'.((!$hasIntro && $idx === 0) ? '' : ' aria-hidden="true"').'>';
            if (!empty($tab['children'])) {
                $html .= BlockRenderer::render($tab['children'], $db, false);
            } else if (!empty($tab['content'])) {
                $html .= '<div class="prose max-w-none">' . ($tab['content'] ?? '') . '</div>';
            }
            $html .= '</div>';
        }
        $html .= '</div></div>';

        // JS – izolowany, obsługuje kotwice (#agenda), auto-wysokość i ekran startowy
        $html .= "<script>
        (function() {
            var cid = '{$cid}';
            var wrapper = document.getElementById(cid);
            if (!wrapper) return;
            var track = document.getElementById('track-' + cid);
            var panel = document.getElementById('panel-' + cid);
            var intro = document.getElementById('intro-' + cid);
            var buttons = Array.prototype.slice.call(wrapper.querySelectorAll('.c-btn-' + cid));
            var slides = Array.prototype.slice.call(track.querySelectorAll(':scope > .c-content-' + cid));
            var arrows = wrapper.querySelectorAll('.c-arrow-' + cid);
            var current = intro ? -1 : 0;
            var ro = null;

            function fit() {
                if (current < 0 || !slides[current]) return;
                track.style.height = slides[current].offsetHeight + 'px';
            }

            function goToSlide(index, opts) {
                opts = opts || {};
                if (index < 0 || index >= slides.length) return;
                var btn = buttons[index];
                if (btn && btn.dataset.link) return;
                current = index;
                if (intro) intro.hidden = true;
                panel.hidden = false;
                track.style.transform = 'translateX(-' + (index * 100) + '%)';
                slides.forEach(function(s, i) { s.setAttribute('aria-hidden', i === index ? 'false' : 'true'); });
                buttons.forEach(function(b) {
                    var on = parseInt(b.dataset.index, 10) === index;
                    b.classList.toggle('is-active', on);
                    if (b.tagName === 'BUTTON') b.setAttribute('aria-selected', on ? 'true' : 'false');
                });
                if (ro) { ro.disconnect(); ro.observe(slides[index]); }
                fit();
                if (opts.hash !== false && btn && history.replaceState) {
                    history.replaceState(null, '', '#' + btn.dataset.anchor);
                }
                if (opts.scroll) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            buttons.forEach(function(btn) {
                if (btn.dataset.link) return;
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    goToSlide(parseInt(this.dataset.index, 10));
                });
            });

            arrows.forEach(function(arrow) {
                arrow.addEventListener('click', function(e) {
                    e.stopPropagation();
                    var n = (current < 0 ? 0 : current) + parseInt(this.dataset.dir, 10);
                    if (n < 0) n = slides.length - 1;
                    if (n >= slides.length) n = 0;
                    goToSlide(n);
                });
            });

            function fromHash(scroll) {
                var h = decodeURIComponent((location.hash || '').replace('#', ''));
                if (!h) return false;
                for (var i = 0; i < buttons.length; i++) {
                    if (buttons[i].dataset.anchor === h) { goToSlide(i, { hash: false, scroll: scroll }); return true; }
                }
                return false;
            }
            window.addEventListener('hashchange', function() { fromHash(true); });

            if ('ResizeObserver' in window) ro = new ResizeObserver(fit);
            window.addEventListener('resize', fit);
            window.addEventListener('load', fit);

            if (!fromHash(false) && current === 0) goToSlide(0, { hash: false });

            requestAnimationFrame(function() {
                setTimeout(function() { track.classList.add('pa-carousel-animate'); }, 50);
            });
        })();
        </script></div>";

        return $html;
    }
}
