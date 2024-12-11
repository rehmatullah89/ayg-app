<?php

namespace App\Consumer\Responses;

/**
 * Class OrderRateResponse
 */
class OrderRateResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $status;

    /**
     * OrderRateResponse constructor.
     * @param $status
     */
    public function __construct($status)
    {
        $this->status = $status;
    }

    /**
     * @param $bool
     * @return OrderRateResponse
     */
    public static function createFromBool($bool)
    {
        return new OrderRateResponse($bool);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}