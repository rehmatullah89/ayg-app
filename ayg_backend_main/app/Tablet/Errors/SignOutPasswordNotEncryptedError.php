<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\SignOutPasswordNotEncryptedException;

/**
 * Class SignOutPasswordNotEncryptedError
 * @package App\Tablet\Errors
 *
 * @see SignOutPasswordNotEncryptedException
 */
class SignOutPasswordNotEncryptedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_464';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}