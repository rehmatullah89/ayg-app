<?php

namespace App\Consumer\Responses;

class OrderApplyTipAsPercentageResponse extends ControllerResponse implements \JsonSerializable
{
    private $success;

    public function __construct(bool $status)
    {
        $this->success = $status;
    }

    public static function createSuccess(): OrderApplyTipAsPercentageResponse
    {
        return new OrderApplyTipAsPercentageResponse(true);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
