<?php

namespace App\Tablet\Responses;

use App\Tablet\Entities\OrderShortInfo;

/**
 * Class OrderConfirmResponse
 */
class OrderConfirmResponse extends ControllerResponse implements \JsonSerializable
{
    private $order;

    /**
     * UserSignInResponse constructor.
     * @param OrderShortInfo $orderShortInfo
     */
    public function __construct(OrderShortInfo $orderShortInfo)
    {
        $this->order = $orderShortInfo;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}