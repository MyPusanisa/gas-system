<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://wptablebuilder.com/
 * @since             1.0.0
 * @package           WP_Table_Builder
 *
 * @wordpress-plugin
 * Plugin Name:       WP Table Builder
 * Plugin URI:        https://wptablebuilder.com/
 * Description:       Drag and Drop Responsive Table Builder Plugin for WordPress.
 * Version:           2.1.11
 * Author:            WP Table Builder
 * Author URI:        https://wptablebuilder.com//
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       wp-table-builder
 * Domain Path:       /languages
 */

define('WPTB_PLUGIN_DIR', __DIR__);
define('WPTB_PLUGIN_URL', rtrim(plugin_dir_url(__FILE__), '/'));
define('WPTB_PLUGIN_FILE', __FILE__);
define('WPTB_VERSION', '2.1.11');

require_once __DIR__ . '/vendor/autoload.php';

if (!function_exists('wptb_fs')) {
    function wptb_fs()
    {
        global $wptb_fs;

        if (!isset($wptb_fs)) {
            $wptb_fs = fs_dynamic_init([
                'id' => '6602',
                'slug' => 'wp-table-builder',
                'type' => 'plugin',
                'public_key' => 'pk_6bf7fb67d8b8bcce83459fd46432e',
                'is_premium' => false,
                'has_addons' => true,
                'has_paid_plans' => false,
                'is_org_compliant' => false,
                'menu' => [
                    'slug' => 'wptb',
                    'first-path' => 'admin.php?page=wptb-welcome',
                    'account' => true,
                    'contact' => false,
                    'support' => false,
                ],
            ]);
        }

        return $wptb_fs;
    }
}


\WPTableBuilder\Admin\Api\ApiHandler::init();

add_action('init', [
    \WPTableBuilder\WPTableBuilder::class,
    'init'
]);

add_action('admin_menu', [
    \WPTableBuilder\WPTableBuilder::class,
    'admin_init'
]);


add_action('admin_bar_menu', [\WPTableBuilder\WPTableBuilder::class, 'add_admin_bar_menu'], 100);
