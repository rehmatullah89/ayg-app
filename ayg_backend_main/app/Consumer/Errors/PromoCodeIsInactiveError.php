<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsInactiveException;

/**
 * Class PromoCodeIsInactiveError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsInactiveException
 */
class PromoCodeIsInactiveError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_467';

    /**
     * error message
     */
    const MESSAGE = 'This offer is no longer available.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}