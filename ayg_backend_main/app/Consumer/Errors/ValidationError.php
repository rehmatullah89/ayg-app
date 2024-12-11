<?php

namespace App\Consumer\Errors;

/**
 * Class UserDoesNotExistError
 * @package Consumer\App\Errors
 */
class ValidationError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_032';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_WARNING;
}