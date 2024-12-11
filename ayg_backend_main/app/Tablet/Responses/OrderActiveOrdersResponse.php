<?php

namespace App\Tablet\Responses;

use App\Tablet\Entities\CloseEarlyData;
use App\Tablet\Entities\OrderShortInfo;

/**
 * Class OrderActiveOrdersResponse
 */
class OrderActiveOrdersResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var OrderShortInfo[]
     */
    private $ordersList;
    /**
     * @var CloseEarlyData
     */
    private $closeEarlyData;

    public function __construct(array $ordersList, CloseEarlyData $closeEarlyData)
    {
        $this->ordersList = $ordersList;
        $this->closeEarlyData = $closeEarlyData;
    }

    /**
     * @return OrderShortInfo[]
     */
    public function getOrdersList()
    {
        return $this->ordersList;
    }

    /**
     * @return CloseEarlyData
     */
    public function getCloseEarlyData()
    {
        return $this->closeEarlyData;
    }



    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}