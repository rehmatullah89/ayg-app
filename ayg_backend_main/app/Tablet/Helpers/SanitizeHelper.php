<?php
namespace App\Tablet\Helpers;


/**
 * Class SanitizeHelper
 * @package App\Tablet\Helpers
 */
class SanitizeHelper
{
    /**
     * @param $value
     * @return string
     */
    public static function sanitizeEmail($value)
    {
        return sanitizeEmail($value);
    }

    /**
     * Sanitize user provided value
     *
     * @param  string $value Value to be cleansed
     *
     * @return string Cleansed string
     */
    public static function sanitize($value)
    {
        return sanitize($value);
    }

    /**
     * @param $input
     * @return string
     */
    public static function sanitizeArray(array $input)
    {
        return sanitize_array($input);
    }
}