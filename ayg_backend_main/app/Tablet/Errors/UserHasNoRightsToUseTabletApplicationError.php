<?php

namespace App\Tablet\Errors;

/**
 * Class UserHasNoRightsToUseTabletApplicationError
 * @package App\Tablet\Errors
 */
class UserHasNoRightsToUseTabletApplicationError extends Error
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