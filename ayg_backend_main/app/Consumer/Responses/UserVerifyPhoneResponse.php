<?php

namespace App\Consumer\Responses;

/**
 * Class UserVerifyPhoneResponse
 */
class UserVerifyPhoneResponse extends ControllerResponse implements \JsonSerializable
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
     * @param $string
     * @return UserVerifyPhoneResponse
     */
    public static function createFromString($string)
    {
        return new UserVerifyPhoneResponse($string);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}