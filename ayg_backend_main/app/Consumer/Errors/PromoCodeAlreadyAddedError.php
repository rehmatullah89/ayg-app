<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeAlreadyAddedException;

/**
 * Class PromoCodeAlreadyAddedError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeAlreadyAddedException
 */
class PromoCodeAlreadyAddedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_486';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, you have already added this offer.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}