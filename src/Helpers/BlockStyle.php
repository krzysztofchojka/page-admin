<?php
namespace CMS\Helpers;

/**
 * Uniwersalne formatowanie bloków (panel ⚙️ w Page Builderze).
 * Zamienia ustawienia bloku na bezpieczne klasy i style inline.
 * Wszystkie wartości są walidowane białą listą lub regexem – nic z ustawień
 * nie trafia do HTML bez kontroli.
 */
class BlockStyle {

    private const PAD = [
        'none' => '0',
        'sm'   => '0.75rem',
        'md'   => 'clamp(1rem, 3vw, 1.5rem)',
        'lg'   => 'clamp(1.25rem, 4vw, 2.5rem)',
        'xl'   => 'clamp(1.5rem, 6vw, 4rem)',
    ];

    private const SPACE = [
        'none' => '0',
        'sm'   => '0.75rem',
        'md'   => '1.5rem',
        'lg'   => 'clamp(2rem, 5vw, 3rem)',
        'xl'   => 'clamp(3rem, 8vw, 5rem)',
    ];

    private const MAXW = [
        'sm' => '640px', 'md' => '768px', 'lg' => '1024px', 'xl' => '1200px', '2xl' => '1400px',
    ];

    private const RADIUS = [
        'sm' => '0.375rem', 'md' => '0.75rem', 'lg' => '1.25rem', 'xl' => '2rem', 'full' => '9999px',
    ];

    private const SHADOW = [
        'sm' => '0 1px 3px rgba(0,0,0,.12)',
        'md' => '0 4px 12px rgba(0,0,0,.15)',
        'lg' => '0 10px 30px rgba(0,0,0,.2)',
    ];

    private const ALIGN = ['left', 'center', 'right', 'justify'];

    private const POS = [
        'fixed-bl' => 'pa-pos-fixed-bl',
        'fixed-br' => 'pa-pos-fixed-br',
        'fixed-tl' => 'pa-pos-fixed-tl',
        'fixed-tr' => 'pa-pos-fixed-tr',
        'sticky'   => 'pa-pos-sticky',
    ];

    private const SHOW = [
        'desktop' => 'pa-only-desktop',
        'mobile'  => 'pa-only-mobile',
    ];

    /** Zwraca [klasy, style] dla wrappera bloku. */
    public static function fromSettings(array $set): array {
        $classes = [];
        $styles = [];

        if (!empty($set['align']) && in_array($set['align'], self::ALIGN, true)) {
            $styles[] = 'text-align:' . $set['align'];
        }

        if (!empty($set['maxw'])) {
            $mw = self::MAXW[$set['maxw']] ?? self::length($set['maxw']);
            if ($mw) {
                $styles[] = 'max-width:' . $mw;
                $styles[] = 'margin-left:auto';
                $styles[] = 'margin-right:auto';
            }
        }

        if (!empty($set['pad']) && isset(self::PAD[$set['pad']])) {
            $styles[] = 'padding:' . self::PAD[$set['pad']];
        }
        if (isset($set['mb']) && $set['mb'] !== '' && isset(self::SPACE[$set['mb']])) {
            $styles[] = 'margin-bottom:' . self::SPACE[$set['mb']];
        }
        if (isset($set['mt']) && $set['mt'] !== '' && isset(self::SPACE[$set['mt']])) {
            $styles[] = 'margin-top:' . self::SPACE[$set['mt']];
        }

        if ($c = self::color($set['bg'] ?? '')) $styles[] = 'background:' . $c;
        if ($c = self::color($set['color'] ?? '')) $styles[] = 'color:' . $c;

        if (!empty($set['radius']) && isset(self::RADIUS[$set['radius']])) {
            $styles[] = 'border-radius:' . self::RADIUS[$set['radius']];
            $styles[] = 'overflow:hidden';
        }
        if (!empty($set['shadow']) && isset(self::SHADOW[$set['shadow']])) {
            $styles[] = 'box-shadow:' . self::SHADOW[$set['shadow']];
        }

        if (!empty($set['show']) && isset(self::SHOW[$set['show']])) $classes[] = self::SHOW[$set['show']];
        if (!empty($set['pos']) && isset(self::POS[$set['pos']])) $classes[] = self::POS[$set['pos']];

        return [implode(' ', $classes), implode(';', $styles)];
    }

    /** Dozwolone: #rgb, #rrggbb, #rrggbbaa, rgb()/rgba()/hsl()/hsla(), transparent, nazwy kolorów. */
    public static function color($v): string {
        $v = trim((string)$v);
        if ($v === '') return '';
        if (preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $v)) return $v;
        if (preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/]+\)$/i', $v)) return $v;
        if (preg_match('/^[a-z]{3,20}$/i', $v)) return strtolower($v);
        return '';
    }

    /** Dozwolone długości: 120px, 50%, 3rem, 80vw, 20vh, 2em, 0, lub sama liczba (=px). */
    public static function length($v): string {
        $v = trim((string)$v);
        if ($v === '') return '';
        if (preg_match('/^\d+(\.\d+)?$/', $v)) return $v . 'px';
        if (preg_match('/^\d+(\.\d+)?(px|%|rem|em|vw|vh|svh|dvh)$/', $v)) return $v;
        return '';
    }

    /** Bezpieczny URL obrazka/linku (bez javascript:, data: tylko dla obrazów). */
    public static function url($v, bool $allowDataImage = false): string {
        $v = trim((string)$v);
        if ($v === '') return '';
        if (preg_match('/^(https?:)?\/\//i', $v) || str_starts_with($v, '/') || str_starts_with($v, '#')
            || preg_match('/^(mailto|tel):/i', $v) || preg_match('/^[a-z0-9._\-\/?=&%]+$/i', $v)) {
            return $v;
        }
        if ($allowDataImage && preg_match('/^data:image\/(png|jpe?g|gif|webp|svg\+xml);/i', $v)) return $v;
        return '';
    }

    /** Escapowanie wartości do atrybutu style w url('...'). */
    public static function cssUrl(string $url): string {
        return "url('" . str_replace(["'", '"', '\\', "\n", "\r", '(', ')'], ['%27', '%22', '%5C', '', '', '%28', '%29'], $url) . "')";
    }

    private static array $loadedFonts = [];

    /** Czcionki Google ładowane tylko wtedy, gdy blok faktycznie ich używa (raz na stronę). */
    private const GOOGLE_FONTS = [
        'lato' => 'Lato:ital,wght@0,300;0,400;0,700;0,900;1,400',
        'montserrat' => 'Montserrat:wght@400;600;700;800',
    ];

    public static function fontLink(string $font): string {
        $font = strtolower($font);
        if (!isset(self::GOOGLE_FONTS[$font]) || isset(self::$loadedFonts[$font])) return '';
        self::$loadedFonts[$font] = true;
        return '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . self::GOOGLE_FONTS[$font] . '&display=swap">';
    }

    /** Wykrywa użycie czcionek Google w HTML (np. z edytora tekstu) i zwraca potrzebne linki. */
    public static function fontLinksFor(string $html): string {
        $out = '';
        foreach (array_keys(self::GOOGLE_FONTS) as $f) {
            if (stripos($html, $f) !== false && preg_match('/font-family:\s*["\']?' . $f . '/i', $html)) {
                $out .= self::fontLink($f);
            }
        }
        return $out;
    }

    /** Kotwica z etykiety: "Informacje praktyczne" -> "informacje-praktyczne". */
    public static function slug(string $s): string {
        $map = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ź'=>'z','ż'=>'z',
                'Ą'=>'a','Ć'=>'c','Ę'=>'e','Ł'=>'l','Ń'=>'n','Ó'=>'o','Ś'=>'s','Ź'=>'z','Ż'=>'z'];
        $s = strtr($s, $map);
        $s = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $s));
        return trim($s, '-');
    }
}
