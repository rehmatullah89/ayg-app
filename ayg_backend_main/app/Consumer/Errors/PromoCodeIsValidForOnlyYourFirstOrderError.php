<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsValidForOnlyYourFirstOrderException;


/**
 * Class PromoCodeIsValidForOnlyYourFirstOrderError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsValidForOnlyYourFirstOrderException
 */
class PromoCodeIsValidForOnlyYourFirstOrderError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_473';

    /**
     * error message
     */
    const MESSAGE = 'This offer is only valid for your first order.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}