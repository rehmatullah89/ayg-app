<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderDeliveryStatus;
use App\Delivery\Entities\OrderDetailed;
use App\Delivery\Entities\User;
use App\Delivery\Mappers\ParseUserIntoUserMapper;
use App\Delivery\Mappers\UserIntoUserShortInfoMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

class OrderDeliveryStatusParseRepository implements OrderDeliveryStatusRepositoryInterface
{
    public function changeDeliveryStatus(
        OrderDetailed $order,
        OrderDeliveryStatus $fromDeliveryStatus,
        OrderDeliveryStatus $toDeliveryStatus,
        User $user,
        bool $completeOrder
    ): bool {
        $parseOrder = new ParseObject('Order', $order->getOrderId());
        $parseOrder->set('statusDelivery', $toDeliveryStatus->getId());

        $parseDeliveryUser = new ParseObject('_User', $user->getId());
        $parseOrderDeliveryStatus = new ParseObject('OrderDeliveryStatus');
        $parseOrderDeliveryStatus->set('order', $parseOrder);
        $parseOrderDeliveryStatus->set('deliveryUser', $parseDeliveryUser);
        $parseOrderDeliveryStatus->set('oldDeliveryStatusName', $fromDeliveryStatus->getName());
        $parseOrderDeliveryStatus->set('newDeliveryStatusName', $toDeliveryStatus->getName());
        $parseOrderDeliveryStatus->save();

        if ($completeOrder) {
            $parseOrder->set('status', Order::STATUS_COMPLETED);
        }

        $parseOrder->save();

        return true;
    }


    public function getDeliveryUserByOrderDetailed(
        OrderDetailed $order
    ): ?User
    {
        $orderInnerQuery = new ParseQuery('Order');
        $orderInnerQuery->equalTo('objectId', $order->getOrderId());

        $parseOrderDeliveryStatusQuery = new ParseQuery('OrderDeliveryStatus');
        $parseOrderDeliveryStatusQuery->matchesQuery('order', $orderInnerQuery);
        $parseOrderDeliveryStatusQuery->includeKey('deliveryUser');
        $parseOrderDeliveryStatusQuery->equalTo('newDeliveryStatusName', $order->getOrderDeliveryStatus()->getName());
        $parseOrderDeliveryStatusQuery->descending('createdAt');
        $parseOrderDeliveryStatusQuery->limit(1);

        $parseOrderDeliveryStatus = $parseOrderDeliveryStatusQuery->find();

        if (count($parseOrderDeliveryStatus) !== 1) {
            return null;
        }

        return ParseUserIntoUserMapper::map($parseOrderDeliveryStatus[0]->get('deliveryUser'));
    }
}
