<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\InvalidCouponException;

/**
 * Class InvalidCouponError
 * @package Consumer\App\Errors
 *
 * @see InvalidCouponException
 */
class InvalidCouponError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_819';

    /**
     * error message
     */
    const MESSAGE = 'This coupon is not valid.';
}