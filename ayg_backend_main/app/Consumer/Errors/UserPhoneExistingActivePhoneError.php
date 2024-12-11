<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserPhoneExistingActivePhoneException;

/**
 * Class UserPhoneExistingActivePhoneError
 * @package Consumer\App\Errors
 *
 * @see UserPhoneExistingActivePhoneException
 */
class UserPhoneExistingActivePhoneError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_452';

    /**
     * error message
     */
    const MESSAGE = "Something went wrong. We are working on fixing the problem.";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}