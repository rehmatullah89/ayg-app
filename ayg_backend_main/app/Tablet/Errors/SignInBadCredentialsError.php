<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\SignInBadCredentialsException;

/**
 * Class SignInBadCredentialsError
 * @package App\Tablet\Errors
 *
 * @see SignInBadCredentialsException
 */
class SignInBadCredentialsError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_020';

    /**
     * error message
     */
    const MESSAGE = 'Your username or password is incorrect. Please try again.';
}