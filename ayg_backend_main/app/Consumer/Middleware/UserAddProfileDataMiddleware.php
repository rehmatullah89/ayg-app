<?php
namespace App\Consumer\Middleware;

use App\Consumer\Errors\ErrorPrefix;
use Respect\Validation\Validator as v;

class UserAddProfileDataMiddleware extends ValidationMiddleware
{
    public static function validate(\Slim\Route $route)
    {
        global $app;
        v::with('App\\Consumer\\Validation\\Rules', true);

        $email = $app->request()->post('email');
        $email = strtolower(sanitizeEmail(rawurldecode($email)));

        $data = [
            'firstName' => $app->request()->post('firstName'),
            'lastName' => $app->request()->post('lastName'),
            'email' => $email,
        ];

        $rules = array(
            'firstName' => v::notEmpty()->setName('firstName'),
            'lastName' => v::notEmpty()->setName('lastName'),
            'email' => v::notEmpty()->email()->setName('email'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_TABLET .
            ErrorPrefix::CONTROLLER_MIDDLEWARE
        );
    }
}


