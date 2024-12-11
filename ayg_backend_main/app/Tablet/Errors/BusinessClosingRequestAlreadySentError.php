<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\BusinessClosingRequestAlreadySentException;

/**
 * Class ApiAuthError
 * @package \App\Tablet\Errors
 *
 * @see BusinessClosingRequestAlreadySentException
 */
class BusinessClosingRequestAlreadySentError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5306';
    /**
     * error message
     */
    const MESSAGE = 'Your early closing request has been sent. We are processing it.';
}

