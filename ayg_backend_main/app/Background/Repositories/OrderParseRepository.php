<?php
namespace App\Background\Repositories;

use App\Background\Entities\Order;
use App\Background\Entities\OrderList;
use App\Background\Entities\Partners\Grab\OrderStatus;
use App\Background\Entities\Partners\Grab\OrderStatusList;
use Parse\ParseObject;
use Parse\ParseQuery;

class OrderParseRepository implements OrderRepositoryInterface
{
    public function getOpenPartnerOrderStatusList(string $partnerName): OrderStatusList
    {
        $orderStatusList = new OrderStatusList();

        $parseQuery = new ParseQuery('Order');
        $parseQuery->equalTo('partnerName', $partnerName);
        $parseOrders = $parseQuery->find();

        foreach ($parseOrders as $parseOrder) {
            $orderStatusList->addItem(new OrderStatus(
                $parseOrder->getObjectId(),
                $parseOrder->get('partnerOrderId'),
                null,
                null
            ));
        }
        return $orderStatusList;
    }


    public function getActiveOrdersListByRetailerIdList(array $retailerIdList): OrderList
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrdersQuery = new ParseQuery('Order');
        $parseOrdersQuery->containedIn('status', [
            Order::STATUS_PAYMENT_ACCEPTED,
            Order::STATUS_PUSHED_TO_RETAILER,
            Order::STATUS_ACCEPTED_BY_RETAILER,
            Order::STATUS_ACCEPTED_ON_TABLET,
        ]);
        $parseOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);

        $parseOrdersQuery->ascending('submitTimestamp');
        $parseOrdersQuery->limit(1000000);
        $parseOrdersQuery->includeKey('retailer');
        $parseOrdersQuery->includeKey('retailer.location');
        $parseOrdersQuery->includeKey('user');
        $parseOrders = $parseOrdersQuery->find(false, true);

        return OrderList::createFromParseObjectsArray($parseOrders);
    }


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

    public function changeStatusToAcceptedByRetailer(Order $order)
    {

        $parseOrderQuery = new ParseQuery('Order');
        $parseOrderQuery->includeKey('retailer');
        $parseOrderQuery->includeKey('user');
        $parseOrderQuery->includeKey('sessionDevice');
        $parseOrderQuery->includeKey('sessionDevice.userDevice');
        $parseOrderQuery->equalTo('objectId', $order->getId());
        $parseOrder = $parseOrderQuery->find(false, true);
        $parseOrder = $parseOrder[0];
        orderStatusChange_ConfirmedByRetailer($parseOrder);
        $parseOrder->save();
        $order->setStatus(Order::STATUS_ACCEPTED_BY_RETAILER);
        return $order;
    }
}
