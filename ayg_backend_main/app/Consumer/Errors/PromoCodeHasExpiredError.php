<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeHasExpiredException;

/**
 * Class PromoCodeHasExpiredError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeHasExpiredException
 */
class PromoCodeHasExpiredError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_465';

    /**
     * error message
     */
    const MESSAGE = 'This offer is no longer available.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}