<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserPhoneVerifyException;

/**
 * @see UserPhoneVerifyException
 */
class UserPhoneVerifyError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_446';

    /**
     * error message
     */
    const MESSAGE = "Phone verification code failed";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}
