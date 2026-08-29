<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class StarRating
{
    private static $element_id = 1;

    private static function get_rating_star($size, $val, $color)
    {
        $class = 'wptb-rating-star';
        if ($val == 1) {
            $class .= ' wptb-rating-star-selected-full';
        } else if ($val == 0.5) {
            $class .= ' wptb-rating-star-selected-half';
        }
    
        
        return <<<HTML
        <li style="width: {$size}; height: {$size}" data-value="3" class="{$class}">
            <span class="wptb-rating-star-left-signal-part"></span>
            <span class="wptb-filled-rating-star">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 426.667 426.667" style="enable-background: new 0 0 426.667 426.667; fill: {$color};" xml:space="preserve">
                    <polygon points="426.667,165.12 273.28,152.107 213.333,10.667 153.387,152.107 0,165.12 116.48,266.027 81.493,416 213.333,336.427 345.173,416 310.187,266.027 "/>
                </svg>
            </span>
            <span class="wptb-not-filled-rating-star">
                <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 426.667 426.667" style="enable-background: new 0 0 426.667 426.667; fill: {$color};" xml:space="preserve">
                    <path d="M426.667,165.12L273.28,151.893L213.333,10.667l-59.947,141.44L0,165.12l116.48,100.907L81.493,416l131.84-79.573L345.173,416L310.4,266.027L426.667,165.12z M213.333,296.533L133.12,344.96l21.333-91.307l-70.827-61.44l93.44-8.107l36.267-85.973l36.48,86.187l93.44,8.107l-70.827,61.44l21.333,91.307L213.333,296.533z"/>
                </svg>
            </span>
            <span class="wptb-half-filled-rating-star">
                <svg
                    version="1.1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:xlink="http://www.w3.org/1999/xlink"
                    x="0px"
                    y="0px"
                    viewBox="0 0 426.667 426.667"
                    style="enable-background: new 0 0 426.667 426.667; fill: {$color};"
                    xml:space="preserve"
                >
                    <path d="M426.667,165.12L273.28,151.893L213.333,10.667l-59.947,141.44L0,165.12l116.48,100.907L81.493,416l131.84-79.573L345.173,416L310.4,266.027L426.667,165.12z M213.333,296.533v-198.4l36.48,86.187l93.44,8.107l-70.827,61.44l21.333,91.307L213.333,296.533z"/>
                </svg>
            </span>
            <span class="wptb-rating-star-right-signal-part"></span>
        </li>
        HTML;
    }


    public static function render($block)
    {

        $props = $block['props'];

        $style = RenderUtils::generate_css_string([
            'text-align' => $props['alignment'] ?? 'center',
            'padding' => $props['padding'] ?? '',
            'margin' => $props['margin'] ?? '',
        ]);

        $fontSize = $props['fontSize'] ?? '15px';
        $ratingStyle = RenderUtils::generate_css_string([
            'font-size' => $fontSize,
            'line-height' => $fontSize,
            'height' => $fontSize,
            'color' => $props['color'] ?? '',
        ]);

        $total = (int) $props['starCount'] ?? 0;
        $value = (float) $props['value'] ?? 0;

        $ratingDisplay = esc_attr($props['showRating'] ?? false ? 'block' : 'none');

        $stars = '';

        $starColor = esc_attr($props['starColor'] ?? '#000000');
        $starSize = esc_attr($props['starSize'] ?? '20px');

        $rem = $value;

        for ($i = 0; $i < $total; $i++) {
            $stars .= self::get_rating_star($starSize, $rem > 1 ? 1 : $rem, $starColor);
            $rem--;
        }

        $element_id = self::$element_id++;
    
        return <<<HTML
        <div class="wptb-star_rating-container wptb-ph-element wptb-element-star_rating-{$element_id}" data-star-count="{$total}" style="{$style}">
            <div class="wptb-rating-stars-box">
                <ul class="wptb-rating-stars-list">
                    {$stars}
                </ul>
                <div class="wptb-number-rating-box" style="display: {$ratingDisplay}">
                    <div class="wptb-number-rating" style="{$ratingStyle}">{$value}/{$total}</div>
                </div>
            </div>
        </div>
        HTML;
    }

}

