<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeNotFoundException;


/**
 * Class PromoCodeNotFoundError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeNotFoundException
 */

class PromoCodeNotFoundError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_476';

    /**
     * error message
     */
    const MESSAGE = 'This promo code is not valid.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}