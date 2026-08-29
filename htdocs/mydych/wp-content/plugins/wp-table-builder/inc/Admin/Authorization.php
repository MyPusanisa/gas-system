<?php

namespace WPTableBuilder\Admin;

use WPTableBuilder\Core\Settings;

class Authorization
{
    public static function can_edit()
    {
        return Settings::is_user_allowed();
    }

    public static function can_view()
    {
        return Settings::is_user_allowed() || current_user_can('edit_posts');
    }
}