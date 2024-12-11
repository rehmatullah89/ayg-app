<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\Order;
use App\Tablet\Entities\OrderShortInfo;
use App\Tablet\Entities\Retailer;
use App\Tablet\Helpers\OrderHelper;
use App\Tablet\Mappers\ParseOrderIntoOrderMapper;
use App\Tablet\Mappers\ParseRetailerIntoRetailerMapper;
use App\Tablet\Mappers\ParseTerminalGateMapIntoTerminalGateMapMapper;
use App\Tablet\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

/**
 * Class OrderParseRepository
 * @package App\Tablet\Repositories
 */
class OrderParseRepository extends ParseRepository implements OrderRepositoryInterface
{
    /**
     * returns count of orders that are active as in not seen by retailer yet
     * (used by Ops Dashboard only)
     * 
     * @param array $retailerIdList
     * @return int
     */
    public function getBlockingEarlyCloseOrdersForOpsDashboardCountByRetailerIdList(array $retailerIdList)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseFirstStepOrdersQuery = new ParseQuery('Order');
        $parseFirstStepOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);
        $parseFirstStepOrdersQuery->containedIn('status', [
            Order::STATUS_ORDERED,
            Order::STATUS_PAYMENT_ACCEPTED,
            Order::STATUS_PUSHED_TO_RETAILER
        ]);

        try {

            $parseOrdersQuery = $parseFirstStepOrdersQuery->find(false, true);
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 6", 1);
        }

        if(is_bool($parseOrdersQuery)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 6", 1);
        }

        return count_like_php5($parseOrdersQuery);
    }
    /**
     * returns count of orders that are in active and also just created and during payment acceptance
     *
     * @param array $retailerIdList
     * @return int
     */
    public function getBlockingEarlyCloseOrdersCountByRetailerIdList(array $retailerIdList)
    {
        $parseActiveOrdersQuery = $this->createActiveOrderByRetailerIdListParseQuery($retailerIdList);

        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseFirstStepOrdersQuery = new ParseQuery('Order');
        $parseFirstStepOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);
        $parseFirstStepOrdersQuery->containedIn('status', [
            Order::STATUS_ORDERED,
            Order::STATUS_PAYMENT_ACCEPTED,
        ]);

        $parseOrdersQuery = ParseQuery::orQueries([$parseActiveOrdersQuery, $parseFirstStepOrdersQuery]);

        try {

            $count = $parseOrdersQuery->count();
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch", 1);
        }

        if(is_bool($count)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch", 1);
        }

        return $count;
    }

    /**
     * @param array $retailerIdList
     * @return int
     *
     * return count of Active order for a given retailer list,
     * active orders are:
     * for pickup: payment accepted, pushed to retailer, accepted by retailer
     * for delivery: payment accepted, pushed to retailer and accepted by retailer but only to the place
     * where order is picked up by delivery man
     */
    public function getActiveOrdersCountByRetailerIdList(array $retailerIdList)
    {
        $parseOrdersQuery = $this->createActiveOrderByRetailerIdListParseQuery($retailerIdList);

        try {

            $count = $parseOrdersQuery->count();
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 9", 1);
        }

        if(is_bool($count)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 9", 1);
        }

        return $count;
    }

    /**
     * @param array $retailerIdList
     * @param $page
     * @param $limit
     * @return Order[] return list of Active order for a given retailer list
     *
     * return list of Active order for a given retailer list
     * limited amount of items and taken with offset (calculated based on given page)
     * Active means, orders that has one of the statuses:
     * payment accepted, pushed to retailer, accepted by retailer
     */
    public function getActiveOrdersListByRetailerIdListPaginated(array $retailerIdList, $page, $limit)
    { 
        $parseOrdersQuery = $this->createActiveOrderByRetailerIdListParseQuery($retailerIdList);

        $parseOrdersQuery->ascending('submitTimestamp');

        $parseOrdersQuery->limit($limit);
        $parseOrdersQuery->skip(($page - 1) * $limit);

        $parseOrdersQuery->includeKey('retailer');
        $parseOrdersQuery->includeKey('retailer.location');
        $parseOrdersQuery->includeKey('user');

        try {

            $parseOrders = $parseOrdersQuery->find(false, true);
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 2", 1);
        }

        if(is_bool($parseOrders)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 2", 1);
        }

        // JMD
        return $this->getOrdersListByParseOrdersList($parseOrders);
    }

    /**
     * @param array $retailerIdList
     * @return int
     *
     * return count of Past order for a given retailer list,
     * past orders are:
     * for pickup: canceled, completed
     * for delivery: canceled, completed and accepted by retailer with delivery status after picked up (including)
     * where order is picked up by delivery man
     */
    public function getPastOrdersCountByRetailerIdList(array $retailerIdList)
    {
        $parseOrdersQuery = $this->createPastOrderByRetailerIdListParseQuery($retailerIdList);

        try {

            $count = $parseOrdersQuery->count();
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 7", 1);
        }

        if(is_bool($count)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 7", 1);
        }

        return $count;
    }

    /**
     * @param array $retailerIdList
     * @param $page
     * @param $limit
     * @return Order[] return list of Past order for a given retailer list
     *
     * return list of Past order for a given retailer list
     * limited amount of items and taken with offset (calculated based on given page)
     * past orders are:
     * for pickup: canceled, completed
     * for delivery: canceled, completed and accepted by retailer with delivery status after picked up (including)
     * where order is picked up by delivery man
     */
    public function getPastOrdersListByRetailerIdListPaginated(array $retailerIdList, $page, $limit)
    {
        $parseOrdersQuery = $this->createPastOrderByRetailerIdListParseQuery($retailerIdList);

        $parseOrdersQuery->descending('submitTimestamp');

        $parseOrdersQuery->limit($limit);
        $parseOrdersQuery->skip(($page - 1) * $limit);

        $parseOrdersQuery->includeKey('retailer');
        $parseOrdersQuery->includeKey('retailer.location');
        $parseOrdersQuery->includeKey('user');

        try {

            $parseOrders = $parseOrdersQuery->find(false, true);
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 3", 1);
        }

        if(is_bool($parseOrders)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 3", 1);
        }

        return $this->getOrdersListByParseOrdersList($parseOrders);
    }

    /**
     * @param string $orderId
     * @param array $retailerIdList
     * @return Order|null
     *
     * returns Order with a given Id for a give retailers Ids
     */
    public function getOrderWithUserRetailerAndLocationByIdAndRetailerIdList($orderId, array $retailerIdList)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrderQuery = new ParseQuery('Order');
        $parseOrderQuery->equalTo('objectId', $orderId);
        $parseOrderQuery->matchesQuery('retailer', $parseRetailersQuery);
        $parseOrderQuery->includeKey('retailer');
        $parseOrderQuery->includeKey('retailer.location');
        $parseOrderQuery->includeKey('user');

        try {

            $parseOrder = $parseOrderQuery->find(false, true);
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 4", 1);
        }

        if(is_bool($parseOrder)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 4", 1);
        }

        if (count_like_php5($parseOrder)==0) {
            return null;
        }

        $parseOrder = $parseOrder[0];

        $user = ParseUserIntoUserMapper::map($parseOrder->get('user'));
        $retailer = ParseRetailerIntoRetailerMapper::map($parseOrder->get('retailer'));
        $retailerLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseOrder->get('retailer')->get('location'));

        $order = ParseOrderIntoOrderMapper::map($parseOrder);
        $retailer = $retailer->setLocation($retailerLocation);
        $order = $order->setRetailer($retailer);
        $order = $order->setUser($user);
        return $order;
    }

    /**
     * @param Order $order
     * @return Order
     *
     * changes status to accepted by Retailer
     * also add history input in the OrderStatus table
     */
    public function changeStatusToAcceptedByRetailer(Order $order)
    {
        $parseOrderQuery = new ParseQuery('Order');
        $parseOrderQuery->includeKey('retailer');
        $parseOrderQuery->includeKey('retailer.location');
        $parseOrderQuery->includeKey('user');
        $parseOrderQuery->includeKey('sessionDevice');
        $parseOrderQuery->includeKey('sessionDevice.userDevice');
        $parseOrderQuery->equalTo('objectId', $order->getId());

        try {

            $parseOrder = $parseOrderQuery->find(false, true);
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 5", 1);
        }

        if(is_bool($parseOrder)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 5", 1);
        }

        $parseOrder = $parseOrder[0];

        // Order not found
        if (count_like_php5($parseOrder)==0) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 5", 1);
        }

        $isRetailerDualConfig = isRetailerDualConfig($parseOrder->get('retailer')->get('uniqueId'));

        if($isRetailerDualConfig) {

            orderStatusChange_ConfirmedByTablet($parseOrder);
        }
        else {

            orderStatusChange_ConfirmedByRetailer($parseOrder);
        }

        $parseOrder->save();

        // change also $order status - order is passed by reference
        if($isRetailerDualConfig) {

            $order->setStatus(Order::STATUS_ACCEPTED_ON_TABLET);
            $acceptedToBeginDelivery = false;
            $acceptedToBeginPickup = false;
        }
        else {

            $order->setStatus(Order::STATUS_ACCEPTED_BY_RETAILER);
            $acceptedToBeginDelivery = true;
            $acceptedToBeginPickup = true;
        }

        return [$order, $acceptedToBeginDelivery, $acceptedToBeginPickup];
    }

    /**
     * @param Order $order
     * @return Order
     *
     * changes status to pushed to Retailer
     * also add history input in the OrderStatus table
     */
    public function changeStatusToPushedToRetailer(Order $order)
    {
        $parseOrder = new ParseObject('Order', $order->getId());
        $parseOrder->fetch();
        orderStatusChange_PushedToRetailer($parseOrder);
        $parseOrder->save();
        // change also $order status - order is passed by reference
        $order->setStatus(Order::STATUS_PUSHED_TO_RETAILER);
        return $order;
    }

    /**
     * @param array $parseOrders
     * @return array
     *
     * it changed Parse Objects (Order Class) to Order Entities,
     */
    private function getOrdersListByParseOrdersList(array $parseOrders)
    {

        if (empty($parseOrders)) {
            return [];
        }

        $return = [];
        foreach ($parseOrders as $parseOrder) {
            $retailerLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseOrder->get('retailer')->get('location'));

            $retailer = ParseRetailerIntoRetailerMapper::map($parseOrder->get('retailer'));
            $retailer->setLocation($retailerLocation);

            if ($parseOrder->get('user')===null){
                $userLog = $parseOrder->get('user') !== null ? $parseOrder->get('user')->getObjectId() : ' --- ';
                logResponse(\json_encode('ORDER '.$parseOrder->getObjectId().' USER '.$userLog), false);
                logResponse(PHP_EOL, false);
                continue;
            }
            $user = ParseUserIntoUserMapper::map($parseOrder->get('user'));

            $order = ParseOrderIntoOrderMapper::map($parseOrder);
            $order->setUser($user);
            $order->setRetailer($retailer);

            $return[] = $order;
        }

        return $return;
    }

    /**
     * @param array $retailerIdList
     * @param array $statusCodes
     * @return int
     */
    private function getOrdersCountByRetailerIdListAndStatusCodes(array $retailerIdList, array $statusCodes)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrdersQuery = new ParseQuery('Order');
        $parseOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);
        $parseOrdersQuery->containedIn('status', $statusCodes);

        try {

            $count = $parseOrdersQuery->count();
        }
        catch (Exception $ex) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 8", 1);
        }

        if(is_bool($count)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 8", 1);
        }

        return $count;
    }

    /**
     * @param array $retailerIdList
     *
     * generate query to get active orders
     * active orders are:
     * for pickup: payment accepted, pushed to retailer, accepted by retailer
     * for delivery: payment accepted, pushed to retailer and accepted by retailer but only to the place
     * where order is picked up by delivery man
     * @return ParseQuery
     */
    private function createActiveOrderByRetailerIdListParseQuery(array $retailerIdList)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrdersQueryPickup = new ParseQuery('Order');
        $parseOrdersQueryPickup->equalTo('fullfillmentType', 'p');
        $parseOrdersQueryPickup->containedIn('status', [
            Order::STATUS_PAYMENT_ACCEPTED,
            Order::STATUS_PUSHED_TO_RETAILER,
            Order::STATUS_ACCEPTED_BY_RETAILER,
            Order::STATUS_ACCEPTED_ON_TABLET,
        ]);

        $parseOrdersQueryDeliveryBeforeAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryBeforeAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryBeforeAccept->containedIn('status', [
            Order::STATUS_PAYMENT_ACCEPTED,
            Order::STATUS_PUSHED_TO_RETAILER,
        ]);

        // for delivery orders after retailer confirm it is in active till it is not collected by delivery man
        $parseOrdersQueryDeliveryAfterAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryAfterAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryAfterAccept->containedIn('status', [
            Order::STATUS_ACCEPTED_BY_RETAILER,
            Order::STATUS_ACCEPTED_ON_TABLET,
        ]);
        $parseOrdersQueryDeliveryAfterAccept->notContainedIn('statusDelivery', OrderHelper::getOrderStatusDeliveryCompletedListByRetailerPerspective());

        $parseOrdersQueryDelivery = ParseQuery::orQueries([$parseOrdersQueryDeliveryBeforeAccept, $parseOrdersQueryDeliveryAfterAccept]);
        $parseOrdersQuery = ParseQuery::orQueries([$parseOrdersQueryPickup, $parseOrdersQueryDelivery]);
        $parseOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);

        return $parseOrdersQuery;
    }


    /**
     * @param array $retailerIdList
     *
     * generate query to get past orders
     * past orders are:
     * for pickup: canceled, completed
     * for delivery: canceled, completed and accepted by retailer with delivery status after picked up (including)
     * where order is picked up by delivery man
     * @return ParseQuery
     */
    private function createPastOrderByRetailerIdListParseQuery(array $retailerIdList)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrdersQueryPickup = new ParseQuery('Order');
        $parseOrdersQueryPickup->equalTo('fullfillmentType', 'p');
        $parseOrdersQueryPickup->containedIn('status', [
            Order::STATUS_CANCELED_BY_SYSTEM,
            Order::STATUS_CANCELED_BY_USER,
            Order::STATUS_COMPLETED,
        ]);

        $parseOrdersQueryDeliveryBeforeAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryBeforeAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryBeforeAccept->containedIn('status', [
            Order::STATUS_CANCELED_BY_SYSTEM,
            Order::STATUS_CANCELED_BY_USER,
            Order::STATUS_COMPLETED,
        ]);

        // for delivery orders after picked up by delivery man
        $parseOrdersQueryDeliveryAfterAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryAfterAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryAfterAccept->containedIn('status', [
            Order::STATUS_ACCEPTED_BY_RETAILER,
            Order::STATUS_ACCEPTED_ON_TABLET,
        ]);
        $parseOrdersQueryDeliveryAfterAccept->containedIn('statusDelivery', OrderHelper::getOrderStatusDeliveryCompletedListByRetailerPerspective());

        $parseOrdersQueryDelivery = ParseQuery::orQueries([$parseOrdersQueryDeliveryBeforeAccept, $parseOrdersQueryDeliveryAfterAccept]);
        $parseOrdersQuery = ParseQuery::orQueries([$parseOrdersQueryPickup, $parseOrdersQueryDelivery]);
        $parseOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);

        return $parseOrdersQuery;
    }
}
