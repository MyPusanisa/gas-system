<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class CustomHtml
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'];
        
        $style = RenderUtils::generate_css_string([
            "padding" => $props['padding'] ?? '',
            "margin" => $props['margin'] ?? '',
        ]);

        $html = RenderUtils::strip_xss($props['html']);

        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-custom_html-container wptb-ph-element wptb-element-custom_html-{$element_id} edit-active" style="{$style}">
            <div class="wptb-custom-html-wrapper" data-wptb-new-element="1" style="position: relative">
                {$html}
            </div>
        </div>
        HTML;
    }
}

