<?php

namespace App\Tablet\Responses;

use App\Tablet\Entities\OrderShortInfo;


/**
 * Class OrderRequestHelpResponse
 */
class OrderRequestHelpResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var OrderShortInfo
     */
    private $order;

    /**
     * UserSignInResponse constructor.
     * @param $order
     */
    public function __construct(OrderShortInfo $order)
    {
        $this->order = $order;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}