<?php

namespace WPTableBuilder\Core;

class VersionControl
{
    public static function install_version($version)
    {
        if (empty($version)) {
            return new \WP_Error(
                'version_required',
                __('Version is required.', 'wp-table-builder')
            );
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            return new \WP_Error(
                'invalid_version_format',
                __('Invalid version format.', 'wp-table-builder')
            );
        }

        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/misc.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php');

        $plugin_info = plugins_api('plugin_information', ['slug' => 'wp-table-builder']);

        if (is_wp_error($plugin_info)) {
            return new \WP_Error(
                'fetch_failed',
                __('Failed to fetch plugin information from WordPress.org.', 'wp-table-builder')
            );
        }

        if (!is_object($plugin_info) || !isset($plugin_info->versions)) {
            return new \WP_Error(
                'no_version_info',
                __('No version information available.', 'wp-table-builder')
            );
        }

        $versions = $plugin_info->versions;

        if (!isset($versions[$version])) {
            return new \WP_Error(
                'version_not_available',
                sprintf(
                    __('Version %s is not available for download.', 'wp-table-builder'),
                    $version
                )
            );
        }

        $pro_sync_result = null;
        try {
            if (Settings::is_version_sync_enabled()) {
                $pro_sync_result = self::sync_pro_version($version);
                if ($pro_sync_result !== null) {
                    if (is_wp_error($pro_sync_result)) {
                        $response_message .= ' ' . sprintf(
                            __('Pro addon sync failed: %s', 'wp-table-builder'),
                            $pro_sync_result->get_error_message()
                        );
                    } elseif ($pro_sync_result === false) {
                        $response_message .= ' ' . __('Pro addon sync failed: An error occurred during installation.', 'wp-table-builder');
                    } elseif ($pro_sync_result === true) {
                        $response_message .= ' ' . __('Pro addon synced successfully.', 'wp-table-builder');
                    }
                }
            }
        } catch (\Exception $e) {
            $response_message .= ' ' . sprintf(
                __('Pro addon sync failed: %s', 'wp-table-builder'),
                $e->getMessage()
            );
        } catch (\Error $e) {
            $response_message .= ' ' . sprintf(
                __('Pro addon sync failed: %s', 'wp-table-builder'),
                $e->getMessage()
            );
        }

        $download_url = $versions[$version];

        $base_result = self::install_plugin_version(
            $download_url,
            'wp-table-builder/wp-table-builder.php',
            $version
        );

        if (is_wp_error($base_result)) {
            return new \WP_Error(
                'install_failed',
                sprintf(
                    __('Failed to install version %s: %s', 'wp-table-builder'),
                    $version,
                    $base_result->get_error_message()
                )
            );
        }

        if ($base_result === false) {
            return new \WP_Error(
                'install_error',
                __('An error occurred during plugin installation, please refresh and try again.', 'wp-table-builder')
            );
        }

        $activation_result = activate_plugin('wp-table-builder/wp-table-builder.php');
        if (is_wp_error($activation_result)) {
            return new \WP_Error(
                'activation_failed',
                sprintf(
                    __('Version %s installed but failed to activate: %s', 'wp-table-builder'),
                    $version,
                    $activation_result->get_error_message()
                )
            );
        }

        $response_message = sprintf(
            __('Successfully installed version %s.', 'wp-table-builder'),
            $version
        );



        $response_message .= ' ' . __('Page will reload...', 'wp-table-builder');

        return [
            'message' => $response_message,
            'version' => $version,
            'pro_synced' => $pro_sync_result === true
        ];
    }

    public static function install_plugin_version($download_url, $plugin_file, $version)
    {

        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        require_once(ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php');

        $upgrader = new \Plugin_Upgrader(new SilentUpgraderSkin());

        add_filter('upgrader_package_options', function ($options) use ($plugin_file, $version) {
            $options['abort_if_destination_exists'] = false;
            $options['hook_extra'] = array_merge($options['hook_extra'] ?? [], [
                'plugin' => $plugin_file,
                'version' => $version
            ]);
            return $options;
        });

        return $upgrader->install($download_url, ['overwrite_package' => true]);

    }

    public static function sync_pro_version($target_version)
    {
        $wptb_pro = self::get_pro_freemius_instance();
        if (!$wptb_pro) {
            return null;
        }

        $current_pro_version = defined('WPTB_PRO_VERSION') ? WPTB_PRO_VERSION : null;
        if ($current_pro_version === $target_version) {
            return true;
        }


        $pro_versions = self::get_pro_versions($wptb_pro);
        if (is_wp_error($pro_versions)) {
            return $pro_versions;
        }

        if (!isset($pro_versions[$target_version])) {
            $available_version = self::find_compatible_pro_version($target_version, array_keys($pro_versions));
            if ($available_version === null) {
                return new \WP_Error(
                    'version_not_found',
                    sprintf(
                        __('Pro addon version %s is not available.', 'wp-table-builder'),
                        $target_version
                    )
                );
            }
            $target_version = $available_version;
        }

        if ($target_version === $current_pro_version) {
            return true;
        }

        $version_info = $pro_versions[$target_version];
        if (!isset($version_info['url'])) {
            $download_url = self::build_pro_download_url($wptb_pro, $version_info['id'] ?? $target_version);
        } else {
            $download_url = $version_info['url'];
        }

        return self::install_plugin_version(
            $download_url,
            'wp-table-builder-pro/wp-table-builder-pro.php',
            $target_version
        );
    }


    public static function check_and_sync_versions()
    {
        try {
            if (!Settings::is_version_sync_enabled()) {
                return null;
            }

            if (!defined('WPTB_VERSION')) {
                return null;
            }

            $base_version = WPTB_VERSION;
            $pro_version = defined('WPTB_PRO_VERSION') ? WPTB_PRO_VERSION : 'unknown';

            if ($base_version === $pro_version) {
                return true;
            }

            $transient_key = 'wptb_version_sync_check_' . $base_version . '_' . $pro_version;
            $last_check = get_transient($transient_key);

            if ($last_check !== false) {
                return null;
            }

            set_transient($transient_key, time(), 5 * 60);

            return self::sync_pro_version($base_version);
        } catch (\Exception $e) {
            error_log('WP Table Builder: Version sync check failed - ' . $e->getMessage());
            return null;
        } catch (\Error $e) {
            error_log('WP Table Builder: Version sync check failed - ' . $e->getMessage());
            return null;
        }
    }

    private static function get_pro_versions($wptb_pro)
    {
        $transient_key = 'wptb_pro_versions_info';
        $cached_versions = get_transient($transient_key);

        if ($cached_versions !== false && is_array($cached_versions)) {
            return $cached_versions;
        }

        $site = $wptb_pro->get_site();
        if (!$site) {
            return new \WP_Error('no_site', __('Freemius site not available.', 'wp-table-builder'));
        }

        if (!class_exists('FS_Api')) {
            return new \WP_Error('sdk_missing', __('Freemius SDK API class not available.', 'wp-table-builder'));
        }

        $api = \FS_Api::instance(
            $wptb_pro->get_slug(),
            'install',
            $site->id,
            $site->public_key,
            $wptb_pro->is_payments_sandbox(),
            $site->secret_key
        );

        $result = $api->call('updates?version=0.0.1', 'GET');

        if (is_object($result) && isset($result->error)) {
            $error_message = isset($result->error->message)
                ? $result->error->message
                : __('Failed to fetch pro versions from Freemius API.', 'wp-table-builder');
            return new \WP_Error('api_error', $error_message);
        }

        if (!is_object($result) || !isset($result->tags) || !is_array($result->tags)) {
            return new \WP_Error(
                'api_error',
                __('Failed to fetch pro versions from Freemius API.', 'wp-table-builder')
            );
        }

        $versions = [];
        foreach ($result->tags as $tag) {
            if (isset($tag->version)) {
                $versions[$tag->version] = (array) $tag;
            }
        }

        set_transient($transient_key, $versions, 10 * 60);

        return $versions;
    }

    private static function build_pro_download_url($wptb_pro, $version_id)
    {
        $site = $wptb_pro->get_site();
        if (!$site) {
            return null;
        }

        if (!class_exists('FS_Api')) {
            return null;
        }

        $api = \FS_Api::instance(
            $wptb_pro->get_slug(),
            'install',
            $site->id,
            $site->public_key,
            $wptb_pro->is_payments_sandbox(),
            $site->secret_key
        );

        return $api->get_signed_url('updates/' . $version_id . '.zip');
    }

    private static function find_compatible_pro_version($target_version, $available_versions)
    {
        if (in_array($target_version, $available_versions, true)) {
            return $target_version;
        }

        $compatible = null;
        foreach ($available_versions as $version) {
            if (version_compare($version, $target_version, '<=')) {
                if ($compatible === null || version_compare($version, $compatible, '>')) {
                    $compatible = $version;
                }
            }
        }

        return $compatible;
    }

    /**
     * Get the pro Freemius instance without depending on external wptb_pro() function.
     * Initializes Freemius directly using the same configuration.
     *
     * @return \Freemius|null The Freemius instance if available and premium code can be used, null otherwise.
     */
    private static function get_pro_freemius_instance()
    {
        if (!defined('WPTB_PRO_PLUGIN_FILE')) {
            return null;
        }

        // Ensure parent plugin's Freemius is initialized first
        if (!function_exists('wptb_fs')) {
            return null;
        }

        // Initialize parent Freemius instance
        wptb_fs();

        // Check if fs_dynamic_init function is available
        if (!function_exists('fs_dynamic_init')) {
            return null;
        }

        // Initialize pro Freemius instance directly
        $wptb_pro = fs_dynamic_init([
            'id' => '6984',
            'slug' => 'wp-table-builder-pro',
            'type' => 'plugin',
            'public_key' => 'pk_e1120fb37bd7382b676f21362759e',
            'is_premium' => true,
            'is_premium_only' => true,
            'has_paid_plans' => true,
            'is_org_compliant' => false,
            'parent' => [
                'id' => '6602',
                'slug' => 'wp-table-builder',
                'public_key' => 'pk_6bf7fb67d8b8bcce83459fd46432e',
                'name' => 'WP Table Builder',
                'file' => 'wp-table-builder/wp-table-builder.php',
            ],
            'menu' => [
                'first-path' => 'plugins.php',
                'account' => false,
                'support' => false,
            ],
        ]);

        if (!$wptb_pro || !$wptb_pro->can_use_premium_code()) {
            return null;
        }

        return $wptb_pro;
    }
}