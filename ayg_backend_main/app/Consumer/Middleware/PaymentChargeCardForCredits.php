<?php
namespace App\Consumer\Middleware;

use App\Consumer\Errors\ErrorPrefix;
use Respect\Validation\Validator as v;

class PaymentChargeCardForCredits extends ValidationMiddleware
{
    public static function validate(\Slim\Route $route)
    {
        global $app;
        v::with('App\\Consumer\\Validation\\Rules', true);

        $data = [
            'voucherId' => $app->request()->post('voucherId'),
            'amountInCents' => $app->request()->post('amountInCents'),
            'paymentMethodNonce' => $app->request()->post('paymentMethodNonce'),
        ];

        $rules = array(
            'voucherId' => v::notEmpty()->setName('voucherId'),
            'amountInCents' => v::intVal()->positive()->notEmpty()->setName('amountInCents'),
            'paymentMethodNonce' => v::notEmpty()->setName('paymentMethodNonce'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_TABLET .
            ErrorPrefix::CONTROLLER_MIDDLEWARE
        );
    }
}


