<?php

namespace WPTableBuilder\Core;


class Settings
{
    const OPTION_NAME = 'wptb_settings';

    private static $cache = null;
    private static $is_loaded = false;

    private static $user_has_role = null;

    public static function get_all()
    {
        if (!self::$is_loaded) {
            self::$cache = get_option(self::OPTION_NAME, [
                'general' => [
                    'allowed_roles' => [],
                    'display_edit_link_frontend' => false,
                    'display_credits' => false,
                    'restrict_users_to_their_tables' => false,
                    'disable_emoji_image_conversion' => false,
                    'disable_theme_styles' => false,
                    'sidebar_position' => 'left',
                    'take_over_entire_screen' => false,
                    'enable_version_sync' => false,
                ],
                'global_styles' => '',
                'lazy_load' => [
                    'enabled' => false,
                    'visibilityPercentage' => '50',
                    'backgroundColor' => '',
                    'iconName' => null,
                    'iconColor' => '',
                    'iconSize' => '32',
                    'iconAnimation' => 'none',
                    'imageLoadAnimation' => 'none',
                    'imageLoadAnimationDirection' => 'left',
                    'imageLoadAnimationSpeed' => '5',
                    'imageLoadAnimationPerspective' => '400',
                    'flashColor' => ''
                ],
            ]);
            self::$is_loaded = true;
        }

        return self::$cache;
    }

    public static function get($key, $default = null)
    {
        $settings = self::get_all();
        return $settings[$key] ?? $default;
    }

    public static function get_general($key = null, $default = null)
    {
        $general = self::get('general', []);

        if ($key === null) {
            return $general;
        }

        return $general[$key] ?? $default;
    }

    public static function get_styles()
    {
        return self::get('global_styles', '');
    }

    public static function get_lazy_load($key = null, $default = null)
    {
        $lazy_load = self::get('lazy_load', []);

        if ($key === null) {
            return $lazy_load;
        }

        return $lazy_load[$key] ?? $default;
    }

    public static function is_lazy_load_enabled()
    {
        return self::get_lazy_load('enabled', false);
    }

    public static function get_allowed_roles()
    {
        return self::get_general('allowed_roles', []);
    }

    public static function user_has_role()
    {
        if (self::$user_has_role === null) {
            $allowed_roles = self::get_allowed_roles();
            $allowed_roles[] = 'administrator';
            $user = wp_get_current_user();
            $user_roles = $user->roles ?? [];
            self::$user_has_role = !empty(array_intersect($user_roles, $allowed_roles));
        }
        return self::$user_has_role;
    }

    public static function is_user_allowed($id = 0)
    {
        if (current_user_can('manage_options')) {
            return true;
        }

        if (!self::user_has_role()) {
            return false;
        }

        if (!$id || !self::should_restrict_users_to_own_tables()) {
            return true;
        }

        $post = get_post($id);
        if (!$post || $post->post_type !== Cpt::POST_TYPE) {
            return false;
        }

        return $post->post_author === get_current_user_id();
    }

    public static function should_display_edit_link_frontend()
    {
        return (bool) self::get_general('display_edit_link_frontend', false);
    }

    public static function should_display_credits()
    {
        return (bool) self::get_general('display_credits', false);
    }

    public static function should_restrict_users_to_own_tables()
    {
        return (bool) self::get_general('restrict_users_to_their_tables', false);
    }

    public static function is_emoji_conversion_disabled()
    {
        return (bool) self::get_general('disable_emoji_image_conversion', false);
    }

    public static function is_theme_styles_disabled()
    {
        return (bool) self::get_general('disable_theme_styles', false);
    }

    public static function is_fullscreen_mode()
    {
        return (bool) self::get_general('take_over_entire_screen', false);
    }

    public static function is_version_sync_enabled()
    {
        return (bool) self::get_general('enable_version_sync', false);
    }

    public static function clear_cache()
    {
        self::$cache = null;
        self::$is_loaded = false;
    }

    public static function reset()
    {
        delete_option(self::OPTION_NAME);
        self::clear_cache();
        self::$is_loaded = false;
        self::$cache = null;
    }

    public static function get_editable_roles()
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        return get_editable_roles() ?? [];
    }
}
