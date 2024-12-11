<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserIsNotConsumerException;

/**
 * Class UserIsNotConsumerError
 * @package Consumer\App\Errors
 *
 * @see UserIsNotConsumerException
 */
class UserIsNotConsumerError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_464';

    /**
     * error message
     */
    const MESSAGE = "Something went wrong. We are working on fixing the problem.";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}