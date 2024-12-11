<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\PromoCodeIsNotValidForTheSelectedAirportException;


/**
 * Class PromoCodeIsNotValidForTheSelectedAirportError
 * @package Consumer\App\Errors
 *
 * @see PromoCodeIsNotValidForTheSelectedAirportException
 */
class PromoCodeIsNotValidForTheSelectedAirportError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_470';

    /**
     * error message
     */
    const MESSAGE = 'This offer is not valid for the selected airport.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_INFO;
}