<?php

namespace App\Delivery\Errors;


class IncorrectApiCallError extends Error
{
    /**
     * error code
     */
    //const CODE = '1110';
    const CODE = 'AS_005';

    /**
     * error message
     */
    //const MESSAGE = 'Incorrect API Call';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}
