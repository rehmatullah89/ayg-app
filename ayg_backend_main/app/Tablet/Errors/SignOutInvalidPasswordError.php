<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\SignOutInvalidPasswordException;

/**
 * Class SignOutInvalidPasswordError
 * @package App\Tablet\Errors
 *
 * @see SignOutInvalidPasswordException
 */
class SignOutInvalidPasswordError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_463';

    /**
     * error message
     */
    const MESSAGE = 'You must enter the valid password to logout.';
}