<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeMaxUsageForCurrentDeviceReachedException;


/**
 * Class PromoCodeMaxUsageForCurrentDeviceReachedError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeMaxUsageForCurrentDeviceReachedException
 */
class PromoCodeMaxUsageForCurrentDeviceReachedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_475';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, your account is not eligible for this offer.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}