<?php

namespace App\Consumer\Responses;

/**
 * Class InfoHelloWorldResponse
 */
class OrderApplyForCreditResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $addedId;

    /**
     * OrderApplyForCreditResponse constructor.
     * @param $addedId
     */
    public function __construct($addedId)
    {
        $this->addedId = $addedId;
    }

    /**
     * @param $string
     * @return OrderApplyForCreditResponse
     */
    public static function createFromString($string)
    {
        return new OrderApplyForCreditResponse($string);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}