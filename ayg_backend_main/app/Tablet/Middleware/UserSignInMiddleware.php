<?php

namespace App\Tablet\Middleware;

use App\Tablet\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class UserSignInMiddleware extends ValidationMiddleware
{
    public static function validate(Route $route)
    {
        global $app;
        v::with('App\\Tablet\\Validation\\Rules', true);

        $postVars['type'] = $type = strtolower(($app->request()->post('type')));
        $postVars['email'] = $email = strtolower(($app->request()->post('email')));
        $postVars['deviceArray'] = $deviceArray = ($app->request()->post('deviceArray'));

        $data = [
            'type' => $postVars['type'],
            'email' => $postVars['email'],
            'deviceArray' => $postVars['deviceArray'],
        ];

        $rules = array(
            'type' => v::notEmpty()->TabletTypeRule()->setName('type'),
            'email' => v::notEmpty()->email()->setName('email'),
            'deviceArray' => v::DeviceArrayRequirementsRule()->setName('deviceArray'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}
