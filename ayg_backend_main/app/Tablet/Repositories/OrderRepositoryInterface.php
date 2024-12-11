<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\Order;

interface OrderRepositoryInterface
{

    /**
     * @param array $retailerIdList
     * @param $page
     * @param $limit
     * @return Order[]
     *
     * get limited by page and amount list of orders that are active and related with list of retailer id
     */
    public function getActiveOrdersListByRetailerIdListPaginated(array $retailerIdList, $page, $limit);

    /**
     * @param array $retailerIdList
     * @return int
     *
     * get amount of orders that are active and related with list of retailer id
     */
    public function getActiveOrdersCountByRetailerIdList(array $retailerIdList);

    /**
     * returns count of orders that are in active and also just created and during payment acceptance
     *
     * @param array $retailerIdList
     * @return int
     */
    public function getBlockingEarlyCloseOrdersCountByRetailerIdList(array $retailerIdList);

    /**
     * @param array $retailerIdList
     * @param $page
     * @param $limit
     * @return Order[]
     *
     * get limited by page and amount list of orders that are past and related with list of retailer id
     */
    public function getPastOrdersListByRetailerIdListPaginated(array $retailerIdList, $page, $limit);

    /**
     * @param array $retailerIdList
     * @return int
     *
     * get amount of orders that are past and related with list of retailer id
     */
    public function getPastOrdersCountByRetailerIdList(array $retailerIdList);

    /**
     * @param string $orderId
     * @param string[] $retailerIdList
     * @return Order|null
     *
     * gets Order (with filled user, retailer and retailer location data) based on orderId and retailerId
     */
    public function getOrderWithUserRetailerAndLocationByIdAndRetailerIdList($orderId, array $retailerIdList);

    /**
     * @param Order $order
     * @return Order
     *
     * change order status to accepted by retailer and adds new entry into database : OrderStatus
     */
    public function changeStatusToAcceptedByRetailer(Order $order);

    /**
     * @param Order $order
     * @return Order
     *
     * change order status to pushed to retailer and adds new entry into database : OrderStatus
     */
    public function changeStatusToPushedToRetailer(Order $order);
}