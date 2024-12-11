<?php

namespace App\Consumer\Middleware;

use App\Consumer\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class OrderCheckoutMiddleware extends ValidationMiddleware
{
    public static function validate(Route $route)
    {
        global $app;
        v::with('App\\Consumer\\Validation\\Rules', true);

        $postVars['orderId'] = $orderId = (($app->request()->post('orderId')));
        $postVars['deliveryLocation'] = $orderId = (($app->request()->post('deliveryLocation')));
        $postVars['requestedFullFillmentTimestamp'] = $orderId = (($app->request()->post('requestedFullFillmentTimestamp')));
        $postVars['applyTipAs'] = $orderId = (($app->request()->post('applyTipAs')));
        $postVars['applyTipValue'] = $orderId = (($app->request()->post('applyTipValue')));

        $data = [
            'orderId' => $postVars['orderId'],
            'deliveryLocation' => $postVars['deliveryLocation'],
            'requestedFullFillmentTimestamp' => $postVars['requestedFullFillmentTimestamp'],
            'applyTipAs' => $postVars['applyTipAs'],
            'applyTipValue' => $postVars['applyTipValue'],
        ];

        $rules = array(
            'orderId' => v::notEmpty()->setName('orderId'),
            'deliveryLocation' => v::notEmpty()->setName('deliveryLocation'),
            'requestedFullFillmentTimestamp' => v::intVal()->setName('requestedFullFillmentTimestamp'),
            'applyTipAs' => v::notEmpty()->setName('applyTipAs'),
            'applyTipValue' => v::not(v::negative())->setName('applyTipValue'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}
