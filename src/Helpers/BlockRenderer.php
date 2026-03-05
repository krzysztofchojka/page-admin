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
    ];

    public static function render($blocks, $db, $echo = true) {
        if (empty($blocks)) return '';
        $output = '';

        foreach ($blocks as $block) {
            $bType = $block['type'] ?? '';
            if (empty($bType) || !isset(self::$registry[$bType])) continue;

            $set = $block['settings'] ?? [];
            $idAttr = !empty($set['id']) ? ' id="'.htmlspecialchars($set['id']).'"' : '';
            $clsAttr = !empty($set['css']) ? ' ' . htmlspecialchars($set['css']) : '';
            $styleAttr = !empty($set['style']) ? ' style="'.htmlspecialchars($set['style']).'"' : '';

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