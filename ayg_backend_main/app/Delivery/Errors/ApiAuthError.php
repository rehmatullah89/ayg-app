<?php

namespace App\Delivery\Errors;

/**
 * Class ApiAuthError
 * @package \App\Delivery\Errors
 */
class ApiAuthError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_002';

    /**
     * error message
     */
    //const MESSAGE = 'Other Application Error';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}
