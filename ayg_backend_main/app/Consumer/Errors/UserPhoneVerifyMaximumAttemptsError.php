<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserPhoneVerifyMaximumAttemptsException;

/**
 * Class UserPhoneVerifyMaximumAttemptsError
 * @package Consumer\App\Errors
 *
 * @see UserPhoneVerifyMaximumAttemptsException
 */
class UserPhoneVerifyMaximumAttemptsError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_445';

    /**
     * error message
     */
    const MESSAGE = "You have reached maximum attempts allowed. Please try again in an hour.";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}