<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\OrderNotFoundException;

/**
 * Class OrderNotFoundError
 * @package App\Consumer\Errors
 *
 * @see OrderNotFoundException
 */
class OrderNotFoundError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_839';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}