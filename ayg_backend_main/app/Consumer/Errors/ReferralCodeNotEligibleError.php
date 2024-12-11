<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\ReferralCodeNotEligibleException;


/**
 * Class ReferralCodeNotEligibleError
 * @package Consumer\App\Errors
 *
 * @see ReferralCodeNotEligibleException
 */
class ReferralCodeNotEligibleError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_477';

    /**
     * error message
     */
    const MESSAGE = 'Sorry, your account is not eligible for this referral offer.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}