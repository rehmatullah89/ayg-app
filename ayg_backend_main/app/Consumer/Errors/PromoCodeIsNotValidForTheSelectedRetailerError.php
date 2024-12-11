<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsNotValidForTheSelectedRetailerException;


/**
 * Class PromoCodeIsNotValidForTheSelectedRetailerError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsNotValidForTheSelectedRetailerException
 */
class PromoCodeIsNotValidForTheSelectedRetailerError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_471';

    /**
     * error message
     */
    const MESSAGE = 'This offer is not valid for the selected retailer.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}