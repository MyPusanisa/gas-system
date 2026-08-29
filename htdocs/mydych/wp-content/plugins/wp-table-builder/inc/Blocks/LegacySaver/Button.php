<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Button
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'] ?? [];

        $cStyle = RenderUtils::generate_css_string([
            'padding' => $props['padding'] ?? '',
            'margin' => $props['margin'] ?? '',
        ]);

        $btnStyle = RenderUtils::generate_css_string([
            "border-radius" => $props['borderRadius'] ?? '',
            "background-color" => $props['background'] ?? '',
            "color" => $props['color'] ?? '',
            "justify-content" => $props['contentAlignment'] ?? '',
            "transform" => $props[''] ?? '',
        ]);

        $hoverAttrs = RenderUtils::generate_attrs_string([
            "data-wptb-element-hover-bg-color" => $props['hoverBg'] ?? false,
            "data-wptb-element-hover-text-color" => $props['hoverColor'] ?? false,
            "data-wptb-element-hover-scale" => $props['hoverScale'] ?? false,
        ]);

        $btnAttrs = RenderUtils::generate_attrs_string([
            "data-wptb-element-bg-color" => $props['background'] ?? false,
            "data-wptb-element-color" => $props['color'] ?? false,
        ]) . $hoverAttrs;

        $labelBg = esc_attr($props['labelBg'] ?? '#ffffff');

        $labelStyle = RenderUtils::generate_css_string([
            "display" => $props['hasLabel'] ?? false ? 'inline-flex' : 'none',
            "background-color" => $labelBg,
            "color" => $props['labelColor'] ?? '',
        ]);

        $pStyle = RenderUtils::generate_css_string([
            'font-size' => esc_attr($props['fontSize'] ?? '15px')
        ]);

        switch ($props['contentAlignment']) {
            case 'flex-start':
                $pStyle .= 'margin-right: auto !important;';
                break;
            case 'flex-end':
                $pStyle .= 'margin-left: auto !important;';
                break;
            default:
                $pStyle .= 'margin-inline: auto !important;';
                break;

        }


        $lTag = 'span';
        $lAttrs = RenderUtils::generate_attrs_string([
            "id" => $props['id'] ?? false,
            "style" => isset($props['width']) ? 'width: ' . $props['width'] . ';' : false,
        ]);

        if (isset($props['url']) && $props['url'] !== '') {
            $lTag = 'a';
            if ($props['convertToAbsolute'] && !preg_match('/^https?:\/\//', $props['url'])) {
                $props['url'] = 'https://' . ltrim($props['url'], '/');
            }
            $lAttrs .= RenderUtils::generate_attrs_string([
                "href" => RenderUtils::esc_url($props['url'] ?? ''),
                "target" => $props['linkTarget'] ?? false,
                "rel" => $props['linkRel'] ?? false,
                "data-wptb-link-enable-convert-relative" => $props['convertToAbsolute'] ?? false,
            ]);
        }

        $btnOrder = esc_attr($props['iconPosition'] ?? 'left');
        $wrapperClass = 'wptb-size-' . esc_attr($props['size']);
        if ($props['hasLabel'] ?? false) {
            $wrapperClass .= ' wptb-button-has-label';
        }
        $btnAlignment = esc_attr($props['buttonAlignment'] ?? 'center');

        $text = wp_kses_post($props['text']  ?? '');
        $labelText = wp_kses_post($props['labelText']  ?? '');

        $iconSrc = esc_attr($props['icon'] ?? '');
        $icon = RenderUtils::get_icon($iconSrc);
        $iconSize = esc_attr($props['iconSize'] ?? '25px');
        
        $element_id = self::$element_id++;
        //@formatter:off
        return <<<HTML
        <div class="wptb-button-container wptb-ph-element wptb-element-button-{$element_id} edit-active" style="{$cStyle}">
            <div class="wptb-button-wrapper {$wrapperClass}" style="justify-content: {$btnAlignment}">
                <{$lTag} class="wptb-link-target" {$lAttrs}>
                    <div class="wptb-button wptb-plugin-button-order-{$btnOrder}" style="position: relative;{$btnStyle}" {$btnAttrs}>
                        <p style="{$pStyle}">{$text}</p>
                        <div class="wptb-button-icon" data-wptb-button-icon-src="{$iconSrc}" {$hoverAttrs} style="width: {$iconSize}; height: {$iconSize}">
                            {$icon}
                        </div>
                        <div class="wptb-button-label" style="{$labelStyle}" {$hoverAttrs}>
                            <div class="wptb-button-label-decorator" {$hoverAttrs} style="border-color: rgba(0, 0, 0, 0) {$labelBg} rgba(0, 0, 0, 0) rgba(0, 0, 0, 0);">
                                <br />
                            </div>
                            <div class="wptb-button-label-text" {$hoverAttrs}>
                                {$labelText}
                            </div>
                        </div>
                    </div>
                </{$lTag}>
            </div>
        </div>;
        HTML;
        //@formatter:on
    }
}

