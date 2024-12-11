<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\OrderList;
use App\Delivery\Entities\OrderShortInfoList;

class OrderListIntoOrderShortInfoListMapper
{
    public static function map(OrderList $orderList): OrderShortInfoList
    {
        $orderShortInfoList = new OrderShortInfoList();

        foreach ($orderList as $order) {
            $orderShortInfoList->addItem(OrderIntoOrderShortInfoMapper::map($order));
        }

        return $orderShortInfoList;
    }
}
