<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\RetailerUserNotConfiguredCorrectlyException;

/**
 * Class RetailerUserNotConfiguredCorrectlyError
 * @package \App\Tablet\Errors
 *
 * @see RetailerUserNotConfiguredCorrectlyException
 */
class RetailerUserNotConfiguredCorrectlyError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5307';

    /**
     * error message
     */
    const MESSAGE = 'Your account is not fully configured. Please contact customer service for support.';
}

