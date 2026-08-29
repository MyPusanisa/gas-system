<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Ribbon
{
    private static $element_id = 1;

    private static function render_rect($data)
    {
        extract($data);
        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-ribbon_element-container wptb-ph-element wptb-element-ribbon_element-{$element_id}" style="{$style}" {$attrs}>
            <div id="wptbRibbonMainWrap" class="wptb-element-ribbon-wrapper" style="">
                <div id="wptbRibbonTextWrap" class="wptb-element-ribbon-inner wptb-ribbon-type-rectangle-text-wrap wptb-plugin-box-shadow-md" style="background-color: {$bgColor}; border-color: {$borderColor}">
                    <p style="width: auto; font-size: {$fontSize}; position: relative" class="wptb-element-ribbon-text">
                        {$text}
                    </p>
                </div>
                <div class="wptb-element-ribbon-color-dump" style="background-color: {$bgColor}; border-color: {$borderColor}"></div>
                <div id="wptbRibbonIconDump" style="display: none" data-wptb-ribbon-icon-animation-type="beat" data-wptb-ribbon-icon-src="star">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                        <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        HTML;
    }


    private static function render_bookmark($data)
    {
        extract($data);
        $width = esc_attr($props['width'] ?? '70');
        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-ribbon_element-container wptb-ph-element wptb-element-ribbon_element-{$element_id}" style="{$style}" {$attrs}>
            <div id="wptbRibbonMainWrap" class="wptb-element-ribbon-wrapper wptb-ribbon-bookmark-main-wrap wptb-plugin-filter-box-shadow-md" style="width: {$width}px">
                <div id="wptbRibbonTextWrap" class="wptb-element-ribbon-inner" style="background-color: {$bgColor}">
                    <p style="width: auto; font-size: {$fontSize}; position: relative" class="wptb-element-ribbon-text">
                        {$text}
                    </p>
                </div>
                <div class="wptb-element-ribbon-color-dump" style="background-color: {$bgColor}; border-color: {$borderColor}"></div>
                <div id="wptbRibbonIconDump" style="display: none" data-wptb-ribbon-icon-animation-type="beat" data-wptb-ribbon-icon-src="star">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                        <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path>
                    </svg>
                </div>
                <div id="wptbBookmarkRibbonElementEnd" style="color: {$bgColor}; width: {$width}px">
                    <svg preserveAspectRatio="none" viewBox="0 0 64 40" fill="none" xmlns="http://www.w3.org/2000/svg" class="">
                        <path class="wptb-ribbon-bookmark-main-path" d="M0 40V0H64V38.5714L32.5 19.6429L0 40Z" fill="currentColor"></path>
                    </svg>
                </div>
            </div>
        </div>
        HTML;
    }


    private static function render_corner($data)
    {
        extract($data);

        if (($props['side'] ?? '') === 'right') {
            $sideStyle = 'rotateZ(45deg) translateY(-11.5px)';
        } else {
            $sideStyle = 'rotateZ(-45deg) translateY(-11.5px)';
        }

        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-ribbon_element-container wptb-ph-element wptb-element-ribbon_element-{$element_id}" style="{$style}" {$attrs}>
            '<div id="wptbRibbonMainWrap" class="wptb-element-ribbon-wrapper wptb-ribbon-corner-main-wrap" style="">' .
                '<div id="wptbRibbonTextWrap" class="wptb-element-ribbon-inner wptb-plugin-filter-box-shadow-md-close" style="background-color: {$bgColor};border-color: {$borderColor};transform: {$sideStyle}">
                    <p style="width: 200px;font-size: 15px;position: relative;text-align: center;" class="wptb-element-ribbon-text">
                        {$text}
                    </p>
                </div>
                <div class="wptb-element-ribbon-color-dump" style="background-color: {$bgColor}; border-color: {$borderColor}"></div>
                <div id="wptbRibbonIconDump" style="display: none" data-wptb-ribbon-icon-animation-type="beat" data-wptb-ribbon-icon-src="star">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                        <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        HTML;
    }


    private static function render_icon($data)
    {
        extract($data);
        $iconSrc = esc_attr($props['icon'] ?? 'star');
        $icon = RenderUtils::get_icon($iconSrc);

        $icAttrs = RenderUtils::generate_attrs_string([
            "data-wptb-ribbon-icon-animation-type" => $props['animationType'] ?? false,
            "data-enable-animation" => $props['enableAnimation'] ?? false,
        ]);

        $sideClass = '';
        if (($props['side'] ?? '') === 'right') {
            $sideClass = ' flip';
        }

        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-ribbon_element-container wptb-ph-element wptb-element-ribbon_element-{$element_id}" style="{$style}" {$attrs}>
            <div id="wptbRibbonMainWrap" class="wptb-element-ribbon-wrapper wptb-ribbon-icon01-main-wrap{$sideClass}">
                <div id="wptbRibbonTextWrap" class="wptb-element-ribbon-inner wptb-ribbon-icon01-text-wrap wptb-plugin-filter-box-shadow-md">
                    <p style="width: auto;font-size: {$fontSize};position: relative;background-color: {$bgColor};" class="wptb-element-ribbon-text">
                        {$text}
                    </p>
                    <div class="wptb-ribbon-icon01-triangle-end" style="border-right: 20px solid transparent;border-bottom: 88px solid {$bgColor};"></div>
                </div>
                <div class="wptb-element-ribbon-color-dump" style="background-color: {$bgColor}; border-color: rgb(0, 0, 0)"></div>
                <div
                    id="wptbRibbonIconDump"
                    style="display: block; background-color: {$bgColor}"
                    class="wptb-plugin-filter-box-shadow-md wptb-ribbon-icon01-icon-wrapper"
                    data-wptb-ribbon-icon-src="{$iconSrc}"
                    {$icAttrs}
                >
                    {$icon}
                </div>
            </div>
        </div>
        HTML;
    }


    private static function render_side($data)
    {
        extract($data);
        $sideClass = '';
        if (($props['side'] ?? '') === 'right') {
            $sideClass = ' flip';
        }
    
        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-ribbon_element-container wptb-ph-element wptb-element-ribbon_element-{$element_id} wptb-ribbon-side-fix" style="{$style}" {$attrs}>
            <div id="wptbRibbonMainWrap" class="wptb-element-ribbon-wrapper wptb-ribbon-sideFancy-main-wrap{$sideClass}" style="">
                <div id="wptbRibbonTextWrap" class="wptb-element-ribbon-inner wptb-ribbon-sideFancy-text-wrap wptb-plugin-filter-box-shadow-md" style="background-color: {$bgColor}; border-color: {$borderColor}">
                    <p style="width: auto; font-size: 15px; position: relative" class="wptb-element-ribbon-text">
                        {$text}
                    </p>
                </div>
                <div class="wptb-element-ribbon-color-dump" style="background-color: {$bgColor}; border-color: {$borderColor}"></div>
                <div id="wptbRibbonIconDump" style="display: none" data-wptb-ribbon-icon-animation-type="beat" data-wptb-ribbon-icon-src="star" class="" data-enable-animation="1">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                        <path d="M259.3 17.8L194 150.2 47.9 171.5c-26.2 3.8-36.7 36.1-17.7 54.6l105.7 103-25 145.5c-4.5 26.3 23.2 46 46.4 33.7L288 439.6l130.7 68.7c23.2 12.2 50.9-7.4 46.4-33.7l-25-145.5 105.7-103c19-18.5 8.5-50.8-17.7-54.6L382 150.2 316.7 17.8c-11.7-23.6-45.6-23.9-57.4 0z"></path>
                    </svg>
                </div>
                <div class="wptb-ribbon-side-fancy-triangle-wrapper">
                    <div class="wptb-ribbon-side-fancy-triangle-end" style="border-right: 20px solid transparent;border-bottom: 20px solid {$bgColor};"></div>
                    <div class="wptb-ribbon-side-fancy-triangle-overlay" style="border-right: 20px solid transparent;border-bottom: 20px solid rgba(0, 0, 0, 0.4);"></div>
                </div>
            </div>
        </div>
        HTML;
    }


    public static function render($block)
    {
        $props = $block['props'];
        $type = $props['type'] ?? false;

        $attrs = RenderUtils::generate_attrs_string([
            "data-wptb-ribbon-type" => $type,
            "data-wptb-ribbon-modifications" => $props['modifications'] ?? false,
            "data-wptb-ribbon-side" => $props['side'] ?? false,
            "data-wptb-ribbon-width" => $props['width'] ?? false,
            "data-wptb-ribbon-x-offset" => $props['xOffset'] ?? false,
            "data-wptb-ribbon-y-offset" => $props['yOffset'] ?? false,
        ]);

        $style = esc_attr($props['style'] ?? '');

        $bgColor = esc_attr($props['background'] ?? '');
        $borderColor = esc_attr($props['borderColor'] ?? '');

        $fontSize = esc_attr($props['fontSize'] ?? '15px');

        $text = wp_kses_post($props['text'] ?? '');

        $data = compact('attrs', 'style', 'bgColor', 'borderColor', 'text', 'props', 'fontSize');

        switch ($type) {
            case 'iconRibbon':
                return self::render_icon($data);
            case 'rectangleRibbon':
                return self::render_rect($data);
            case 'bookmarkRibbon':
                return self::render_bookmark($data);
            case 'cornerRibbon':
                return self::render_corner($data);
            case 'sideRibbon':
                return self::render_side($data);
        }

        return '';
    }
}

