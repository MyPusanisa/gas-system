<?php

namespace WPTableBuilder\Blocks\LegacySaver;

use WPTableBuilder\Utils\RenderUtils;

class Table
{
    public static function render ($body, $id) {
        $props = $body['props'];

        $borderCss = [];

        if (isset($props['tableBorder']) && $props['tableBorder'] !== '') {
            $borderCss = [
                'border' => $props['tableBorder'],
            ];
        } else {
            $borderCss = [
                'border-width' => $props['borderWidth'] ?? '',
                'border-color' => $props['borderColor'] ?? '',
                'border-style' => $props['borderStyle'] ?? '',
            ];
        }

        $tblStyle = RenderUtils::generate_css_string([
            "border-spacing" => "{$props['tableSpacingX']}px {$props['tableSpacingY']}px",
            "border-collapse" => $props['borderCollapse'] ?? '',
            "min-width" => $props['minWidth'] ?? '',
            "padding" => $props['padding'] ?? '',
        ] + $borderCss);

        $enableMaxWidth = $props['enableMaxWidth'] ?? false;

        
        $attrs_string = RenderUtils::generate_attrs_string([

            "class" => "wptb-preview-table wptb-element-main-table_setting-" . $id,
            "style" => $tblStyle,

            "data-border-spacing-columns" => $props['tableSpacingX'] ?? false,
            "data-border-spacing-rows" => $props['tableSpacingY'] ?? false,

            "data-reconstraction" => "1",
            "data-wptb-table-directives" => $props['directives'] ?? false,
            "data-wptb-responsive-directives" => $props['responsiveDirectives'] ?? false,
            "data-wptb-cells-width-auto-count" => $props['cellsWidthAutoCount'] ?? false,

            "data-wptb-sortable-table-vertical" => $props['sortVertical'] ?? false,
            "data-wptb-sortable-table-horizontal" => $props['sortHorizontal'] ?? false,


            "data-wptb-apply-table-container-max-width" => $enableMaxWidth,
            "data-wptb-table-container-max-width" => $enableMaxWidth ? $props['maxWidth'] ?? false : false,

            "data-wptb-horizontal-scroll-status" => $props['scrollX'] ?? false,
            "data-wptb-extra-styles" => $props['extraStyles'] ?? false,
            "data-wptb-first-column-sticky" => $props['stickyFirstColumn'] ?? false,

            "data-wptb-pagination-enable" => $props['paginationEnable'] ?? false,
            "data-wptb-pro-pagination-top-row-header" => $props['paginationTopRowAsHeader'] ?? false,
            "data-wptb-rows-per-page" => $props['rowsPerPage'] ?? false,
            "data-wptb-rows-changeable" => $props['rowsChangeable'] ?? false,

            "data-wptb-pro-search-top-row-header" => $props['searchKeepHeader'] ?? false,
            "data-wptb-searchbar-position" => $props['searchPosition'] ?? false,
            "role" => $props['role'] ?? false,
            "data-table-columns" => $props['cols'] ?? false,
            "data-wptb-table-alignment" => $props['alignment'] ?? false,
            "data-wptb-td-width-auto" => $props['cellMinWidth'] ?? false,
            "data-wptb-table-tds-sum-max-width" => $props['tdSumMaxWidth'] ?? false,
            "data-disable-theme-styles" => $props['disableThemeStyles'] ?? false,
            "data-wptb-search-enable" => $props['searchEnable'] ?? false,



            "data-wptb-header-background-color" => $props['headerBg'] ?? false,
            "data-wptb-even-row-background-color" => $props['evenRowBg'] ?? false,
            "data-wptb-odd-row-background-color" => $props['oddRowBg'] ?? false,
            "data-wptb-header-hover-background-color" => $props['hoverHeaderBg'] ?? false,
            "data-wptb-even-row-hover-background-color" => $props['hoverEvenRowBg'] ?? false,
            "data-wptb-odd-row-hover-background-color" => $props['hoverOddRowBg'] ?? false,

            "data-v2-props" => $props['v2Props'] ?? false,
        ]);

        $tbody_attrs = RenderUtils::generate_attrs_string([
            "data-global-font-color" => $props['fontColor'] ?? false,
            "data-global-link-color" => $props['linkColor'] ?? false,
            "data-global-font-size" => $props['fontSize'] ?? false,
        ]);

        $tbody = "";

        foreach ($body['rows'] as $i => $row) {
            $cells = "";
            foreach ($row['cells'] as $cell) {
                $cells .= Cell::render($cell);
            }
            $classNames = isset($row['props']['highlighted']) ? 'wptb-row-highlighted-' . esc_attr($row['props']['highlighted']) : '';
            $attrs = "";
            if ($props['stickyTopRow'] ?? false && $i == 0) {
                $attrs = 'data-wptb-sticky-row="true"';
            }
            $hoverColor = '';
            if ($i === 0 && isset($props['hoverHeaderBg']) && $props['hoverHeaderBg'] !== '') {
                $hoverColor = $props['hoverHeaderBg'];
            } elseif ($i % 2 === 0) {
                if (isset($props['hoverOddRowBg']) && $props['hoverOddRowBg'] !== '') {
                    $hoverColor = $props['hoverOddRowBg'];
                }
            } elseif (isset($props['hoverEvenRowBg']) && $props['hoverEvenRowBg'] !== '') {
                $hoverColor = $props['hoverEvenRowBg'];
            }

            if ($hoverColor !== '') {
                $classNames .= ' wptb-row-has-hover';
            }

            $style = RenderUtils::generate_css_string([
                'background-color' => $row['props']['background'] ?? '',
                '--hover-bg-color' => $hoverColor,
            ]);
            $tbody .= '<tr ' . $attrs . ' class="wptb-row ' . $classNames . '" style="' . $style . '">' . $cells . '</tr>';
        }

        return "<table {$attrs_string}><tbody {$tbody_attrs}>{$tbody}</tbody></table>";
    }
}