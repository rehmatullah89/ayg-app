<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserWithSameEmailExistsException;

/**
 * @see UserWithSameEmailExistsException
 */
class UserWithSameEmailExistsError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_032';

    /**
     * error message
     */
    const MESSAGE = "Email address already in use.";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}
