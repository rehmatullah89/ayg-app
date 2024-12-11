<?php

namespace App\Tablet\Repositories;

use App\Tablet\Entities\Order;
use App\Tablet\Services\CacheService;

/**
 * Class OrderCacheRepository
 * @package App\Tablet\Repositories
 */
class OrderCacheRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * OrderCacheRepository constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param CacheService $cacheService
     */
    public function __construct(OrderRepositoryInterface $orderRepository, CacheService $cacheService)
    {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }


    /**
     * @param array $retailerIdList
     * @return int
     *
     *
     * return count of Active order for a given retailer list,
     * Active means, orders that has one of the statuses:
     * payment accepted, pushed to retailer, accepted by retailer
     */
    public function getActiveOrdersCountByRetailerIdList(array $retailerIdList)
    {
        return $this->decorator->getActiveOrdersCountByRetailerIdList($retailerIdList);
    }

    /**
     * it does not use cache - so directly call decorator method
     *
     * @param array $retailerIdList
     * @return int
     */
    public function getBlockingEarlyCloseOrdersCountByRetailerIdList(array $retailerIdList)
    {
        return $this->decorator->getBlockingEarlyCloseOrdersCountByRetailerIdList($retailerIdList);
    }

    /**
     * @param array $retailerIdList
     * @param $page
     * @param $limit
     * @return Order[]
     *
     *
     * return list of Active order for a given retailer list
     * limited amount of items and taken with offset (calculated based on given page)
     * Active means, orders that has one of the statuses:
     * payment accepted, pushed to retailer, accepted by retailer
     */
    public function getActiveOrdersListByRetailerIdListPaginated(array $retailerIdList, $page, $limit)
    {
        return $this->decorator->getActiveOrdersListByRetailerIdListPaginated($retailerIdList, $page, $limit);
    }


    /**
     * @param array $retailerIdList
     * @return int
     *
     * return count of Past order for a given retailer list,
     * Past means, completed or canceled
     */
    public function getPastOrdersCountByRetailerIdList(array $retailerIdList)
    {
        return $this->decorator->getPastOrdersCountByRetailerIdList($retailerIdList);
    }

    /**
     * @param array $retailerIdList
     * @param $page
     * @param $limit
     * @return Order[]
     *
     *
     * return list of Past order for a given retailer list
     * limited amount of items and taken with offset (calculated based on given page)
     * Past means, completed or canceled
     */
    public function getPastOrdersListByRetailerIdListPaginated(array $retailerIdList, $page, $limit)
    {
        return $this->decorator->getPastOrdersListByRetailerIdListPaginated($retailerIdList, $page, $limit);
    }

    /**
     * @param string $orderId
     * @param string[] $retailerIdList
     * @return Order|null
     *
     *
     * returns Order with a given Id for a give retailers Ids
     */
    public function getOrderWithUserRetailerAndLocationByIdAndRetailerIdList($orderId, array $retailerIdList)
    {
        return $this->decorator->getOrderWithUserRetailerAndLocationByIdAndRetailerIdList($orderId, $retailerIdList);
    }

    /**
     * @param Order $order
     * @return Order
     *
     *
     * changes status to accepted by Retailer
     * also add history input in the OrderStatus table
     */
    public function changeStatusToAcceptedByRetailer(Order $order)
    {
        return $this->decorator->changeStatusToAcceptedByRetailer($order);
    }

    /**
     * @param Order $order
     * @return Order
     *
     *
     * changes status to pushed to Retailer
     * also add history input in the OrderStatus table
     */
    public function changeStatusToPushedToRetailer(Order $order)
    {
        return $this->decorator->changeStatusToPushedToRetailer($order);
    }
}