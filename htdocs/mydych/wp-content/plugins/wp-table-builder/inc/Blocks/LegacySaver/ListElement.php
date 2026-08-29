<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class ListElement
{
    private static $element_id = 1;

    public static function render($block)
    {

        $attrs = $block['props'];
        $listStyle = RenderUtils::generate_css_string([
            'padding' => $attrs['padding'] ?? null,
            'margin' => $attrs['margin'] ?? null,
        ]);

        $pClass = '';
        if (isset($attrs['type']) && $attrs['type'] == 'unordered') {
            $pClass = esc_attr($attrs['listIcon']);
        }
        $items = '';
        $i = 0;

        foreach ($block['items'] as $item) {
            $i++;
            $liStyle = RenderUtils::generate_css_string([
                'margin-bottom' => $attrs['itemSpacing'] ?? '',
            ]);

            $pStyle = RenderUtils::generate_css_string([
                'color' => $item['color'] ?? $attrs['color'] ?? '',
                'font-size' => $attrs['fontSize'] ?? '',
                'text-align' => $item['alignment'] ?? '',
            ]);
            $liClass = 'wptb-in-element';
            $item['toolTip'] = trim($item['toolTip'] ?? '');

            if ($item['toolTip'] != '') {
                $liClass .= ' wptb-tooltip wptb-tooltip-' . esc_attr($item['tooltipPosision'] ?? 'top');
            }

            $ttStyle = esc_attr($item['toolTipStyle'] ?? '');
            $text = wp_kses_post($item['text']);
            $ttText = wp_kses_post($item['toolTip']);
    
            $items .= <<<HTML
            <li class="{$liClass}" style="{$liStyle}">
                <div class="wptb-list-item-content" style="position: relative">
                    <p data-list-style-type-index="{$i}" style="{$pStyle}" class="{$pClass}">
                        {$text}
                    </p>
                </div>
                <div class="wptb-m-tooltip" style="{$ttStyle}">
                    {$ttText}
                </div>
            </li>
            HTML;
        }
    
        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-list-container wptb-ph-element wptb-element-list-{$element_id}" style="{$listStyle}">
            <ul>
                {$items}
            </ul>
        </div>
        HTML;

    }
}

