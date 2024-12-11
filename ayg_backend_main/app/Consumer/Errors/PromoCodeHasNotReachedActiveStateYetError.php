<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeHasNotReachedActiveStateYetException;

/**
 * Class PromoCodeHasNotReachedActiveStateYetError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeHasNotReachedActiveStateYetException
 */
class PromoCodeHasNotReachedActiveStateYetError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_466';

    /**
     * error message
     */
    const MESSAGE = 'This offer is not available.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}