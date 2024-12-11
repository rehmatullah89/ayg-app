<?php
namespace App\Tablet\Helpers;

/**
 * Class CommonFunctionsHelper
 * @package App\Tablet\Helpers
 */
class CommonFunctionsHelper
{
    /**
     * @param $string
     * @return bool
     */
    public static function emptyZeroAllowed($string)
    {
        if (
            empty($string) &&
            (
                (is_string($string) && $string != "0")
                ||
                (is_int($string) && $string != 0)
            )
        ) {

            return true;
        }

        return false;
    }

    /**
     * @param $f
     * @return bool
     */
    public static function isFloatValue($f)
    {
        return ($f == (string)(float)$f);
    }

    /**
     * @param $integer
     * @return bool
     */
    public static function convertToBoolFromInt($integer)
    {
        $boolConversion = (intval($integer) === 1);

        return $boolConversion;
    }

}