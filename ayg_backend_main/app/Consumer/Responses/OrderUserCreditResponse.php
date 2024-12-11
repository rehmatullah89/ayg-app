<?php

namespace App\Consumer\Responses;

/**
 * Class InfoHelloWorldResponse
 */
class OrderUserCreditResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $addedId;

    /**
     * OrderUserCreditResponse constructor.
     * @param $addedId
     */
    public function __construct($addedId)
    {
        $this->addedId = $addedId;
    }

    /**
     * @param $string
     * @return OrderUserCreditResponse
     *
     *
     *
     * This method is called by route: POST /credit/request/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId
     * This method will return the response for requestForCredit in OrderController
     */
    public static function createFromString($string)
    {
        return new OrderUserCreditResponse($string);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}