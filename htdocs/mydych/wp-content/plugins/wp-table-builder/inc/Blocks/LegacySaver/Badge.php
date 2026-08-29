<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Badge
{
    private static $element_id = 1;

    public static function render($block)
    {

        $props = $block['props'];
        $style = RenderUtils::generate_css_string([
            "justify-content" => $props['alignment'] ?? 'center',
            "padding" => $props['padding'] ?? '',
            "margin" => $props['margin'] ?? '',
        ]);

        $wrapperStyle = RenderUtils::generate_css_string([
            "font-size" => $props['fontSize'] ?? '',
            "color" => $props['color'] ?? '',
            "background-color" => $props['background'] ?? '',
            "border-radius" => $props['borderRadius'] ?? '',
        ]);

        $text = wp_kses_post($props['text'] ?? '');

        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-badge-container wptb-ph-element wptb-element-badge-{$element_id}" style="{$style}">
          <div class="wptb-badge-wrapper" style="{$wrapperStyle}">
            <p class="wptb-badge" style="position: relative">{$text}</p>
          </div>
        </div>
        HTML;
    }
}

