<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Icon
{
    private static $element_id = 1;

    public static function render($block)
    {

        $props = $block['props'];

        $style = RenderUtils::generate_css_string([
            'padding' => $props['padding'] ?? '',
            'margin' => $props['margin'] ?? '',
        ]);

        $size = esc_attr($props['size'] ?? '25px');
        $icons = '';

        $i = 0;
        $iconCount = max((int) $props['count'] ?? 1, 1);

        foreach ($block['icons'] as $icon) {
            $i++;
            if ($i > $iconCount) {
                break;
            }
            $lTag = 'span';
            $lAttrs = "";
            $color = esc_attr($icon['color'] ?? 'rgb(0, 0, 0)');
            $iconSrc = esc_attr($icon['icon'] ?? 'star');
            $iconStr = RenderUtils::get_icon($iconSrc);

            if ( isset($icon['url']) && $icon['url'] !== '') {
                $lTag = 'a';
                if ($icon['convertToAbsolute'] && !preg_match('/^https?:\/\//', $icon['url'])) {
                    $icon['url'] = 'https://' . ltrim($icon['url'], '/');
                }
                $lAttrs = RenderUtils::generate_attrs_string([
                    "href" => RenderUtils::esc_url($icon['url'] ?? ''),
                    "target" => $icon['linkTarget'] ?? false,
                    "rel" => $icon['linkRel'] ?? false,
                    "data-wptb-link-enable-convert-relative" => $icon['convertToAbsolute'] ?? false,
                ]);
            }
            
            $icons .= <<<HTML
            <{$lTag} {$lAttrs} class="wptb-icon-link-target-{$i}">
                <div
                    class="wptb-icon wptb-icon-{$i}"
                    style="width: {$size}; height: {$size}; fill: {$color}"
                    data-wptb-icon-src="{$iconSrc}"
                >
                    {$iconStr}
                </div>
            </{$lTag}>
            HTML;
        }

        $starIcon = RenderUtils::get_icon('star');

        for (; $i <= 5; $i++) {
            
            $icons .= <<<HTML
            <span class="wptb-icon-link-target-{$i}">
                <div
                    class="wptb-icon wptb-icon-{$i}"
                    style="width: {$size}; height: {$size}; fill: rgb(0, 0, 0); display: none;"
                    data-wptb-icon-src="star"
                >
                    {$starIcon}
                </div>
            </span>
            HTML;
        }

        $element_id = self::$element_id++;
        $align = esc_attr($props['alignment'] ?? 'center');
        
        return <<<HTML
        <div class="wptb-icon-container wptb-ph-element wptb-element-icon-{$element_id}" data-wptb-icon-number="{$iconCount}" style="{$style}">
            <div class="wptb-icon-wrapper" style="text-align: {$align}">
                {$icons}
            </div>
        </div>
        HTML;

    }
}

