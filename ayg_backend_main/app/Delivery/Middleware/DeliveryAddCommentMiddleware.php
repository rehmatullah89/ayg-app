<?php

namespace App\Delivery\Middleware;

use App\Delivery\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class DeliveryAddCommentMiddleware extends ValidationMiddleware
{
    public static function validate(Route $route)
    {
        global $app;
        v::with('App\\Delivery\\Validation\\Rules', true);

        $postVars['comment'] = $app->request()->post('comment');

        $data = [
            'comment' => $postVars['comment'],
        ];

        $rules = array(
            'comment' => v::notEmpty()->setName('comment'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}
