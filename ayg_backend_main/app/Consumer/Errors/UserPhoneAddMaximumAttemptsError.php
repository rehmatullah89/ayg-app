<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\UserPhoneAddMaximumAttemptsException;

/**
 * Class UserPhoneAddMaximumAttemptsError
 * @package Consumer\App\Errors
 *
 * @see UserPhoneAddMaximumAttemptsException
 */
class UserPhoneAddMaximumAttemptsError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_444';

    /**
     * error message
     */
    const MESSAGE = "You have reached maximum attempts allowed. Please try again in an hour.";

    /**
     * error level
     */
    const LEVEL = Error::LEVEL_ERROR;
}