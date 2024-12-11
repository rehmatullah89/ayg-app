<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsNotValidException;

/**
 * Class PromoCodeIsNotValidError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsNotValidException
 */
class PromoCodeIsNotValidError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_469';

    /**
     * error message
     */
    const MESSAGE = 'This is not a valid code.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}