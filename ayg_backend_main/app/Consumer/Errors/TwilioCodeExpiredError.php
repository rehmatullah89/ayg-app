<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\TwilioCodeExpiredException;

/**
 * Class TwilioCodeExpiredError
 * @package App\Consumer\Errors
 *
 * @see TwilioCodeExpiredException
 */
class TwilioCodeExpiredError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_424';

    /**
     * error message
     */
    const MESSAGE = 'Provided code is not valid or has expired.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}
