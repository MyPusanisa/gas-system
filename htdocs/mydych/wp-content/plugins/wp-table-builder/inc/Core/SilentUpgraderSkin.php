<?php

namespace WPTableBuilder\Core;

use WP_Upgrader_Skin;

/**
 * Silent Upgrader Skin for REST API operations.
 *
 * Suppresses all output during plugin installation/upgrade operations.
 */
class SilentUpgraderSkin extends WP_Upgrader_Skin
{
    public function header() {}
    public function footer() {}
    public function before() {}
    public function after() {}
    public function feedback($feedback, ...$args) {}
    public function error($errors) {}
}
