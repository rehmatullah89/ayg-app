<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeMaxUsageForAllUsersReachedException;

/**
 * Class PromoCodeMaxUsageForAllUsersReachedError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeMaxUsageForAllUsersReachedException
 */
class PromoCodeMaxUsageForAllUsersReachedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_474';

    /**
     * error message
     */
    const MESSAGE = 'This offer is no longer available.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}