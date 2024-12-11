<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeMaxUsageForCurrentUserReachedException;


/**
 * Class PromoCodeMaxUsageForCurrentUserReachedError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeMaxUsageForCurrentUserReachedException
 */
class PromoCodeMaxUsageForCurrentUserReachedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_475';

    /**
     * error message
     */
    const MESSAGE = 'This offer can be used only once.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}