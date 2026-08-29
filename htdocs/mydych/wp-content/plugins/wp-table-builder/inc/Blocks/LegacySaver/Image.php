<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Image
{
    private static $element_id = 1;

    public static function render($block)
    {
        $attrs = $block['props'];
        $wrapperAttrs = RenderUtils::generate_attrs_string([
            "data-wptb-image-alignment" => $attrs['alignment'],
            "data-wptb-image-size-relative" => $attrs['sizeRelativeTo'],
        ]);
        $style = RenderUtils::generate_css_string([
            "padding" => $attrs['padding'] ?? '',
            "margin" => $attrs['margin'] ?? '',
        ]);

        $imgAttrs = RenderUtils::generate_attrs_string([
            "data-wptb-size" => $attrs['size'] ?? false,
            "height" => $attrs['imgHeight'] ?? false,
            "width" => $attrs['imgWidth'] ?? false,
            "src" => $attrs['src'] ?? false,
            "alt" => $attrs['alt'] ?? false,
        ]);

        $lStyle = RenderUtils::generate_css_string([
            "float" => $attrs['alignment'] === "center" ? "none" : $attrs['alignment'],
            "width" => $attrs['width'] ?? "50%",
        ]);

        $lTag = 'span';
        $lAttrs = "";

        if (isset($attrs['url']) && $attrs['url'] !== '') {
            $lTag = 'a';
            if ($attrs['convertToAbsolute'] && !preg_match('/^https?:\/\//', $attrs['url'])) {
                $attrs['url'] = 'https://' . ltrim($attrs['url'], '/');
            }
            $lAttrs = RenderUtils::generate_attrs_string([
                "href" => RenderUtils::esc_url($attrs['url'] ?? ''),
                "target" => $attrs['linkTarget'] ?? false,
                "rel" => $attrs['linkRel'] ?? false,
                "data-wptb-link-enable-convert-relative" => $attrs['convertToAbsolute'] ?? false,
            ]);
        }

        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-image-container wptb-ph-element wptb-element-image-{$element_id}" {$wrapperAttrs} style="{$style}">
            <div class="wptb-image-wrapper">
                <{$lTag} {$lAttrs} class="wptb-link-target" style="{$lStyle}">
                    <img class="wptb-image-element-target" {$imgAttrs} style="width: 100%" />
                </{$lTag}>
            </div>
        </div>
        HTML;
    }
}

