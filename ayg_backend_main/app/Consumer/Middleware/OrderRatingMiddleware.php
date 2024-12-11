<?php

namespace App\Consumer\Middleware;

use App\Consumer\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class OrderRatingMiddleware extends ValidationMiddleware
{
    /**
     * @param Route $route
     *
     *
     *
     * This method validates the POST data for endpoint: /rate/a/:apikey/e/:epoch/u/:sessionToken
     */
    public static function validate(Route $route)
    {
        global $app;
        v::with('App\\Consumer\\Validation\\Rules', true);

        $postVars['overallRating'] = $overallRating = (($app->request()->post('overallRating')));
        $postVars['orderId'] = $orderId = (($app->request()->post('orderId')));

        $data = [
            'overallRating' => $postVars['overallRating'],
            'orderId' => $postVars['orderId'],
        ];

        $rules = array(
            'overallRating' => v::numeric()->notEmpty()->length(-1, 5)->OrderRateRule()->setName('overallRating'),
            'orderId' => v::notEmpty()->setName('overallRating'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}