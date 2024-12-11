<?php
namespace App\Delivery\Errors;

use App\Delivery\Exceptions\SignOutFailException;

class SignOutFailError extends Error
{
    /**
     * error code
     */
    //const CODE = '3510';
    const CODE = 'AS_438';

    /**
     * error message
     */
    //const MESSAGE = 'User Can Not Sign Out';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}
