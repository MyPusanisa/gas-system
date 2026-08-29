<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Shortcode
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'];
        $style = RenderUtils::generate_css_string([
            'margin' => $props['margin'] ?? '',
            'padding' => $props['padding'] ?? '',
        ]);

        $shortcode = trim($props['shortcode'] ?? '');

        $code = empty($shortcode) ? '' : RenderUtils::strip_xss($shortcode);

        $element_id = self::$element_id++;

        return <<<HTML
        <div class="wptb-shortcode-container wptb-ph-element wptb-element-shortcode-{$element_id}" style="{$style}">
            <wptb_shortcode_container_element>
                <div class="" style="position: relative;">{$code}</div>
            </wptb_shortcode_container_element>
        </div>
        HTML;

    }
}

