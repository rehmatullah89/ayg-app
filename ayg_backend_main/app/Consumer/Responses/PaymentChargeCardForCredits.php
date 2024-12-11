<?php

namespace App\Consumer\Responses;

class PaymentChargeCardForCredits extends ControllerResponse implements \JsonSerializable
{
    private $success;

    public function __construct(bool $status)
    {
        $this->success = $status;
    }

    public static function createSuccess(): PaymentChargeCardForCredits
    {
        return new PaymentChargeCardForCredits(true);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
