<?php
namespace CMS\Helpers;

class BlockRenderer {
    // Rejestr Twoich 25 Klocków
    private static $registry = [
        'columns_2' => \CMS\Blocks\Columns2Block::class,
        'columns_3' => \CMS\Blocks\Columns3Block::class,
        'text' => \CMS\Blocks\TextBlock::class,
        'image' => \CMS\Blocks\ImageBlock::class,
        'video' => \CMS\Blocks\VideoBlock::class,
        'button' => \CMS\Blocks\ButtonBlock::class,
        'divider' => \CMS\Blocks\DividerBlock::class,
        'quote' => \CMS\Blocks\QuoteBlock::class,
        'accordion' => \CMS\Blocks\AccordionBlock::class,
        'form' => \CMS\Blocks\FormBlock::class,
        'linked_image' => \CMS\Blocks\LinkedImageBlock::class,
        'carousel' => \CMS\Blocks\CarouselBlock::class,
        'gallery' => \CMS\Blocks\GalleryBlock::class,
        'image_cards' => \CMS\Blocks\ImageCardsBlock::class,
        'banner' => \CMS\Blocks\BannerBlock::class,
        'raw_html' => \CMS\Blocks\RawHtmlBlock::class,
        'map' => \CMS\Blocks\MapBlock::class,
        'countdown' => \CMS\Blocks\CountdownBlock::class,
        'table' => \CMS\Blocks\TableBlock::class,
        'flight' => \CMS\Blocks\FlightBlock::class,
        'system_login' => \CMS\Blocks\SystemLoginBlock::class,
        'system_register' => \CMS\Blocks\SystemRegisterBlock::class,
        'system_change_password' => \CMS\Blocks\SystemChangePasswordBlock::class,
        'system_lockdown' => \CMS\Blocks\SystemLockdownBlock::class,
        'posts_grid' => \CMS\Blocks\PostsGridBlock::class,
        // Bloki układu i formatowania (v2.1)
        'section' => \CMS\Blocks\SectionBlock::class,
        'grid' => \CMS\Blocks\GridBlock::class,
        'heading' => \CMS\Blocks\HeadingBlock::class,
        'ticker' => \CMS\Blocks\TickerBlock::class,
        'switcher' => \CMS\Blocks\SwitcherBlock::class,
        'schedule' => \CMS\Blocks\ScheduleBlock::class,
        'translate' => \CMS\Blocks\TranslateBlock::class,
    ];

    public static function render($blocks, $db, $echo = true) {
        if (empty($blocks)) return '';
        $output = '';

        foreach ($blocks as $block) {
            $bType = $block['type'] ?? '';
            if (empty($bType) || !isset(self::$registry[$bType])) continue;

            $set = is_array($block['settings'] ?? null) ? $block['settings'] : [];
            [$fmtClasses, $fmtStyles] = BlockStyle::fromSettings($set);

            $idAttr = !empty($set['id']) ? ' id="'.htmlspecialchars($set['id']).'"' : '';
            $clsAttr = trim((!empty($set['css']) ? $set['css'] : '') . ' ' . $fmtClasses);
            $clsAttr = $clsAttr !== '' ? ' ' . htmlspecialchars($clsAttr) : '';
            // Ręczne style z pola "Style wew." mają pierwszeństwo przed presetami
            $allStyles = trim($fmtStyles . ($fmtStyles !== '' && !empty($set['style']) ? ';' : '') . ($set['style'] ?? ''));
            $styleAttr = $allStyles !== '' ? ' style="'.htmlspecialchars($allStyles).'"' : '';

            $output .= "<div{$idAttr} class=\"block-wrapper mb-0{$clsAttr}\"{$styleAttr}>";
            
            $blockClass = self::$registry[$bType];
            $instance = new $blockClass();
            $output .= $instance->render($block, $db);
            
            $output .= "</div>";
        }

        if ($echo) echo $output;
        return $output;
    }
}