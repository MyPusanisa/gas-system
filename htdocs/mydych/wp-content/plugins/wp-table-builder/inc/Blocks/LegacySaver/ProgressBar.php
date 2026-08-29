<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class ProgressBar
{
    private static $element_id = 1;

    public static function render($block)
    {
        $props = $block['props'];

        $style = RenderUtils::generate_css_string([
            'margin' => $props['margin'] ?? '',
            'padding' => $props['padding'] ?? '',
        ]);

        $value = (float) $props['value'];
        $rest = 100 - $value;

        $thickness = esc_attr($props['thickness'] ?? '5');

        $primaryColor = esc_attr($props['primaryColor'] ?? '#3C87B1');
        $secondaryColor = esc_attr($props['secondaryColor'] ?? '#CCCCCC');
        $labelColor = esc_attr($props['labelColor'] ?? 'rgb(60, 135, 177)');

        $element_id = self::$element_id++;
        return <<<HTML
        <div class="wptb-progress_bar-container wptb-ph-element wptb-ondragenter wptb-element-progress_bar-{$element_id}" style="{$style}">
            <div class="wptb-progress-bar-wrapper">
                <svg class="wptb-progress-bar" viewBox="0 0 100 10" preserveAspectRatio="none">
                    <path d="M 0,5 L 100,5" class="wptb-progress-bar-trail" stroke="{$secondaryColor}" style="stroke-width: {$thickness}"></path>
                    <path d="M 0,5 L 100,5" class="wptb-progress-bar-path" stroke="{$primaryColor}" style="stroke-dashoffset: {$rest}px; stroke-width: {$thickness}"></path>
                </svg>
                <div class="wptb-progress-bar-label" style="width: {$value}%; color: {$labelColor}">
                    {$value}%
                </div>
            </div>
        </div>
        HTML;

    }
}

