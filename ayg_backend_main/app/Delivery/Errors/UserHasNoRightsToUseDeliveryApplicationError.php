<?php

namespace App\Delivery\Errors;

class UserHasNoRightsToUseDeliveryApplicationError extends Error
{
    /**
     * error code
     */
    //const CODE = '3411';
    const CODE = 'AS_465';

    /**
     * error message
     */
    //const MESSAGE = 'This user has no rights to use tablet application';
    const MESSAGE = 'Something went wrong. We are working on fixing the problem.';
}
