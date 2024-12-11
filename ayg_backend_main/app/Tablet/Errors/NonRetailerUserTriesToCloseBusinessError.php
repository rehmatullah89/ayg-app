<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\NonRetailerUserTriesToCloseBusinessException;

/**
 * Class NonRetailerUserTriesToCloseBusinessError
 * @package App\Tablet\Errors
 *
 * @see NonRetailerUserTriesToCloseBusinessException
 */
class NonRetailerUserTriesToCloseBusinessError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5308';

    /**
     * error message
     */
    const MESSAGE = 'You are not authorized to close the POS Terminal.';
}