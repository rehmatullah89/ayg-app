<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\ReferralCodeIsNotValidException;


/**
 * Class ReferralCodeIsNotValidError
 * @package Consumer\App\Errors
 *
 * @see ReferralCodeIsNotValidException
 */
class ReferralCodeIsNotValidError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_477';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, this is code is not valid.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}