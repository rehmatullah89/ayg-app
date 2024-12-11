<?php

namespace App\Delivery\Middleware;

use App\Delivery\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class DeliveryStatusChangeMiddleware extends ValidationMiddleware
{
    public static function validate(Route $route)
    {
        global $app;
        v::with('App\\Delivery\\Validation\\Rules', true);

        $postVars['status'] = $app->request()->post('status');

        $data = [
            'status' => $postVars['status'],
        ];

        $rules = array(
            'status' => v::notEmpty()->DeliveryStatusRule()->setName('status'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}
