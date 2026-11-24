<?php
namespace CMS\Blocks;

/**
 * Widżet Google Tłumacz (wybór języka). Pozycję (np. przyklejony w prawym
 * dolnym rogu) ustawia się w uniwersalnym panelu formatowania bloku.
 */
class TranslateBlock implements BlockInterface {
    private static bool $loaded = false;

    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : [];
        $pageLang = preg_replace('/[^a-z\-]/i', '', (string)($d['pageLang'] ?? 'pl')) ?: 'pl';
        $langs = implode(',', array_filter(array_map(fn($l) => preg_replace('/[^a-z\-]/i', '', trim($l)), explode(',', (string)($d['langs'] ?? 'en,pl')))));

        // Google obsługuje jeden widżet na stronę
        if (self::$loaded) return '';
        self::$loaded = true;

        return '<div id="pa_translate" class="pa-translate"></div>'
            . '<script>function paTranslateInit(){new google.translate.TranslateElement({pageLanguage:' . json_encode($pageLang) . ',includedLanguages:' . json_encode($langs) . '},"pa_translate");}</script>'
            . '<script src="https://translate.google.com/translate_a/element.js?cb=paTranslateInit" defer></script>';
    }
}
