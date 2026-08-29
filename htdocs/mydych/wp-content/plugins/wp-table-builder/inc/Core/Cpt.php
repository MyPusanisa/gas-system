<?php

namespace WPTableBuilder\Core;

use WPTableBuilder\Utils\AssetsFrontend;

class Cpt
{

    const TAX_ID = 'table_tags';
    const POST_TYPE = 'wptb-tables';

    public static function init()
    {
        self::register_cpt();
    }

    public static function register_cpt()
    {
        $args = [
            'label' => 'WPTB Tables',
            'public' => true,
            'show_ui' => false,
            'show_in_admin_bar' => false,
            'rewrite' => false,
            'query_var' => true,
            'can_export' => false,
            'supports' => ['title', 'custom-fields'],
            'show_in_rest' => true,
            'publicly_queryable' => true
        ];

        register_post_type(self::POST_TYPE, $args);

        register_post_meta(self::POST_TYPE, '_wptb_content_', [
            'show_in_rest' => true
        ]);

        register_post_meta(self::POST_TYPE, '_wptb_prebuilt_', [
            'show_in_rest' => true
        ]);

        $args = [
            'labels' => [
                'name' => _x('Table Tags', 'table tag name', 'wp-table-builder'),
                'singular_name' => _x('Table Tag', 'singular table tag taxonomy name', 'wp-table-builder'),
                'all_items' => __('All Table Tags', 'wp-table-builder'),
                'edit_item' => __('Edit Table Tag', 'wp-table-builder'),
                'add_new_item' => __('Add New Table Tag', 'wp-table-builder'),
            ],
            'description' => 'tags for wp table builder tables',
            'show_in_menu' => false,
            'show_in_ui' => false,
            'show_in_rest' => true
        ];

        register_taxonomy(self::TAX_ID, self::POST_TYPE, $args);

        add_action('template_redirect', [self::class, 'handle_preview']);
    }

    public static function handle_preview()
    {
        if (!isset($_GET['post_type']) || $_GET['post_type'] !== self::POST_TYPE) {
            return;
        }

        if (!isset($_GET['p'])) {
            return;
        }

        if (!Settings::user_has_role()) {
            wp_die('You are not allowed to preview this table.', 'Access Denied', ['response' => 403]);
            return;
        }
        
        AssetsFrontend::enqueue();
        add_filter('the_content', [self::class, 'filter_preview_content']);

    }



    public static function filter_preview_content($content)
    {

        $table_id = intval($_GET['p']);
        $table = get_post($table_id);
        if (!$table || $table->post_type !== self::POST_TYPE) {
            wp_die('Table not found', 'Table Not Found', ['response' => 404]);
        }
        
        $post_preview_id = get_post_meta($table_id, '_wptb_preview_id_', true);
        $preview_id = $post_preview_id;

        if (isset($_GET['preview_id'])) {
            $preview_id = $_GET['preview_id'];
            $wptb_prebuilt = get_post_meta($table_id, '_wptb_content_preview_', true);
        } else {
            $wptb_prebuilt = get_post_meta($table_id, '_wptb_content_', true);
        }

        if ($post_preview_id !== $preview_id || empty($wptb_prebuilt)) {
            return <<<HTML
            <div class="wptb-skeleton-table wptb-skeleton-table-preview-page">
                <table>
                    <thead>
                        <tr>
                            <th class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></th>
                            <th class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></th>
                            <th class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></th>
                            <th class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                        </tr>
                        <tr>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                        </tr>
                        <tr>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                        </tr>
                        <tr>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                        </tr>
                        <tr>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                            <td class="wptb-skeleton-cell"><div class="wptb-skeleton-line"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <script>
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            </script>
            HTML;
        }
        

        $wptb_prebuilt = self::process_shortcodes($wptb_prebuilt);

        return <<<HTML
        <div class="wptb-container-legacy" data-table-id="{$table_id}">
            {$wptb_prebuilt}
        </div>
        <script>
            document.currentScript.parentElement.classList.remove('alignfull')
        </script>
        HTML;
    }


    public static function process_shortcodes($html, $args = [])
    {
        $has_wptb_shortcodes = preg_match_all('|<wptb_shortcode_container_element(.+)</wptb_shortcode_container_element>|isU', $html, $arr);
        if (!$has_wptb_shortcodes) {
            return $html;
        }
        foreach ($arr[1] as $value) {
            if (isset($args['internal_shortcodes_stop']) || !$value) {
                continue;
            }
            $pattern = get_shortcode_regex();

            $has_shortcodes = preg_match_all('/' . $pattern . '/s', $value, $matches);

            if (!$has_shortcodes) {
                continue;
            }

            for ($i = 0; $i < count($matches[0]); $i++) {
                $shortcode = $matches[0][$i];
                if (isset($matches[2][$i]) && $matches[2][$i] == 'wptb') {

                    $shortcode = str_replace(']', ' internal_shortcodes_stop="1"]', $matches[0][$i]);

                    $div_outer_html_new = str_replace($matches[0][$i], $shortcode, $value);

                    $html = str_replace($value, $div_outer_html_new, $html);

                    $html = str_replace($div_outer_html_new, do_shortcode($div_outer_html_new), $html);
                } else {
                    $html = str_replace($value, do_shortcode($value), $html);
                }
            }

        }
        return $html;
    }
}