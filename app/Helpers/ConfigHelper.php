<?php

namespace App\Helpers;

use App\Models\SystemConfig;

class ConfigHelper
{
    /**
     * Get a system configuration value
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The configuration value
     */
    public static function get($key, $default = null)
    {
        return SystemConfig::get($key, $default);
    }

    /**
     * Set a system configuration value
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     * @param string $group The group this config belongs to
     * @return bool Success status
     */
    public static function set($key, $value, $group = 'general')
    {
        return SystemConfig::set($key, $value, $group);
    }

    /**
     * Get all configurations in a group
     *
     * @param string $group The group name
     * @return array Configurations in the specified group
     */
    public static function getGroup($group = 'general')
    {
        return SystemConfig::getGroup($group);
    }
}
