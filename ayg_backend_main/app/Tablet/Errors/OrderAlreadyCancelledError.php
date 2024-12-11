<?php

namespace App\Tablet\Errors;

/**
 * Class OrderAlreadyCancelledError
 * @package App\Tablet\Errors
 */
class OrderAlreadyCancelledError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5309';

    /**
     * error message
     */
    //const MESSAGE = 'Order Not found';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}