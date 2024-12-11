<?php
namespace App\Tablet\Errors;

use App\Tablet\Exceptions\TabletReopenLevelNotMetException;

/**
 * Class TabletReopenLevelNotMetError
 * @package App\Tablet\Errors
 *
 * @see TabletReopenLevelNotMetException
 */
class TabletReopenLevelNotMetError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_5400';

    /**
     * error message
     */
    const MESSAGE = 'The tablet is closed by the adminstrator and cannot be opened. Please call AtYourGate for support.';
}
