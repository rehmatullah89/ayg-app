<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsOnlyForSignupException;


/**
 * Class PromoCodeIsOnlyForSignupError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsOnlyForSignupException
 */
class PromoCodeIsOnlyForSignupError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_472';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, this offer is only available for new users.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}