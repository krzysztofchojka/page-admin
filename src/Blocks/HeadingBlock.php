<?php
namespace CMS\Blocks;

use CMS\Helpers\BlockStyle;

/**
 * Nagłówek z formatowaniem: poziom h1–h6, płynny (responsywny) rozmiar,
 * wyrównanie, kolor, grubość, krój, wersaliki, rozstrzelenie, cień.
 */
class HeadingBlock implements BlockInterface {

    // Rozmiary skalują się płynnie między telefonem a desktopem
    private const SIZES = [
        'sm'  => '1rem',
        'md'  => '1.25rem',
        'lg'  => 'clamp(1.2rem, 2.2vw, 1.5rem)',
        'xl'  => 'clamp(1.4rem, 3vw, 2rem)',
        '2xl' => 'clamp(1.6rem, 4vw, 2.6rem)',
        '3xl' => 'clamp(1.9rem, 5vw, 3.4rem)',
        '4xl' => 'clamp(2.2rem, 7vw, 4.6rem)',
    ];
    private const WEIGHTS = ['300', '400', '500', '600', '700', '800', '900'];
    private const FONTS = [
        'lato' => "'Lato', sans-serif",
        'montserrat' => "'Montserrat', sans-serif",
        'serif' => "Georgia, 'Times New Roman', serif",
        'mono' => "ui-monospace, Menlo, monospace",
    ];
    private const SPACING = ['tight' => '-0.02em', 'wide' => '0.05em', 'wider' => '0.1em', 'widest' => '0.2em'];

    public function render(array $block, $db): string {
        $d = is_array($block['content'] ?? null) ? $block['content'] : ['text' => (string)($block['content'] ?? '')];
        $text = trim((string)($d['text'] ?? ''));
        if ($text === '') return '';

        $level = in_array($d['level'] ?? 'h2', ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $d['level'] : 'h2';

        $st = ['margin:0', 'line-height:1.2'];
        $st[] = 'font-size:' . (self::SIZES[$d['size'] ?? ''] ?? self::SIZES[['h1' => '3xl', 'h2' => '2xl', 'h3' => 'xl', 'h4' => 'lg'][$level] ?? 'md']);
        $st[] = 'font-weight:' . (in_array((string)($d['weight'] ?? ''), self::WEIGHTS, true) ? $d['weight'] : '700');
        if (in_array($d['align'] ?? '', ['left', 'center', 'right'], true)) $st[] = 'text-align:' . $d['align'];
        if ($c = BlockStyle::color($d['color'] ?? '')) $st[] = 'color:' . $c;
        if (!empty($d['font']) && isset(self::FONTS[$d['font']])) $st[] = 'font-family:' . self::FONTS[$d['font']];
        if (!empty($d['upper'])) $st[] = 'text-transform:uppercase';
        if (!empty($d['spacing']) && isset(self::SPACING[$d['spacing']])) $st[] = 'letter-spacing:' . self::SPACING[$d['spacing']];
        if (!empty($d['shadow'])) $st[] = 'text-shadow:0 2px 8px rgba(0,0,0,.25)';
        $st[] = 'overflow-wrap:anywhere';

        // Tekst jest escapowany; Enter = łamanie linii
        $safe = nl2br(htmlspecialchars($text), false);

        $fontLink = !empty($d['font']) ? BlockStyle::fontLink($d['font']) : '';

        return $fontLink . '<' . $level . ' class="pa-heading" style="' . htmlspecialchars(implode(';', $st)) . '">' . $safe . '</' . $level . '>';
    }
}
