<?php
namespace App\Consumer\Helpers;

use App\Consumer\Exceptions\ConfigKeyNotFoundException;

/**
 * Class CacheHelper
 * @package App\Consumer\Helpers
 *
 * Config helper is a class that has static function to operate on config variables,
 */
class ConfigHelper
{
    /**
     * @param string $key
     * @return array|false|string
     * @throws ConfigKeyNotFoundException
     */
    public static function get($key)
    {
        // look in env
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        // some keys are hardcoded as globals, need to move them to env data
        if (isset($GLOBALS[$key])) {
            return $GLOBALS[$key];
        }

        throw new ConfigKeyNotFoundException('Env value not found, key:' . $key);
    }
}
