<?php

namespace App\Tablet\Middleware;

use App\Tablet\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class OrderHelpRequestMiddleware extends ValidationMiddleware
{
    public static function validate(Route $route)
    {
        global $app;
        v::with('App\\Tablet\\Validation\\Rules', true);


        $data = [
            'orderId' => $app->request()->post('orderId'),
            'content' => $app->request()->post('content'),
        ];

        $rules = array(
            'orderId' => v::notEmpty()->setName('orderId'),
            'content' => v::notEmpty()->setName('content'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_TABLET.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}