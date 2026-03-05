<?php
namespace CMS\Blocks;

class GalleryBlock implements BlockInterface {
    private static $assetsLoaded = false;

    public function render(array $block, $db): string {
        $gal = $db->query("SELECT * FROM pa_galleries WHERE id = :id", ['id' => $block['content']])->fetch();
        if (!$gal) return '';

        $imgs = json_decode($gal['images_json'], true);
        $settings = json_decode($gal['settings'] ?? '{}', true);
        if (empty($imgs)) return '';

        $html = '<div class="mb-10"><h3 class="text-2xl font-bold mb-6 text-gray-800">'.htmlspecialchars($gal['title']).'</h3>';

        if (!self::$assetsLoaded) {
            $html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>';
            $html .= '<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>';
            $html .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"/>';
            $html .= '<script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>';
            $html .= '<script>document.addEventListener("DOMContentLoaded", function() { if(typeof GLightbox !== "undefined") { GLightbox({ selector: ".glightbox" }); } });</script>';
            self::$assetsLoaded = true;
        }

        if ($gal['type'] === 'grid') {
            $maxVisible = 5;
            $total = count($imgs);
            $html .= '<div class="grid grid-cols-6 gap-2 md:gap-3 rounded-xl overflow-hidden shadow-sm">';
            foreach ($imgs as $index => $img) {
                if ($index >= $maxVisible) {
                    $html .= "<a href='$img' class='glightbox hidden' data-gallery='gallery-{$gal['id']}'></a>";
                    continue;
                }
                $classes = 'block relative overflow-hidden group bg-gray-100';
                if ($total === 1) $classes .= ' col-span-6 aspect-video';
                elseif ($total === 2) $classes .= ' col-span-3 aspect-[4/3] md:aspect-video';
                elseif ($total === 3) $classes .= ($index === 0) ? ' col-span-6 aspect-video md:aspect-[21/9]' : ' col-span-3 aspect-square md:aspect-[4/3]';
                elseif ($total === 4) $classes .= ($index === 0) ? ' col-span-6 aspect-video md:aspect-[21/9]' : ' col-span-2 aspect-square';
                else $classes .= ($index < 2) ? ' col-span-3 aspect-square md:aspect-[4/3]' : ' col-span-2 aspect-square';
                
                $html .= "<a href='$img' class='glightbox $classes' data-gallery='gallery-{$gal['id']}'>";
                $html .= "<img src='$img' class='w-full h-full object-cover transition-transform duration-500 group-hover:scale-110'>";
                if ($index === 4 && $total > 5) {
                    $more = $total - 5;
                    $html .= "<div class='absolute inset-0 bg-black/60 flex items-center justify-center text-white font-bold text-3xl md:text-5xl backdrop-blur-sm transition-colors group-hover:bg-black/50'>+{$more}</div>";
                }
                $html .= "</a>";
            }
            $html .= '</div>';
        } else {
            $swiperId = 'gallery_swiper_' . $gal['id'] . '_' . md5(uniqid());
            $isLoop = !empty($settings['loop']) ? 'true' : 'false';
            $showNav = !empty($settings['nav']);
            $showPag = !empty($settings['pag']);
            $autoplay = (!empty($settings['autoplay']) && $settings['autoplay'] > 0) ? "{ delay: {$settings['autoplay']}, disableOnInteraction: false }" : 'false';
            $effect = 'slide';
            $extraConfig = '';
            $containerClasses = 'rounded-xl shadow-sm relative';
            $slideClasses = 'relative bg-gray-100 group rounded-xl overflow-hidden';
            
            if ($gal['type'] === 'swiper_coverflow') {
                $effect = 'coverflow'; $extraConfig = "coverflowEffect: { rotate: 50, stretch: 0, depth: 100, modifier: 1, slideShadows: true },";
                $containerClasses .= ' !p-4 !-m-4 !overflow-visible'; $slideClasses .= ' aspect-[4/3] md:aspect-[16/9]';
            } elseif ($gal['type'] === 'swiper_fade') {
                $effect = 'fade'; $extraConfig = "fadeEffect: { crossFade: true },";
                $containerClasses .= ' overflow-hidden'; $slideClasses .= ' aspect-[4/3] md:aspect-[16/9]';
            } elseif ($gal['type'] === 'swiper_cards') {
                $effect = 'cards'; $extraConfig = "cardsEffect: { slideShadows: true }, grabCursor: true,";
                $containerClasses .= ' !overflow-visible max-w-sm mx-auto mt-8 mb-12'; $slideClasses .= ' aspect-[3/4] shadow-lg';
            } else {
                $containerClasses .= ' overflow-hidden'; $slideClasses .= ' aspect-[4/3] md:aspect-[16/9]';
            }

            $html .= '<div class="swiper '.$swiperId.' '.$containerClasses.'"><div class="swiper-wrapper">';
            foreach ($imgs as $img) {
                $html .= '<div class="swiper-slide w-full h-full">';
                $html .= "<a href='$img' class='glightbox block {$slideClasses}' data-gallery='gallery-{$gal['id']}'><img src='$img' draggable='false' class='w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 select-none'></a>";
                $html .= '</div>';
            }
            $html .= '</div>';

            $navNext = 'next_' . $swiperId; $navPrev = 'prev_' . $swiperId; $pagEl = 'pag_' . $swiperId;
            if ($showPag) $html .= '<div class="swiper-pagination '.$pagEl.' !bottom-0"></div>';
            if ($showNav) {
                $html .= '<button type="button" class="'.$navPrev.' absolute top-1/2 left-4 z-50 -translate-y-1/2 cursor-pointer text-white w-10 h-10 bg-black/30 rounded-full shadow-lg border border-white/20 flex items-center justify-center hover:bg-black/60 transition backdrop-blur-sm focus:outline-none"><span class="text-xl font-bold leading-none">&lt;</span></button>';
                $html .= '<button type="button" class="'.$navNext.' absolute top-1/2 right-4 z-50 -translate-y-1/2 cursor-pointer text-white w-10 h-10 bg-black/30 rounded-full shadow-lg border border-white/20 flex items-center justify-center hover:bg-black/60 transition backdrop-blur-sm focus:outline-none"><span class="text-xl font-bold leading-none">&gt;</span></button>';
            }
            $html .= '</div>';

            $jsBreakpoints = ""; $jsSlidesPerView = "1"; $imgCount = count($imgs);
            if ($effect === 'slide') {
                $jsBreakpoints = "breakpoints: { 640: { slidesPerView: 1, spaceBetween: 16 }, 768: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 24 } }";
            } elseif ($effect === 'coverflow') {
                $jsSlidesPerView = "'auto'"; $jsBreakpoints = "breakpoints: { 640: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } }";
            }
            if ($imgCount <= 1 || ($effect === 'cards' && $imgCount < 4) || ($effect === 'fade' && $imgCount < 2)) $isLoop = 'false';
            $jsSpaceBetween = in_array($effect, ['fade', 'cards']) ? '0' : '12';

            $html .= "<script>
            document.addEventListener('DOMContentLoaded', function() {
                new Swiper('.$swiperId', {
                    effect: '{$effect}', {$extraConfig} loop: {$isLoop}, autoplay: {$autoplay}, observer: true, observeParents: true,
                    ".($showPag ? "pagination: { el: '.{$pagEl}', clickable: true, dynamicBullets: true }," : "")."
                    ".($showNav ? "navigation: { nextEl: '.{$navNext}', prevEl: '.{$navPrev}' }," : "")."
                    slidesPerView: {$jsSlidesPerView}, spaceBetween: {$jsSpaceBetween}, {$jsBreakpoints}
                });
            });
            </script>";
        }
        $html .= '</div>';
        return $html;
    }
}