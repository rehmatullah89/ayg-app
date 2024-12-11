<?php

namespace App\Consumer\Errors;

/**
 * Class ConfigKeyNotFoundError
 * @package App\Tablet\Errors
 */
class ConfigKeyNotFoundError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_033';

    /**
     * error message
     */
    //const MESSAGE = 'Config Key Not Found';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}