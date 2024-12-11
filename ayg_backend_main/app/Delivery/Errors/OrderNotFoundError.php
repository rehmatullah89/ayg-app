<?php

namespace App\Delivery\Errors;

/**
 * Class OrderNotFoundError
 * @package App\Delivery\Errors
 */
class OrderNotFoundError extends Error
{
    /**
     * error code
     */
    //const CODE = '1411';
    const CODE = 'AS_5302';

    /**
     * error message
     */
    //const MESSAGE = 'Order Not found';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}
