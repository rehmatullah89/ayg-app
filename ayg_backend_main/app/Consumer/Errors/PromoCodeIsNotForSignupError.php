<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsNotForSignupException;


/**
 * Class PromoCodeIsNotForSignupError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsNotForSignupException
 */
class PromoCodeIsNotForSignupError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_468';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, this offer can be applied only on checkout';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}