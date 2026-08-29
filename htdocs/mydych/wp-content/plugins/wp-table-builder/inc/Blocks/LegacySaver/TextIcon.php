<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class TextIcon
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'];

        $iconLoc = $props['iconLocation'] ?? 'left';
        $space = $props['spaceBetween'] ?? '5px';

        $attrs = RenderUtils::generate_attrs_string([
            "data-wptb-text-icon-space-between" => $space,
            "data-wptb-text-icon-alignment" => $props['alignment'] ?? 'left',
            "data-wptb-text-icon-icon-location" => $iconLoc,
        ]);

        $style = RenderUtils::generate_css_string([
            "font-size" => $props['fontSize'] ?? '',
            "padding" => $props['padding'] ?? '',
            "margin" => $props['margin'] ?? '',
        ]);

        $iconSrc = esc_attr($props['icon'] ?? 'star');
        $icon = RenderUtils::get_icon($iconSrc);
        $iconSize = esc_attr($props['iconSize'] ?? '15px');
        $iconColor = esc_attr($props['iconColor'] ?? '#000000');

        $text = wp_kses_post($props['text'] ?? '');
        $txtStyle = RenderUtils::generate_css_string(
            [
                'color' => $props['color'] ?? '#000000',
            ] + ($iconLoc === 'left' ? [
                    'margin-left' => $space,
                    'margin-right' => '0',
                ] : [
                    'margin-left' => '0',
                    'margin-right' => $space,
                ])
        );

        $element_id = self::$element_id++;

        return <<<HTML
        <div class="wptb-text_icon_element-container wptb-ph-element mce-content-body wptb-element-text_icon_element-{$element_id}" {$attrs} style="{$style}">
            <div class="wptb-element-text-icon-wrapper">
                <div id="wptbTextIconIconWrapper" class="wptb-text-icon-icon-wrapper" data-wptb-text-icon-icon-src="{$iconSrc}" style="color: {$iconColor}; width: {$iconSize}; height: {$iconSize};">
                    {$icon}
                    <br />
                </div>
                <div id="wptbTextIconMainTextWrapper" style="{$txtStyle}">
                    <p id="wptbTextIconMainText">{$text}</p>
                </div>
            </div>
        </div>
        HTML;

    }
}

