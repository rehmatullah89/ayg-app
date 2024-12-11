<?php
namespace App\Delivery\Helpers;


/**
 * Class EncryptionHelper
 * @package App\Consumer\Helpers
 */
class EncryptionHelper
{
    /**
     * @param $string
     * @return string
     */
    public static function decryptPaymentInfo($string)
    {
        return decryptPaymentInfo($string);
    }

    /**
     * @param $string
     * @return string
     */
    public static function encryptPaymentInfo($string)
    {
        return encryptPaymentInfo($string);
    }

    /**
     * @param $string
     * @param $key
     * @return string
     */
    public static function decryptString($string, $key)
    {
        return decryptString($string, $key);
    }

    /**
     * @param $string
     * @param $key
     * @return string
     */
    public static function encryptString($string, $key)
    {
        return encryptString($string, $key);
    }

    /**
     * @param $string
     * @return string
     */
    public static function decryptStringInMotion($string)
    {
        return decryptString($string, $GLOBALS['env_DeliveryStringInMotionEncryptionKey']);
    }

    /**
     * @param $string
     * @return string
     */
    public static function encryptStringInMotion($string)
    {
        return encryptString($string, $GLOBALS['env_DeliveryStringInMotionEncryptionKey']);
    }

    /**
     * @param string $input
     * @return array
     */
    public static function decodeDeviceArray($input)
    {
        return decodeDeviceArray($input);
    }

}
