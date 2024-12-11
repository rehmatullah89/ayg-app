<?php

namespace App\Consumer\Errors;

/**
 * Class UserNotFoundError
 * @package Consumer\App\Errors
 */
class UserNotFoundError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_463';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}