<?php

namespace App\Tablet\Errors;

/**
 * Class TabletUserRetailerConnectionNotFoundError
 * @package App\Tablet\Errors
 */
class TabletUserRetailerConnectionNotFoundError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5300';

    /**
     * error message
     */
    //const MESSAGE = 'This user is not connected to any retailer';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}