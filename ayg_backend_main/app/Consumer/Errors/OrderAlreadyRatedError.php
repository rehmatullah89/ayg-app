<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\OrderAlreadyRatedException;

/**
 * Class OrderAlreadyRatedError
 * @package Consumer\App\Errors
 *
 * @see OrderAlreadyRatedException
 */
class OrderAlreadyRatedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_890';

    /**
     * error message
     */
    const MESSAGE = 'You can not add rating twice.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}

