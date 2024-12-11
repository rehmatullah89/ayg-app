<?php

namespace App\Consumer\Controllers;

use App\Consumer\Entities\Order;
use App\Consumer\Entities\User;
use App\Consumer\Errors\EnvKeyNotFoundError;
use App\Consumer\Errors\ErrorPrefix;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Responses\InfoHelloWorldResponse;
use App\Consumer\Responses\OrderApplyForCreditResponse;
use App\Consumer\Responses\OrderApplyTipAsFixedValueResponse;
use App\Consumer\Responses\OrderApplyTipAsPercentageResponse;
use App\Consumer\Responses\OrderGetLastRatingResponse;
use App\Consumer\Responses\OrderUserCreditResponse;
use App\Consumer\Services\OrderService;
use App\Consumer\Services\OrderServiceFactory;
use App\Consumer\Services\UserCreditService;
use App\Consumer\Services\UserCreditServiceFactory;
use App\Consumer\Responses\OrderRateResponse;

/**
 * Class OrderController
 * @package App\Consumer\Controllers
 */
class OrderController extends Controller
{
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->orderService = OrderServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }


    /**
     * @param User $user
     * @return void this method prints json response
     *
     * Jira ticket: MVP-1284
     * This method is called by route: POST /rate/a/:apikey/e/:epoch/u/:sessionToken
     * Data in POST: 'overallRating', 'feedback', 'orderId'
     *
     * This method allows User to rate the Order and provide feedback on it
     * This method will return True/False as the status
     */
    public function orderRating(User $user)
    {
        try {
            $overAllRating = $this->app->request->post('overallRating');
            $feedback = $this->app->request->post('feedback');
            $orderId = $this->app->request->post('orderId');

            $overAllRating = intval($overAllRating);

            $result = $this->orderService->addOrderRatingWithFeedback($user->getId(), $orderId, $overAllRating,
                $feedback);
            $response = OrderRateResponse::createFromBool($result);

            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    /**
     * Jira ticket: CON-315
     * This method is called by route: GET order/getLastRating/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId
     * @param User $user
     * @param $orderId
     *
     * gets last order rating for a given Order
     */
    public function getLastOrderRating(User $user, $orderId)
    {
        try {
            $orderRatingWithDelivery = $this->orderService->getLastOrderRatingWithDelivery($user->getId(), $orderId);
            if ($orderRatingWithDelivery === null) {
                $response = OrderGetLastRatingResponse::createEmpty();
            } else {
                $response = OrderGetLastRatingResponse::createFromOrderRatingWithDelivery($orderRatingWithDelivery);
            }
            $this->response->setSuccess($response)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function applyTipAsPercentage(User $user, string $orderId)
    {
        $value = $this->app->request->post('value');
        try {
            $order = $this->orderService->applyTipAsPercentage($orderId, $user->getId(), $value);
            $this->response->setSuccess(OrderApplyTipAsPercentageResponse::createSuccess())->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function add2CartItems()
    {
        $postVars = array();

        $postVars['orderId'] = $this->app->request()->post('orderId');
        $postVars['orderItemId'] = $this->app->request()->post('orderItemId');
        $postVars['uniqueRetailerItemId'] = $this->app->request()->post('uniqueRetailerItemId');
        $postVars['itemQuantity'] = $this->app->request()->post('itemQuantity');
        $postVars['itemComment'] = $this->app->request()->post('itemComment');
        $postVars['options'] = htmlspecialchars_decode($this->app->request()->post('options'));

        $responseArray = $this->orderService->setCartItems($postVars);

        try {
            json_echo(
                json_encode($responseArray)
            );
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function applyTipAsFixedValue(User $user, string $orderId)
    {

        $value = $this->app->request->post('value');
        try {
            $order = $this->orderService->applyTipAsFixedValue($orderId, $user->getId(), $value);
            /** @var $order Order */
            $this->response->setSuccess(OrderApplyTipAsFixedValueResponse::createSuccess())->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_CONSUMER . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }

    }
}
