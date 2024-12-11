<?php

namespace App\Consumer\Errors;

/**
 * Class OtherApplicationError
 * @package \App\Consumer\Errors
 */
class OtherApplicationError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_000';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}
