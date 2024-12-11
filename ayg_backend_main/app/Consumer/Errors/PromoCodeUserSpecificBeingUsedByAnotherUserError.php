<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeUserSpecificBeingUsedByAnotherUserException;


/**
 * Class PromoCodeUserSpecificBeingUsedByAnotherUserError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeUserSpecificBeingUsedByAnotherUserException
 */
class PromoCodeUserSpecificBeingUsedByAnotherUserError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_477';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, your account is not eligible for this offer.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}