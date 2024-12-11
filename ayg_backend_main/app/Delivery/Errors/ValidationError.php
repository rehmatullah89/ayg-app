<?php

namespace App\Delivery\Errors;

/**
 * Class ValidationError
 * @package Consumer\App\Errors
 */
class ValidationError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5399';

    /**
     * error message
     */
    //const MESSAGE = 'Validation Error';
    const MESSAGE = 'Something went wrong. We are are working on fixing the problem.';
}
