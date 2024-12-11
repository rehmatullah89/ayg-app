<?php

namespace App\Delivery\Responses;

use App\Delivery\Entities\OrderShortInfoList;
use App\Delivery\Entities\OrderShortInfo;

/**
 * Class OrderActiveOrdersResponse
 */
class OrderActiveOrdersResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var OrderShortInfoList
     */
    private $ordersShortInfoList;

    public function __construct(OrderShortInfoList $ordersList)
    {
        $this->ordersShortInfoList = $ordersList;
    }

    public function getOrdersShortInfoList(): OrderShortInfoList
    {
        return $this->ordersShortInfoList;
    }

    public function jsonSerialize()
    {
        return $this->ordersShortInfoList->asArray();
    }
}
