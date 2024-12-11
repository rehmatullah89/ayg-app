<?php

namespace App\Tablet\Services;

use App\Tablet\Entities\OrderModifier;
use App\Tablet\Entities\OrderModifierShortInfo;
use App\Tablet\Entities\Retailer;
use App\Tablet\Exceptions\OrderNotFoundException;
use App\Tablet\Exceptions\OrderAlreadyCancelledException;
use App\Tablet\Helpers\CacheExpirationHelper;
use App\Tablet\Helpers\CacheKeyHelper;
use App\Tablet\Helpers\EntityHelper;
use App\Tablet\Helpers\QueueMessageHelper;
use App\Tablet\Helpers\RetailerHelper;
use App\Tablet\Helpers\RetailerItemModifierOptionHelper;
use App\Tablet\Mappers\OrderModifierIntoOrderModifierShortMapper;
use App\Tablet\Repositories\OrderRepositoryInterface;
use App\Tablet\Repositories\OrderTabletHelpRequestsRepositoryInterface;
use App\Tablet\Entities\ListOfOrderShortInfoPaginated;
use App\Tablet\Entities\Order;
use App\Tablet\Entities\OrderShortInfo;
use App\Tablet\Entities\Pagination;
use App\Tablet\Helpers\OrderHelper;
use App\Tablet\Mappers\OrderIntoOrderShortInfoMapper;
use App\Tablet\Mappers\RetailerItemModifierOptionListWithQuantitiesIntoRetailerItemModifierOptionShortInfoListMapper;
use App\Tablet\Repositories\OrderModifierRepositoryInterface;
use App\Tablet\Repositories\RetailerItemModifierOptionRepositoryInterface;
use App\Tablet\Repositories\RetailerItemModifierRepositoryInterface;

/**
 * Class OrderService
 * @package App\Tablet\Services
 */
class OrderService extends Service
{
    /**
     * @var RetailerItemModifierRepositoryInterface
     */
    private $retailerItemModifierRepository;

    /**
     * @var RetailerItemModifierOptionRepositoryInterface
     */
    private $retailerItemModifierOptionRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderModifierRepository;
    /**
     * @var OrderTabletHelpRequestsRepositoryInterface
     */
    private $orderTabletHelpRequestsRepository;
    /**
     * @var SlackOrderHelpRequestService
     */
    private $slackOrderHelpRequestService;
    /**
     * @var QueueServiceInterface
     */
    private $queueService;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var LoggingService
     */
    private $loggingService;

    /**
     * OrderService constructor.
     * @param RetailerItemModifierRepositoryInterface $retailerItemModifierRepository
     * @param RetailerItemModifierOptionRepositoryInterface $retailerItemModifierOptionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderModifierRepositoryInterface $orderModifierRepository
     * @param OrderTabletHelpRequestsRepositoryInterface $orderTabletHelpRequestsRepository
     * @param SlackOrderHelpRequestService $slackOrderHelpRequestService
     * @param QueueServiceInterface $queueService
     * @param CacheService $cacheService
     * @param LoggingService $loggingService
     */
    public function __construct(
        RetailerItemModifierRepositoryInterface $retailerItemModifierRepository,
        RetailerItemModifierOptionRepositoryInterface $retailerItemModifierOptionRepository,
        OrderRepositoryInterface $orderRepository,
        OrderModifierRepositoryInterface $orderModifierRepository,
        OrderTabletHelpRequestsRepositoryInterface $orderTabletHelpRequestsRepository,
        SlackOrderHelpRequestService $slackOrderHelpRequestService,
        QueueServiceInterface $queueService,
        CacheService $cacheService,
        LoggingService $loggingService)
    {
        $this->retailerItemModifierRepository = $retailerItemModifierRepository;
        $this->retailerItemModifierOptionRepository = $retailerItemModifierOptionRepository;
        $this->orderRepository = $orderRepository;
        $this->orderModifierRepository = $orderModifierRepository;
        $this->orderTabletHelpRequestsRepository = $orderTabletHelpRequestsRepository;
        $this->slackOrderHelpRequestService = $slackOrderHelpRequestService;
        $this->queueService = $queueService;
        $this->cacheService = $cacheService;
        $this->loggingService = $loggingService;
    }

    /**
     * @param Retailer[] $retailers
     * @param $page
     * @param $limit
     * @return ListOfOrderShortInfoPaginated
     *
     * List of Active Orders of Retailer
     */
    public function getActiveOrdersPaginatedByRetailers($retailers, $page, $limit)
    {
        $retailerIds = RetailerHelper::retailersListIntoRetailerIdsList($retailers);

        $orderListCount = $this->orderRepository->getActiveOrdersCountByRetailerIdList($retailerIds);
        $orderList = $this->orderRepository->getActiveOrdersListByRetailerIdListPaginated($retailerIds, $page, $limit);

        // iterate through list of orders and identify canceled orders
        // cache response object
        // and report on slack
        $orderList = $this->identifyCanceledOrders($orderList);

        // iterate through list of orders and push to retailer (change status to pushedToRetailer and save into status history)
        // orders that are in payment accepted status
        $orderList = $this->pushToRetailerOrdersThatHasSatusPaymentConfirmed($orderList);

        $notResolvedHelpRequestListByOrderIds = $this->orderTabletHelpRequestsRepository->getNotResolvedByOrderIds(EntityHelper::listOfEntitiesIntoListOfIds($orderList));

        $orderShortInfoList = $this->orderListIntoOrderShortInfoList($orderList, $notResolvedHelpRequestListByOrderIds);

        return new ListOfOrderShortInfoPaginated(
            $orderShortInfoList,
            new Pagination($page, ceil($orderListCount / $limit), $limit, $orderListCount)
        );
    }


    /**
     * @param Retailer[] $retailers
     * @param $page
     * @param $limit
     * @return ListOfOrderShortInfoPaginated
     *
     * List of Past Orders by Retailers
     */
    public function getPastOrdersPaginatedByRetailers($retailers, $page, $limit)
    {
        $retailerIds = RetailerHelper::retailersListIntoRetailerIdsList($retailers);

        $orderListCount = $this->orderRepository->getPastOrdersCountByRetailerIdList($retailerIds);
        $orderList = $this->orderRepository->getPastOrdersListByRetailerIdListPaginated($retailerIds, $page, $limit);

        $notResolvedHelpRequestListByOrderIds = $this->orderTabletHelpRequestsRepository->getNotResolvedByOrderIds(EntityHelper::listOfEntitiesIntoListOfIds($orderList));

        $orderShortInfoList = $this->orderListIntoOrderShortInfoList($orderList, $notResolvedHelpRequestListByOrderIds);

        return new ListOfOrderShortInfoPaginated(
            $orderShortInfoList,
            new Pagination($page, ceil($orderListCount / $limit), $limit, $orderListCount)
        );
    }

    /**
     * @param Retailer[] $retailers
     * @param $orderId
     * @param $content
     * @return OrderShortInfo
     * @throws OrderNotFoundException
     *
     * Add Request to Parse
     */
    public function saveHelpRequest($retailers, $orderId, $content)
    {
        $retailerIds = RetailerHelper::retailersListIntoRetailerIdsList($retailers);
        $order = $this->orderRepository->getOrderWithUserRetailerAndLocationByIdAndRetailerIdList($orderId, $retailerIds);
        if ($order === null) {
            throw new OrderNotFoundException('Order with order ID ' . $orderId . ' and retailer(s) ' . json_encode($retailerIds) . ' not found');
        }

        $orderTabletHelpRequest = $this->orderTabletHelpRequestsRepository->add($orderId, $content);

        $this->slackOrderHelpRequestService->sendSlackMessage($orderTabletHelpRequest);

        $orderShortInfo = $this->getOrderShortInfoByOrder($order);
        $orderShortInfo->setHelpRequestPending(true);

        return $orderShortInfo;
    }

    /**
     * @param Retailer[] $retailers
     * @param $orderId
     * @return OrderShortInfo
     * @throws OrderAlreadyCancelledException
     * @throws OrderNotFoundException Retailer confirms the order
     *
     * checks if a given Order (by OrderId) belongs to one of the retailers from the list,
     * then change it status to confirmed by retailer
     */
    public function confirm($retailers, $orderId)
    {
        $retailerIds = RetailerHelper::retailersListIntoRetailerIdsList($retailers);
        $order = $this->orderRepository->getOrderWithUserRetailerAndLocationByIdAndRetailerIdList($orderId, $retailerIds);
        if ($order === null) {
            throw new OrderNotFoundException('Order with orderID ' . $orderId . ' not found');
        }

        if ($order->getStatus() === Order::STATUS_ACCEPTED_BY_RETAILER
                || $order->getStatus() === Order::STATUS_ACCEPTED_ON_TABLET) {
            $this->loggingService->logInfo('AS_5301', 'Order ' . $orderId . ' is trying to be confirmed while it is already accepted by retailer');
            $orderShortInfo = $this->getOrderShortInfoByOrder($order);
            $notResolvedHelpRequestListByOrderIds = $this->orderTabletHelpRequestsRepository->getNotResolvedByOrderIds([$orderShortInfo->getOrderId()]);
            $orderShortInfo = $this->setHelpRequestPending($orderShortInfo, $notResolvedHelpRequestListByOrderIds);
            return $orderShortInfo;
        }

        // Order was canceled after sending to Tablet
        if ($order->getStatus() === Order::STATUS_CANCELED_BY_SYSTEM
            || $order->getStatus() === Order::STATUS_CANCELED_BY_USER) {
            throw new OrderAlreadyCancelledException('Order with orderID ' . $orderId . ' has been already cancelled');
        }

        if ($order->getStatus() !== Order::STATUS_PUSHED_TO_RETAILER) {
            throw new OrderNotFoundException('Order with orderID ' . $orderId . ' AND status = 4 not found');
        }

        // Confirm Order
        list($confirmedOrder, $acceptedToBeginDelivery, $acceptedToBeginPickup) = $this->orderRepository->changeStatusToAcceptedByRetailer($order);

        $orderEmailReceiptMessage = QueueMessageHelper::getOrderEmailReceiptMessage($order);
        $this->queueService->sendMessage($orderEmailReceiptMessage, 0);

        if (strcasecmp($order->getFullfillmentType(), "p") == 0) {

            if($acceptedToBeginPickup == true) {
                $orderPickupMarkCompleteMessage = QueueMessageHelper::getOrderPickupMarkCompleteMessage($order);
                $messageDelayInSeconds = $this->queueService->getWaitTimeForDelay($order->getEtaTimestamp());
                $this->queueService->sendMessage($orderPickupMarkCompleteMessage, $messageDelayInSeconds);

                $orderPickupMarkCompleteMessage = QueueMessageHelper::getSendNotificationOrderPickupAccepted($order);
                $this->queueService->sendMessage($orderPickupMarkCompleteMessage, 0);

                $logPickupOrderStatus = QueueMessageHelper::getLogOrderDeliveryStatuses($order->getRetailer()->getLocation()->getAirportIataCode(), 'retailer_accepted', time(), $order->getOrderSequenceId());
                $this->queueService->sendMessage($logPickupOrderStatus, 0);
            }
        }
        else if (strcasecmp($order->getFullfillmentType(), "d") == 0) {

            if($acceptedToBeginDelivery == true) {

                // Assign for delivery for orders progressed to STATUS_ACCEPTED_BY_RETAILER
                $orderDeliveryAssignDeliveryMessage = QueueMessageHelper::getOrderDeliveryAssignDeliveryMessage($order);
                $this->queueService->sendMessage($orderDeliveryAssignDeliveryMessage, 0);
                $logDeliveryOrderStatus = QueueMessageHelper::getLogOrderDeliveryStatuses($order->getRetailer()->getLocation()->getAirportIataCode(), 'retailer_accepted', time(), $order->getOrderSequenceId());
                $this->queueService->sendMessage($logDeliveryOrderStatus, 0);
            }
        }

        $orderShortInfo = $this->getOrderShortInfoByOrder($confirmedOrder);
        $notResolvedHelpRequestListByOrderIds = $this->orderTabletHelpRequestsRepository->getNotResolvedByOrderIds([$orderShortInfo->getOrderId()]);
        $orderShortInfo = $this->setHelpRequestPending($orderShortInfo, $notResolvedHelpRequestListByOrderIds);

        return $orderShortInfo;
    }

    /**
     * @param Order[] $orderList
     * @param array $notResolvedHelpRequestListByOrderIds
     * @return OrderShortInfo[]
     *
     * enriches order list with items modifiers and modifiers options and return in short version
     */
    private function orderListIntoOrderShortInfoList(array $orderList, array $notResolvedHelpRequestListByOrderIds)
    {
        $orderShortInfoList = [];
        foreach ($orderList as $order) {
            $orderShortInfo = $this->getOrderShortInfoByOrder($order);
            $orderShortInfo = $this->setHelpRequestPending($orderShortInfo, $notResolvedHelpRequestListByOrderIds);
            $orderShortInfoList[] = $orderShortInfo;
        }
        return $orderShortInfoList;
    }

    /**
     * @param Order $order
     * @return OrderShortInfo
     *
     * enriches order with items modifiers and modifiers options and return in short version
     * use cache if possible
     */
    private function getOrderShortInfoByOrder(Order $order)
    {
        // map order into short version
        $orderShortInfo = OrderIntoOrderShortInfoMapper::map($order);

        // gets modifiers
        $orderModifiers = $this->orderModifierRepository->getOrderModifiersByOrderId($order->getId());

        $orderModifierShortInfoList = [];
        // iterate through modifiers get short info with options and prices
        foreach ($orderModifiers as $orderModifier) {
            $orderModifierShortInfo = OrderModifierIntoOrderModifierShortMapper::map($orderModifier);
            $orderModifierShortInfo = $this->fetchOrderModifierShortInfoByOptions($orderModifierShortInfo, $orderModifier);
            $orderModifierShortInfoList[] = $orderModifierShortInfo;
        }

        // set items and number of items
        $orderShortInfo->setItems($orderModifierShortInfoList);
        $orderShortInfo->setNumberOfItems(count_like_php5($orderModifierShortInfoList));
        return $orderShortInfo;
    }

    /**
     * @param OrderModifierShortInfo $orderModifierShortInfo
     * @param OrderModifier $orderModifier
     * @return OrderModifierShortInfo
     */
    private function fetchOrderModifierShortInfoByOptions(OrderModifierShortInfo $orderModifierShortInfo, OrderModifier $orderModifier)
    {
        // encode json from modifier (if can not, then there is no options)
        $options = json_decode($orderModifier->getModifierOptions());
        if ($options === null) {
            $options = [];
        }

        // get list of options Ids
        $optionIds = OrderHelper::getModifiersOptionsUniqueIdFromJson($options);

        // get modifiers options by ids
        $retailerItemModifierOptionList = $this->retailerItemModifierOptionRepository->getListByUniqueIdList($optionIds);
        $uniqueRetailerItemModifierIdList = RetailerItemModifierOptionHelper::getUniqueRetailerItemModifierIdListFromRetailerItemModifierOptionList($retailerItemModifierOptionList);
        $retailerItemModifierList = $this->retailerItemModifierRepository->getListByUniqueIdList($uniqueRetailerItemModifierIdList);

        // map modifiers options into short version
        $options = RetailerItemModifierOptionListWithQuantitiesIntoRetailerItemModifierOptionShortInfoListMapper::map($retailerItemModifierOptionList, $retailerItemModifierList, $options);

        // set options short version into options value in modifier
        $orderModifierShortInfo->setOptions($options);

        return $orderModifierShortInfo;
    }

    /**
     * @param Order[] $orderList
     * @return Order[]
     *
     */
    private function identifyCanceledOrders($orderList)
    {

        foreach ($orderList as $key => $order) {
        if ($order->getStatus() === Order::STATUS_CANCELED_BY_SYSTEM
            || $order->getStatus() === Order::STATUS_CANCELED_BY_USER) {

                setCache('__tabler_canceled_' . $order->getOrderSequenceId(), $orderList, 1);
                sendTabletOrderCanceledToSlack($order->getOrderSequenceId(), $order);
                //sendTabletOrderCanceledToSlack($order->getOrderSequenceId());
            }
        }
        return $orderList;
    }

    /**
     * @param Order[] $orderList
     * @return Order[]
     *
     * iterate through list of orders and push to retailer (change status to pushedToRetailer and save into status history)
     * orders that are in payment accepted status
     */
    private function pushToRetailerOrdersThatHasSatusPaymentConfirmed($orderList)
    {
        foreach ($orderList as $key => $order) {

            if ($order->getStatus() === Order::STATUS_PAYMENT_ACCEPTED) {
                $orderList[$key] = $this->orderRepository->changeStatusToPushedToRetailer($order);
            }

            // if(intval(getenv('env_tableErrorTrigger')) == 4) {

            //     unset($orderList[$key]);
            // }
        }
        return $orderList;
    }

    /**
     * @param OrderShortInfo $orderShortInfo
     * @param $notResolvedHelpRequestList
     * @return OrderShortInfo
     *
     * looks for a key in $notResolvedHelpRequestList that equals Order Id,
     * if it is found then it means that there is pendingHelpRequest
     */
    private function setHelpRequestPending(OrderShortInfo $orderShortInfo, $notResolvedHelpRequestList)
    {
        if (key_exists($orderShortInfo->getOrderId(), $notResolvedHelpRequestList)) {
            $orderShortInfo->setHelpRequestPending(true);
        } else {
            $orderShortInfo->setHelpRequestPending(false);
        }

        return $orderShortInfo;
    }

}
