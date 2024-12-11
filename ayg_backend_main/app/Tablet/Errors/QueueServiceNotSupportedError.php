<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\QueueServiceNotSupportedException;

/**
 * Class QueueServiceNotSupportedError
 * @package \App\Tablet\Errors
 *
 * @see QueueServiceNotSupportedException
 */
class QueueServiceNotSupportedError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_1073';

    /**
     * error message
     */
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}