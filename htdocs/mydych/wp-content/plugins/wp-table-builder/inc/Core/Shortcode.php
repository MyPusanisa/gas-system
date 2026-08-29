<?php

namespace WPTableBuilder\Core;

use WPTableBuilder\Utils\AssetsFrontend;

class Shortcode
{
    public static function init()
    {
        add_shortcode('wptb', [self::class, 'render_table']);
    }

    public static function render_table($args)
    {

        $table_id = isset($args['id']) ? absint($args['id']) : 0;

        $html = get_post_meta($table_id, '_wptb_content_', true);


        if (!$table_id || empty($html)) {
            return '[wptb id="' . esc_attr($table_id) . '" not found ]';
        }

        AssetsFrontend::enqueue();

        $html = Cpt::process_shortcodes($html, $args);

        $post_edit_link = '';
        $post_give_credit = '';
        $after_table = '';

        if (current_user_can('manage_options') && Settings::should_display_edit_link_frontend()) {
            $post_edit_link = '<div class="wptb-frontend-table-edit-link">'
                . '<a href="' . admin_url('admin.php?page=wptb-builder&table=' . $table_id) . '">' . __("Edit Table", 'wp-table-builder') . '</a></div>';
        }

        if (Settings::should_display_credits()) {
            $post_give_credit .= '<div class="wptb-frontend-table-powered-by">'
                . '<small><i>Powered By </i></small>'
                . '<a href="https://wptablebuilder.com/" target="_blank">' . __("WP Table Builder", 'wp-table-builder')
                . '</a></div>';
        }

        if ($post_edit_link != '' || $post_give_credit != '') {
            $after_table = '<div class="wptb-frontend-table-after">' . $post_edit_link . $post_give_credit . '</div>';
        }

        $html = <<<HTML
        <div class="wptb-container-legacy" data-table-id="{$table_id}">
            {$html}
        </div>
        {$after_table}
        HTML;   

        // $html = apply_filters('wp-table-builder/filter/table_html_shortcode', $html);

        return $html;
    }
}