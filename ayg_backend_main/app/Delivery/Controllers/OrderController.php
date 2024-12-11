<?php

namespace App\Delivery\Controllers;

use App\Delivery\Entities\OrderDeliveryStatusFactory;
use App\Delivery\Entities\User;
use App\Delivery\Errors\ErrorPrefix;
use App\Delivery\Responses\OrderActiveOrdersResponse;
use App\Delivery\Responses\OrderAddCommentResponse;
use App\Delivery\Responses\OrderChangeDeliveryStatusResponse;
use App\Delivery\Services\OrderService;
use App\Delivery\Services\OrderServiceFactory;

/**
 * Class OrderController
 * @package App\Tablet\Controllers
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
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }


    public function getActiveOrders(User $user, $page, $limit)
    {
        try {
            $page = intval($page);
            $limit = intval($limit);

            $ordersPaginated = $this->orderService->getActiveOrdersPaginated($user, $page, $limit);

            $returnData = new OrderActiveOrdersResponse($ordersPaginated->getOrderShortInfoList());
            $pagination = $ordersPaginated->getPagination();

            $this->response->setPaginatedSuccess($returnData, $pagination)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }


    public function getCompletedOrders(User $user, $page, $limit)
    {
        try {
            $page = intval($page);
            $limit = intval($limit);

            $ordersPaginated = $this->orderService->getCompletedOrdersPaginated($user, $page, $limit);

            $returnData = new OrderActiveOrdersResponse($ordersPaginated->getOrderShortInfoList());
            $pagination = $ordersPaginated->getPagination();

            $this->response->setPaginatedSuccess($returnData, $pagination)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function getOrderDetails(User $user, $orderId)
    {
        try {
            $orderDetails = $this->orderService->getOrderDetails($user, $orderId);
            $this->response->setSuccess($orderDetails)->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function changeStatus(User $user, $orderId)
    {
        try {
            $newStatus = OrderDeliveryStatusFactory::fromName($this->app->request->post('status'));
            $changeStatus = $this->orderService->changeDeliveryStatus($user, $orderId, $newStatus);
            $this->response->setSuccess(OrderChangeDeliveryStatusResponse::createFromBool($changeStatus))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }
    }

    public function addComment(User $user, $orderId)
    {
        try {
            $comment = $this->app->request->post('comment');
            $orderComment=$this->orderService->addComment($user, $orderId, $comment);
            $this->response->setSuccess(OrderAddCommentResponse::createFromOrderComment($orderComment))->returnJson();
        } catch (\Exception $e) {
            $this->response->setErrorFromException(ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_ORDER,
                $e)->returnJson();
        }

    }
}
