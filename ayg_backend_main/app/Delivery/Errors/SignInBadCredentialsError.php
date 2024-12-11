<?php
namespace App\Delivery\Errors;

class SignInBadCredentialsError extends Error
{
    /**
     * error code
     */
    const CODE = 'AS_020';

    /**
     * error message
     */
    const MESSAGE = 'Your username or password is incorrect. Please try again.';
}
