<?php

namespace App\Consumer\Errors;

/**
 * Class TwilioCodeNotValidError
 * @package App\Consumer\Errors
 */
class TwilioCodeNotValidError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_424';

    /**
     * error message
     */
    const MESSAGE = 'Provided code is not valid or expired';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}
