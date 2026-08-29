<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Cell
{
    public static function render($cell)
    {
        $props = $cell['props'];
        $borderCss = [];
        $radiusCss = [];

        if (isset($props['border']) && $props['border'] !== '') {
            $borderCss = [
                'border' => $props['border'],
            ];
        } else {
            $borderCss = [
                'border-width' => $props['borderWidth'] ?? '',
                'border-color' => $props['borderColor'] ?? '',
                'border-style' => $props['borderStyle'] ?? '',
            ];
        }

        if (isset($props['borderRadius']) && $props['borderRadius'] !== '') {
            $radiusCss = [
                'border-radius' => $props['borderRadius'],
            ];
        } else {
            $radiusCss = [
                'border-top-left-radius' => $props['borderTopLeftRadius'] ?? '',
                'border-top-right-radius' => $props['borderTopRightRadius'] ?? '',
                'border-bottom-right-radius' => $props['borderBottomRightRadius'] ?? '',
                'border-bottom-left-radius' => $props['borderBottomLeftRadius'] ?? '',
            ];
        }

        $styles = RenderUtils::generate_css_string([
            "padding" => $props['padding'] ?? "",
            "height" => $props['height'] ?? "",
            "width" => $props['width'] ?? "",
            "background-color" => $props['background'] ?? '',
        ] + $borderCss + $radiusCss);

        $attrs = RenderUtils::generate_attrs_string([

            "colspan" => $props['colspan'] ?? false,
            "rowspan" => $props['rowspan'] ?? false,

            "style" => $styles,

            "data-y-index" => $props['yIndex'] ?? false,
            "data-x-index" => $props['xIndex'] ?? false,
            "data-sorted-vertical" => $props['ySort'] ?? false,
            "data-sorted-horizontal" => $props['xSort'] ?? false,
            "data-wptb-css-td-auto-width" => $props['autoWidth'] ?? false,
            "data-wptb-css-td-auto-height" => $props['autoHeight'] ?? false,
            "data-wptb-cell-vertical-alignment" => $props['vAlign'] ?? false,
            "data-wptb-own-bg-color" => $props['ownBgColor'] ?? false,
        ]);

        $classNames = isset($props['highlighted']) ? 'wptb-col-highlighted-' . esc_attr($props['highlighted']) . ' wptb-highlighted ' : '';
        $blocks = "";

        $isFirst = true;

        if ($props['hideOnMobile'] ?? false) {
            $classNames .= 'wptb-hide-on-mobile';
        }

        if ($props['isEmpty'] ?? false) {
            $classNames .= ' wptb-empty';
        } else {
            foreach ($cell['blocks'] as $block) {

                switch ($block['type']) {
                    case 'text':
                        $block['props']['isFirst'] = $isFirst;
                        $isFirst = false;
                        $blocks .= Text::render($block);
                        break;
                    case 'button':
                        $blocks .= Button::render($block);
                        break;
                    case 'image':
                        $blocks .= Image::render($block);
                        break;
                    case 'list':
                        $blocks .= ListElement::render($block);
                        break;
                    case 'starRating':
                        $blocks .= StarRating::render($block);
                        break;
                    case 'customHtml':
                        $blocks .= CustomHtml::render($block);
                        break;
                    case 'shortcode':
                        $blocks .= Shortcode::render($block);
                        break;

                    case 'circleRating':
                        $blocks .= CircleRating::render($block);
                        break;
                    case 'icon':
                        $blocks .= Icon::render($block);
                        break;
                    case 'ribbon':
                        $blocks .= Ribbon::render($block);
                        break;
                    case 'styledList':
                        $blocks .= StyledList::render($block);
                        break;
                    case 'textIcon':
                        $blocks .= TextIcon::render($block);
                        break;
                    case 'progressBar':
                        $blocks .= ProgressBar::render($block);
                        break;
                    case 'badge':
                        $blocks .= Badge::render($block);
                        break;
                }
            }
        }

        return "<td class=\"wptb-cell {$classNames}\" {$attrs}>{$blocks}</td>";
    }
}