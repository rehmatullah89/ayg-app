<?php

namespace App\Consumer\Middleware;

use App\Consumer\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class OrderUserCreditAppliedMiddleware extends ValidationMiddleware
{
    public static function validate(Route $route)
    {
        global $app;

        v::with('App\\Consumer\\Validation\\Rules', true);

        $postVars['appliedInCents'] = $appliedInCents = (($app->request()->post('appliedInCents')));
        $postVars['userCreditId'] = $userCreditId = (($app->request()->post('userCreditId')));

        $data = [
            'appliedInCents' => $postVars['appliedInCents'],
            'userCreditId' => $postVars['userCreditId'],
        ];

        $rules = array(
            'appliedInCents' => v::numeric()->notEmpty()->setName('appliedInCents'),
            'userCreditId' => v::notEmpty()->setName('userCreditId'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}