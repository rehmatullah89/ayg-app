<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\LoginFailureAppAccessException;

/**
 * Class LoginFailureAppAccessError
 * @package App\Tablet\Errors
 *
 * @see LoginFailureAppAccessException
 */
class LoginFailureAppAccessError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_027';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}