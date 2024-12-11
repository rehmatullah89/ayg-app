<?php

namespace App\Tablet\Errors;

/**
 * Class ApiAuthError
 * @package \App\Tablet\Errors
 */
class InvalidSessionError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_015';
    /**
     * error message
     */
    //const MESSAGE = 'Other Application Error';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}