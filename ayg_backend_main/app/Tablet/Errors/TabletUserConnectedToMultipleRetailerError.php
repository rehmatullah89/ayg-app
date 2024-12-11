<?php

namespace App\Tablet\Errors;

/**
 * Class TabletUserConnectedToMultipleRetailerError
 * @package App\Tablet\Errors
 */
class TabletUserConnectedToMultipleRetailerError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5304';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}