<?php

namespace WPTableBuilder\Admin\Api;

use WPTableBuilder\Admin\Api\ApiHandler;
use WPTableBuilder\Core\Settings;
use WPTableBuilder\Core\VersionControl;

class SettingsApi
{
    const OPTION_NAME = 'wptb_settings';

    public static function register($apiBase)
    {
        register_rest_route($apiBase, '/settings', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_settings'],
            'permission_callback' => [self::class, 'check_manage_options'],
        ]);

        register_rest_route($apiBase, '/settings', [
            'methods' => 'POST',
            'callback' => [self::class, 'update_settings'],
            'permission_callback' => [self::class, 'check_manage_options'],
        ]);

        register_rest_route($apiBase, '/settings/install-version', [
            'methods' => 'POST',
            'callback' => [self::class, 'install_version'],
            'permission_callback' => [self::class, 'check_manage_options'],
        ]);
    }

    public static function check_manage_options()
    {
        return current_user_can('manage_options');
    }

    public static function get_settings($request)
    {
        $settings = Settings::get_all();
        return ApiHandler::response($settings);
    }

    public static function update_settings($request)
    {
        $updates = $request->get_json_params();

        if (empty($updates)) {
            return ApiHandler::response([
                'error' => __('No settings provided.', 'wp-table-builder')
            ], 400);
        }

        $current_settings = Settings::get_all();
        $new_settings = $current_settings;

        if (isset($updates['general'])) {
            $new_settings['general'] = array_merge(
                $current_settings['general'],
                self::sanitize_general_settings($updates['general'])
            );
        }

        if (isset($updates['global_styles'])) {
            $new_settings['global_styles'] = wp_strip_all_tags($updates['global_styles']);
        }

        if (isset($updates['lazy_load'])) {
            $new_settings['lazy_load'] = array_merge(
                $current_settings['lazy_load'],
                self::sanitize_lazy_load_settings($updates['lazy_load'])
            );
        }

        update_option(self::OPTION_NAME, $new_settings);
        Settings::clear_cache();

        return ApiHandler::response([
            'message' => __('Settings saved successfully.', 'wp-table-builder'),
            'settings' => $new_settings
        ]);
    }

    private static function sanitize_general_settings($settings)
    {
        $sanitized = [];

        if (isset($settings['allowed_roles']) && is_array($settings['allowed_roles'])) {
            $valid_roles = array_keys(Settings::get_editable_roles());
            $sanitized['allowed_roles'] = array_values(array_intersect($settings['allowed_roles'], $valid_roles));
        }

        $boolean_fields = [
            'display_edit_link_frontend',
            'display_credits',
            'restrict_users_to_their_tables',
            'disable_emoji_image_conversion',
            'disable_theme_styles',
            'take_over_entire_screen',
            'enable_version_sync'
        ];

        $enum_fields = [
            'sidebar_position' => [
                'options' => ['left', 'right'],
                'default' => 'left',
            ],
        ];

        foreach ($enum_fields as $field => $config) {
            $default = $config['default'];
            $options = $config['options'];
            $value = $settings[$field] ?? $default;
            $sanitized[$field] = in_array($value, $options) ? $value : $default;
        }

        foreach ($boolean_fields as $field) {
            if (isset($settings[$field])) {
                $sanitized[$field] = (bool) $settings[$field];
            }
        }

        return $sanitized;
    }

    private static function sanitize_lazy_load_settings($settings)
    {
        $sanitized = [];

        if (isset($settings['enabled'])) {
            $sanitized['enabled'] = (bool) $settings['enabled'];
        }

        if (isset($settings['visibilityPercentage'])) {
            $sanitized['visibilityPercentage'] = sanitize_text_field($settings['visibilityPercentage']);
        }

        $color_fields = ['backgroundColor', 'iconColor', 'flashColor'];
        foreach ($color_fields as $field) {
            if (isset($settings[$field])) {
                $color = sanitize_hex_color($settings[$field]);
                $sanitized[$field] = $color !== null ? $color : '';
            }
        }

        if (isset($settings['iconName'])) {
            $sanitized['iconName'] = $settings['iconName'];
        }

        if (isset($settings['iconSize'])) {
            $sanitized['iconSize'] = sanitize_text_field($settings['iconSize']);
        }

        if (isset($settings['iconAnimation'])) {
            $valid_icon_animations = ['none', 'heartbeat', 'rotate', 'flip', 'jump'];
            $icon_animation = sanitize_text_field($settings['iconAnimation']);
            $sanitized['iconAnimation'] = in_array($icon_animation, $valid_icon_animations) ? $icon_animation : 'none';
        }

        if (isset($settings['imageLoadAnimation'])) {
            $valid_image_animations = ['none', 'slide-in', 'grow-sling', 'flash', 'flip'];
            $image_animation = sanitize_text_field($settings['imageLoadAnimation']);
            $sanitized['imageLoadAnimation'] = in_array($image_animation, $valid_image_animations) ? $image_animation : 'none';
        }

        if (isset($settings['imageLoadAnimationDirection'])) {
            $valid_directions = ['left', 'right', 'top', 'bottom'];
            $direction = sanitize_text_field($settings['imageLoadAnimationDirection']);
            $sanitized['imageLoadAnimationDirection'] = in_array($direction, $valid_directions) ? $direction : 'left';
        }

        if (isset($settings['imageLoadAnimationSpeed'])) {
            $sanitized['imageLoadAnimationSpeed'] = sanitize_text_field($settings['imageLoadAnimationSpeed']);
        }

        if (isset($settings['imageLoadAnimationPerspective'])) {
            $sanitized['imageLoadAnimationPerspective'] = sanitize_text_field($settings['imageLoadAnimationPerspective']);
        }

        return $sanitized;
    }

    public static function get_all_settings()
    {
        return Settings::get_all();
    }

    public static function install_version($request)
    {
        $params = $request->get_json_params();
        $version = isset($params['version']) ? sanitize_text_field($params['version']) : '';

        $result = VersionControl::install_version($version);

        if (is_wp_error($result)) {
            $status_code = in_array($result->get_error_code(), ['version_required', 'invalid_version_format', 'no_version_info', 'version_not_available']) ? 400 : 500;
            return ApiHandler::response([
                'error' => $result->get_error_message()
            ], $status_code);
        }

        return ApiHandler::response($result);
    }

    public static function reset_all_settings()
    {
        Settings::reset();
    }
}
