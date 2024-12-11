<?php
namespace App\Delivery\Repositories;

use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderList;
use App\Delivery\Exceptions\Exception;
use App\Delivery\Exceptions\OrderNotFoundException;
use App\Delivery\Mappers\ParseAirportIntoAirportMapper;
use App\Delivery\Mappers\ParseOrderIntoOrderMapper;
use App\Delivery\Mappers\ParseRetailerIntoRetailerMapper;
use App\Delivery\Mappers\ParseTerminalGateMapIntoTerminalGateMapMapper;
use App\Delivery\Mappers\ParseUserIntoUserMapper;
use Parse\ParseQuery;

/**
 * Class OrderParseRepository
 * @package App\Tablet\Repositories
 */
class OrderParseRepository extends ParseRepository implements OrderRepositoryInterface
{
    public function getActiveOrdersCountByAirportIataCode(string $airportIataCode, int $page, int $limit): int
    {
        $count = 0;
        $parseOrdersQuery = $this->createActiveOrderListParseQuery($airportIataCode);

        try {
            $count = $parseOrdersQuery->count();
        } catch (\Exception $ex) {
            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 2", 1);
        }

        return $count;
    }

    public function getCompletedOrdersCountByAirportIataCode(string $airportIataCode, int $page, int $limit): int
    {
        $count = 0;
        $parseOrdersQuery = $this->createCompletedOrderListParseQuery($airportIataCode);

        try {
            $count = $parseOrdersQuery->count();
        } catch (\Exception $ex) {
            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 2", 1);
        }

        return $count;
    }

    public function getActiveOrdersByAirportIataCode(string $airportIataCode, int $page, int $limit): OrderList
    {
        $parseOrdersQuery = $this->createActiveOrderListParseQuery($airportIataCode);
        return $this->getOrdersByParseQuery($parseOrdersQuery, $page, $limit);
    }

    public function getCompletedOrdersByAirportIataCode(string $airportIataCode, int $page, int $limit): OrderList
    {
        $parseOrdersQuery = $this->createCompletedOrderListParseQuery($airportIataCode);
        return $this->getOrdersByParseQuery($parseOrdersQuery, $page, $limit);
    }

    public function getOrderByIdAndAirportIataCode(string $orderId, string $airportIataCode): Order
    {
        $retailerInnerQuery = new ParseQuery('Retailers');
        $retailerInnerQuery->equalTo('airportIataCode', $airportIataCode);

        $parseOrdersQuery = new ParseQuery('Order');
        $parseOrdersQuery->equalTo('objectId', $orderId);
        $parseOrdersQuery->includeKey('retailer');
        $parseOrdersQuery->includeKey('retailer.location');
        $parseOrdersQuery->includeKey('user');
        $parseOrdersQuery->includeKey('deliveryLocation');
        $parseOrdersQuery->matchesQuery('retailer', $retailerInnerQuery);
        $parseOrdersQuery->limit(1);
        $orders = $parseOrdersQuery->find(true);

        if (empty($orders)) {
            throw new OrderNotFoundException('Order not found for id ' . $orderId);
        }

        // enhancement and mapping
        $orders = $this->getOrdersListByParseOrdersList($orders);

        return $orders->getFirst();
    }


    private function createActiveOrderListParseQuery(string $airportIataCode): ParseQuery
    {
        $retailerInnerQuery = new ParseQuery('Retailers');
        $retailerInnerQuery->equalTo('airportIataCode', $airportIataCode);

        $parseOrdersQuery = new ParseQuery('Order');
        $parseOrdersQuery->equalTo('fullfillmentType', 'd');
        $parseOrdersQuery->containedIn('status', Order::STATUS_FOR_DELIVERY_ALL_LIST);
        $parseOrdersQuery->containedIn('statusDelivery', Order::STATUS_DELIVERY_ACTIVE_LIST);
        $parseOrdersQuery->matchesQuery('retailer', $retailerInnerQuery);
        $parseOrdersQuery->descending('createdAt');

        return $parseOrdersQuery;
    }


    private function createCompletedOrderListParseQuery(string $airportIataCode): ParseQuery
    {
        $retailerInnerQuery = new ParseQuery('Retailers');
        $retailerInnerQuery->equalTo('airportIataCode', $airportIataCode);

        $parseOrdersQuery = new ParseQuery('Order');
        $parseOrdersQuery->equalTo('fullfillmentType', 'd');
        $parseOrdersQuery->containedIn('status', Order::STATUS_FOR_DELIVERY_ALL_LIST);
        $parseOrdersQuery->containedIn('statusDelivery', Order::STATUS_DELIVERY_COMPLETED_LIST);
        $parseOrdersQuery->matchesQuery('retailer', $retailerInnerQuery);
        $parseOrdersQuery->descending('createdAt');

        return $parseOrdersQuery;
    }

    private function getOrdersByParseQuery(ParseQuery $parseOrdersQuery, int $page, int $limit)
    {
        $parseOrders = false;
        try {
            $parseOrdersQuery->ascending('submitTimestamp');

            $parseOrdersQuery->limit($limit);
            $parseOrdersQuery->skip(($page - 1) * $limit);

            $parseOrdersQuery->includeKey('retailer');
            $parseOrdersQuery->includeKey('retailer.location');
            $parseOrdersQuery->includeKey('user');
            $parseOrdersQuery->includeKey('deliveryLocation');

            $parseOrders = $parseOrdersQuery->find(false, true);
        } catch (\Exception $ex) {
            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 2", 1);
        }
        if (is_bool($parseOrders)) {
            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 2", 1);
        }


        return $this->getOrdersListByParseOrdersList($parseOrders);
    }

    private function getOrdersListByParseOrdersList(array $parseOrders): OrderList
    {
        $orderList = new OrderList();
        if (empty($parseOrders)) {
            return $orderList;
        }

        foreach ($parseOrders as $parseOrder) {
            $retailerLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseOrder->get('retailer')->get('location'));

            $retailer = ParseRetailerIntoRetailerMapper::map($parseOrder->get('retailer'));
            $retailer->setLocation($retailerLocation);
            $retailer->setAirport(ParseAirportIntoAirportMapper::map($this->getParseAirportByIataCode($retailer->getAirportIataCode())));

            if ($parseOrder->get('user') === null) {
                $userLog = $parseOrder->get('user') !== null ? $parseOrder->get('user')->getObjectId() : ' --- ';
                logResponse(\json_encode('ORDER ' . $parseOrder->getObjectId() . ' USER ' . $userLog), false);
                logResponse(PHP_EOL, false);
                continue;
            }
            $user = ParseUserIntoUserMapper::map($parseOrder->get('user'));

            $deliveryLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseOrder->get('deliveryLocation'));

            $order = ParseOrderIntoOrderMapper::map($parseOrder);
            $order->setUser($user);
            $order->setRetailer($retailer);
            $order->setDeliveryLocation($deliveryLocation);

            $orderList->addItem($order);
        }

        return $orderList;
    }

    private function getParseAirportByIataCode(string $iataCode)
    {

        $parseAirportQuery = new ParseQuery('Airports');
        $parseAirportQuery->equalTo('airportIataCode', $iataCode);
        $parseAirports = $parseAirportQuery->find();

        if (count($parseAirports) != 1) {
            throw new Exception('Airport with IataCode ' . $iataCode . ' not found');
        }

        return $parseAirports[0];
    }
}
