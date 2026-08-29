<?php

namespace WPTableBuilder;

use WPTableBuilder\Admin\Api\ApiHandler;
use WPTableBuilder\Blocks\Gutenberg;
use WPTableBuilder\Core\Cpt;
use WPTableBuilder\Utils\Assets;
use WPTableBuilder\Core\Shortcode;
use WPTableBuilder\Core\Settings;
use WPTableBuilder\Core\VersionControl;
use WPTableBuilderPro\WPTableBuilderPro;

class WPTableBuilder
{

    public static function init()
    {
        wptb_fs();
        do_action('wptb_fs_loaded');
        Assets::init();
        Gutenberg::init();
        Cpt::init();
        Shortcode::init();
    }

    public static function admin_init()
    {
        self::add_menu();
        VersionControl::check_and_sync_versions();
    }

    public static function is_pro()
    {
        return class_exists(WPTableBuilderPro::class) && WPTableBuilderPro::is_active();
    }


    public static function add_menu()
    {
        if (!Settings::user_has_role()) {
            return;
        }

        add_menu_page(
            'WP Table Builder',
            'WP Table Builder',
            'read',
            'wptb',
            [self::class, 'wptb_page'],
            'dashicons-editor-table',
            50
        );

        add_submenu_page(
            'wptb',
            __('WP Table Builder', 'wp-table-builder'),
            __('All Tables', 'wp-table-builder'),
            'read',
            'wptb',
            [self::class, 'wptb_page']
        );

        add_submenu_page(
            'wptb',
            __('WP Table Builder', 'wp-table-builder'),
            __('Add New', 'wp-table-builder'),
            'read',
            'wptb-create',
            [self::class, 'wptb_page']
        );

        add_submenu_page(
            '_',
            __('WP Table Builder', 'wp-table-builder'),
            __('Builder', 'wp-table-builder'),
            'read',
            'wptb-builder',
            [self::class, 'wptb_page']
        );

        add_submenu_page(
            'wptb',
            __('WP Table Builder', 'wp-table-builder'),
            __('Import', 'wp-table-builder'),
            'read',
            'wptb-import',
            [self::class, 'wptb_page']
        );


        add_submenu_page(
            'wptb',
            __('WP Table Builder', 'wp-table-builder'),
            __('Export', 'wp-table-builder'),
            'read',
            'wptb-export',
            [self::class, 'wptb_page']
        );


        add_submenu_page(
            'wptb',
            __('WP Table Builder', 'wp-table-builder'),
            __('Settings', 'wp-table-builder'),
            'manage_options',
            'wptb-settings',
            [self::class, 'wptb_page']
        );

        add_submenu_page(
            '',
            __('WP Table Builder', 'wp-table-builder'),
            __('Welcome', 'wp-table-builder'),
            'read',
            'wptb-welcome',
            [self::class, 'wptb_page']
        );
    }


    public static function add_admin_bar_menu($wp_admin_bar)
    {
        if (!Settings::user_has_role()) {
            return;
        }

        $wp_admin_bar->add_menu([
            'id' => 'wptb-add-new',
            'title' => __('Add New Table', 'wp-table-builder'),
            'href' => admin_url('admin.php?page=wptb-create'),
            'parent' => 'new-content',
        ]);
    }

    public static function wptb_page()
    {
        echo '<div id="wptb-app-root" class="wptb-app-root"></div>';
        Assets::enqueue();
    }
}
