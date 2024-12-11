<?php

namespace App\Delivery\Responses;

class OrderChangeDeliveryStatusResponse extends ControllerResponse implements \JsonSerializable
{
    private $status;

    public function __construct(
        $status
    ) {
        $this->status = $status;
    }


    public static function createFromBool($bool)
    {
        return new OrderChangeDeliveryStatusResponse($bool);
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
