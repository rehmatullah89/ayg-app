<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserPhoneDoesNotExistException;

/**
 * @see UserPhoneDoesNotExistException
 */
class UserPhoneDoesNotExistError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_424';

    /**
     * error message
     */
    const MESSAGE = "Invalid phone Id";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}
