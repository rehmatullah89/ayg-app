<?php
namespace App\Consumer\Errors;

use App\Consumer\Exceptions\QueueServiceNotSupportedException;

/**
 * Class QueueServiceNotSupportedError
 * @package \App\Consumer\Errors
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