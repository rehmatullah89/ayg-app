<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeOfCreditTypeAppliedInCartException;

/**
 * Class PromoCodeOfCreditTypeAppliedInCartError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeOfCreditTypeAppliedInCartException
 */
class PromoCodeOfCreditTypeAppliedInCartError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_485';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, this offer is only available for new users.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}