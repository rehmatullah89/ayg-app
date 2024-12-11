<?php

namespace App\Tablet\Controllers;

use App\Tablet\Entities\User;
use App\Tablet\Errors\ErrorPrefix;
use App\Tablet\Responses\OrderActiveOrdersResponse;
use App\Tablet\Responses\OrderConfirmResponse;
use App\Tablet\Responses\OrderPastOrdersResponse;
use App\Tablet\Responses\OrderRequestHelpResponse;
use App\Tablet\Services\OrderProcessingService;
use App\Tablet\Services\OrderProcessingServiceFactory;

/**
 * Class OrderController
 * @package App\Tablet\Controllers
 */
class OrderController extends Controller
{
    /**
     * @var OrderProcessingService
     */
    private $orderProcessingService;

    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        try {
            parent::__construct();
            $this->orderProcessingService = OrderProcessingServiceFactory::create($this->cacheService);
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER, $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @param $page
     * @param $limit
     * @return void - controller's method prints json response
     *
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1269
     * /order/getActiveOrders/a/:apikey/e/:epoch/u/:sessionToken/page/:page/limit/:limit
     * used by: TabletUser
     * method: GET
     *
     * gets active orders paginated
     * sets user as active (set ping value in cache)
     */
    public function getActiveOrders(User $user, $page, $limit)
    {
        // if(intval(getenv('env_tableErrorTrigger')) == 1) {

        //     json_error("AS_1000", "", "AS_2004 - Multiple Fetch Failed for test objectValueArray: ", 1);
        // }
        // else if(intval(getenv('env_tableErrorTrigger')) == 2) {

        //     json_error("AS_014", "", "AS_2004 - Multiple Fetch Failed for test objectValueArray: ", 1);
        // }
        // else if(intval(getenv('env_tableErrorTrigger')) == 3) {

        //     json_error("AS_001", "", "AS_2004 - Multiple Fetch Failed for test objectValueArray: ", 1);
        // }

        try {
            $page = intval($page);
            $limit = intval($limit);

            $listOfShortOrdersPaginatedAndCloseEarlyData = $this->orderProcessingService->getActiveOrdersAndCloseEarlyDataAndSetRetailerAsActive($user, $page, $limit);

            $returnData = new OrderActiveOrdersResponse(
                $listOfShortOrdersPaginatedAndCloseEarlyData->getListOfOrderShortInfoPaginated()->getOrderList(),
                $listOfShortOrdersPaginatedAndCloseEarlyData->getCloseEarlyData()
            );
            $pagination = $listOfShortOrdersPaginatedAndCloseEarlyData->getListOfOrderShortInfoPaginated()->getPagination();

            $this->response->setPaginatedSuccess($returnData, $pagination)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER, $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @param $page
     * @param $limit
     * @return void - controller's method prints json response
     *
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1269
     * /order/getPastOrders/a/:apikey/e/:epoch/u/:sessionToken/page/:page/limit/:limit
     * used by: TabletUser
     * method: GET
     *
     * gets active orders paginated
     */
    public function getPastOrders(User $user, $page, $limit)
    {
        try {
            $page = intval($page);
            $limit = intval($limit);

            $listOfShortOrdersPaginatedAndCloseEarlyData = $this->orderProcessingService->getPastOrdersAndCloseEarlyData($user, $page, $limit);

            $returnData = new OrderPastOrdersResponse(
                $listOfShortOrdersPaginatedAndCloseEarlyData->getListOfOrderShortInfoPaginated()->getOrderList(),
                $listOfShortOrdersPaginatedAndCloseEarlyData->getCloseEarlyData()
            );
            $pagination = $listOfShortOrdersPaginatedAndCloseEarlyData->getListOfOrderShortInfoPaginated()->getPagination();

            $this->response->setPaginatedSuccess($returnData, $pagination)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER, $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @return void - controller's method prints json response
     *
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1271
     * used by: TabletUser
     * method: POST
     *
     * sets new entry for order request in the database
     * sends slack message
     */
    public function requestHelp(User $user)
    {
        try {
            $orderId = $this->app->request->post('orderId');
            $content = $this->app->request->post('content');

            $orderShortInfo = $this->orderProcessingService->requestHelp($user, $orderId, $content);

            $this->response->setSuccess(new OrderRequestHelpResponse($orderShortInfo))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER, $e)->returnJson();
        }
    }

    /**
     * @param User $user
     * @param $orderId
     * @return void - controller's method prints json response
     *
     * Jira Ticket - https://airportsherpa.atlassian.net/browse/MVP-1270
     * /order/confirm/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId
     * used by: TabletUser
     * method: GET
     *
     *
     * confirm order (change status from Order::STATUS_PUSHED_TO_RETAILER to Order::STATUS_ACCEPTED_BY_RETAILER)
     * confirm order (change status from Order::STATUS_PUSHED_TO_RETAILER to Order::STATUS_ACCEPTED_ON_TABLET for DualConfig retailers)
     */
    public function confirm(User $user, $orderId)
    {
        try {
            $confirmedOrder = $this->orderProcessingService->confirm($user, $orderId);

            $this->response->setSuccess(new OrderConfirmResponse($confirmedOrder))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER, $e)->returnJson();
        }

    }
}
