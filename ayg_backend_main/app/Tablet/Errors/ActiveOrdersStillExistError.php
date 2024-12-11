<?php

namespace App\Tablet\Errors;

/**
 * Class ActiveOrdersStillExistError
 * @package App\Tablet\Errors
 */
class ActiveOrdersStillExistError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5305';

    /**
     * error message
     */
    const MESSAGE = 'There are active orders that must be completed before closing the terminal.';
}