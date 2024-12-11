<?php
namespace App\Consumer\Helpers;

use App\Consumer\Entities\Airport;

/**
 * Class PhoneNumberHelper
 * @package App\Consumer\Helpers
 */
class PhoneNumberHelper
{
    /**
     * @param $phoneNumberFormatted
     * @return mixed
     */
    public static function removeNonDigitsAndNonPlus($phoneNumberFormatted)
    {
        return preg_replace("/[^0-9\+]/", '', $phoneNumberFormatted);
    }

    /**
     * @param $number - full number like "18587805512"
     * @param $nationalFormat - formatted number without country code like "(858) 780-5512"
     * @return string - country code like "1"
     */
    public static function getCountryCodeFromNumberAndCountryFormat($number, $nationalFormat)
    {
        // remove all non digits from national format
        $nationalFormat = preg_replace("/[^0-9]/", '', $nationalFormat);

        // remove all non digits from country number
        $number = preg_replace("/[^0-9]/", '', $number);

        // remove national format from number
        return str_replace($nationalFormat, '', $number);
    }
}
