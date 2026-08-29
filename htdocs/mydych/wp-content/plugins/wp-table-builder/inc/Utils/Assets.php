<?php

namespace WPTableBuilder\Utils;
use WPTableBuilder\Core\Settings;
use WPTableBuilder\WPTableBuilder;

class Assets
{

    private const CDN_HOST = WPTB_PLUGIN_URL;


    public static function init()
    {
        add_action('enqueue_block_editor_assets', function () {
            self::enqueue(true);
        });

        add_action('admin_print_footer_scripts', function () {
            self::enqueue_config();
            echo AssetLoader::get_scripts();
        });
    }


    public static function enqueue($is_block_editor = false)
    {
        wp_enqueue_media();

        self::enqueue_i18n();

        $assets = AssetLoader::backend(self::CDN_HOST, WPTB_PLUGIN_DIR . '/dist/vite/manifest.json', WPTB_PLUGIN_DIR . '/tmp/.hotfile');

        $assets->register('src/editor-common.ts');
        $assets->register_style('wptb-editor-style', 'src/editor.scss');
        $assets->register_style('wptb-frontend-style', 'src/styles.scss');

        do_action('wptb_enqueue_pro_assets');

        if ($is_block_editor) {
            $assets->register('src/frontend/common.ts');
            $assets->register_path('build/index.js');
            $assets->register_style_path('wptb-gutenberg-style', 'build/editor.css');
        } else {
            $assets->register('src/index.tsx');
            AssetLoader::enqueue_styles();
        }
    }

    private static function enqueue_config()
    {
        $data = [
            'API_BASE' => rest_url('wp-table-builder'),
            'PLUGIN_URL' => WPTB_PLUGIN_URL,
            'HOME_URL' => home_url(),
            'IS_PRO' => WPTableBuilder::is_pro(),
            'TEST' => __("Create Table", "wptb"),
            'SETTINGS' => self::get_settings_config(),
            'NONCE' => [
                'wp_rest' => wp_create_nonce('wp_rest'),
            ],
        ];

        echo '<script type="text/javascript">var WPTB_CFG = ' . json_encode($data) . ';</script>';
    }

    private static function get_settings_config()
    {
        return [
            'all_roles' => Settings::get_editable_roles(),
            'is_authorized' => Settings::is_user_allowed(),
            'version' => WPTB_VERSION,
            'general' => Settings::get_general(),
            'table_style' => Settings::get_styles(),
            'lazy_load' => Settings::get_lazy_load(),
        ];
    }

    private static function enqueue_i18n()
    {
        load_plugin_textdomain('wp-table-builder', false, 'wp-table-builder/languages');
        wp_register_script(
            'wptb-i18n',
            self::CDN_HOST . '/dist/wptb-i18n.js',
            ['wp-i18n']
        );

        wp_set_script_translations('wptb-i18n', 'wp-table-builder', WPTB_PLUGIN_DIR . '/languages');
        add_action('admin_enqueue_scripts', function () {
            // wp_enqueue_script('wptb-i18n');
        });
    }

}