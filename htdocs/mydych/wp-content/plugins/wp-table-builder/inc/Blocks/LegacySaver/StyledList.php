<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class StyledList
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'];

        $style = RenderUtils::generate_css_string([
            "padding" => $props['padding'] ?? '',
            "margin" => $props['margin'] ?? '',
        ]);

        $vSpace = esc_attr($props['itemSpacing'] ?? '5px');
        $icSize = esc_attr($props['iconSize'] ?? '20px');
        $icColor = esc_attr($props['iconColor'] ?? 'rgb(0, 153, 71)');

        $iconSrc = esc_attr($props['icon'] ?? 'check');

        $items = '';

        foreach ($block['items'] as $item) {

            $alignment = esc_attr($item['alignment'] ?? 'left');
            $ttStyle = esc_attr($item['toolTipStyle'] ?? '');

            $text = wp_kses_post($item['text'] ?? '');
            $toolTip = wp_kses_post($item['toolTip'] ?? '');

            $itemColor = $item['color'] ?? $props['color'] ?? '#000000';
            $itemIconSrc = $item['icon'] ?? $iconSrc;
            $itemIconColor = $item['iconColor'] ?? $icColor;
            $itemIcon = isset($item['iconSVG']) && $item['iconSVG'] !== ''
                ? $item['iconSVG']
                : RenderUtils::get_icon($itemIconSrc);

            $itemTxtStyle = RenderUtils::generate_css_string([
                "font-size" => $props['fontSize'] ?? '',
                "line-height" => $props['fontSize'] ?? '',
                "color" => $itemColor,
                "margin-left" => $props['iconSpacing'] ?? '',
            ]);

            $liClass = 'wptb-in-element';
            if (isset($item['toolTip']) && trim($item['toolTip']) !== '') {
                $liClass .= ' wptb-tooltip wptb-tooltip-' . esc_attr($item['tooltipPosision'] ?? 'top');
            }
        
            $items .= <<<HTML
            <li class="{$liClass}" style="margin-bottom: {$vSpace}">
                <div class="wptb-styled-list-li-inner-wrap" data-wptb-styled-list-alignment="{$alignment}">
                    <div class="wptb-styled-list-icon" data-wptb-styled-list-icon-src="{$itemIconSrc}" style="width: {$icSize}; height: {$icSize}; flex: 0 0 {$icSize}; fill: {$itemIconColor}">
                        {$itemIcon}
                    </div>
                    <div class="wptb-styled-list-item-content" style="position: relative">
                        <p data-styled_list-marker="" style="{$itemTxtStyle}">
                            {$text}
                        </p>
                    </div>
                    <div class="wptb-m-tooltip" style="{$ttStyle}">
                        {$toolTip}
                    </div>
                </div>
                <div class="wptb-clear-both"></div>
            </li>
            HTML;
        }



        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-styled_list-container wptb-ph-element wptb-element-styled_list-{$element_id}" style="{$style}">
            <ul>
                {$items}
            </ul>
        </div>
        HTML;

    }
}

