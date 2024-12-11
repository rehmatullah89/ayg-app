<?php

namespace App\Consumer\Middleware;

use App\Consumer\Errors\ErrorPrefix;
use Slim\Route;
use Respect\Validation\Validator as v;

class OrderApplyCreditsToUserMiddleware extends ValidationMiddleware
{
    /**
     * @param Route $route
     *
     *
     *
     *
     * This method validates the POST data for endpoint: /credit/request/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId
     */
    public static function validate(Route $route)
    {
        global $app;

        v::with('App\\Consumer\\Validation\\Rules', true);

        $postVars['creditsInCents'] = $creditsInCents = (($app->request()->post('creditsInCents')));
        $postVars['userId'] = $userId = (($app->request()->post('userId')));
        $postVars['reasonForCredit'] = $reasonForCredit = (($app->request()->post('reasonForCredit')));
        $postVars['reasonForCreditCode'] = $reasonForCreditCode = (($app->request()->post('reasonForCreditCode')));

        $data = [
            'creditsInCents' => $postVars['creditsInCents'],
            'reasonForCredit' => $postVars['reasonForCredit'],
            'reasonForCreditCode' => $postVars['reasonForCreditCode'],
            'userId' => $postVars['userId'],
        ];

        $rules = array(
            'creditsInCents' => v::intVal()->positive()->notEmpty()->setName('creditsInCents'),
            'reasonForCredit' => v::notEmpty()->setName('reasonForCredit'),
            'reasonForCreditCode' => v::notEmpty()->setName('reasonForCreditCode'),
            'userId' => v::notOptional()->setName('userId'),
        );

        self::validateByDataAndRules($data, $rules,
            ErrorPrefix::APPLICATION_CONSUMER.
            ErrorPrefix::CONTROLLER_MIDDLEWARE);
    }
}