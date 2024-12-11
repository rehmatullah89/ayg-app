<?php

namespace App\Tablet\Errors;

/**
 * Class OtherApplicationError
 * @package \App\Tablet\Errors
 */
class OtherApplicationError extends Error
{
    /**
     * error code
     */
    //const CODE = 9010;
    const CODE = 'AS_000';

    /**
     * error message
     */
    //const MESSAGE = 'Other Application Error';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}