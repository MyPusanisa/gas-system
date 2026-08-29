<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class CircleRating
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'];

        $style = RenderUtils::generate_css_string([
            'margin' => $props['margin'] ?? '',
            'padding' => $props['padding'] ?? '',
        ]);

        $size = esc_attr($props['size'] ?? '100px');
        $value = (float) $props['value'] ?? '37';
        $unit = ($props['ratingType'] ?? null) === 'number' ? '' : '%';
        $color = esc_attr($props['color'] ?? 'rgb(48, 123, 187)');
        $ratingType = esc_attr($props['ratingType'] ?? 'percentage');
        
        if ($unit === '%') {
            $total = 100;
        } else {
            $total = (float) $props['total'] ?? '100';
            if ($total < 1) {
                $total = 1;
            }
        }

        $angle = ($value / $total) * 360;

        if ($angle > 180) {
            $barAngle = 180;
            $clip = "rect(auto, auto, auto, auto)";
        } else {
            $barAngle = 0;
            $clip = "rect(0em, 1em, 1em, 0.5em)";
        }

        $element_id = self::$element_id++;
        return <<<HTML
        <div
            class="wptb-circle_rating-container wptb-ph-element wptb-element-circle_rating-{$element_id}"
            data-percentage-count="{$value}"
            data-wptb-rating-number="{$value}"
            data-wptb-total-number="{$total}"
            data-wptb-rating-type="{$ratingType}"
            style="{$style}"
        >
            <div class="wptb-rating-circle-wrapper" style="font-size: {$size}">
                <span style="color: {$color}">{$value}{$unit}</span>
                <div class="wptb-rating-circle-slice" style="clip: {$clip}">
                    <div class="wptb-rating-circle-bar" style="border-color: {$color}; transform: rotate({$barAngle}deg)"></div>
                    <div class="wptb-rating-circle-fill" style="border-color: {$color}; transform: rotate({$angle}deg);"></div>
                </div>
            </div>
        </div>
            <div class="wptb-rating-circle-wrapper" style="font-size: {$size}">
                <span style="color: {$color}">{$value}{$unit}</span>
                <div class="wptb-rating-circle-slice" style="clip: {$clip}">
                    <div class="wptb-rating-circle-bar" style="border-color: {$color}; transform: rotate({$barAngle}deg)"></div>
                    <div class="wptb-rating-circle-fill" style="border-color: {$color}; transform: rotate({$angle}deg);"></div>
                </div>
            </div>
        </div>
        HTML;
    }
}

