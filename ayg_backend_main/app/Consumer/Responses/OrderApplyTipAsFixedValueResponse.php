<?php

namespace App\Consumer\Responses;

class OrderApplyTipAsFixedValueResponse extends ControllerResponse implements \JsonSerializable
{
    private $success;

    public function __construct(bool $status)
    {
        $this->success = $status;
    }

    public static function createSuccess(): OrderApplyTipAsFixedValueResponse
    {
        return new OrderApplyTipAsFixedValueResponse(true);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
