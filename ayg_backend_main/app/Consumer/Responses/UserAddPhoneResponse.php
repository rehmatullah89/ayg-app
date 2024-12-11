<?php

namespace App\Consumer\Responses;

use App\Consumer\Entities\UserPhone;

/**
 * Class UserAddPhoneResponse
 */
class UserAddPhoneResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var string
     */
    private $addedPhoneId;

    /**
     * OrderRateResponse constructor.
     * @param $addedPhoneId
     */
    public function __construct($addedPhoneId)
    {
        $this->addedPhoneId = $addedPhoneId;
    }

    /**
     * @param UserPhone $userPhone
     * @return UserAddPhoneResponse
     * @internal param $string
     */
    public static function createFromUserPhone(UserPhone $userPhone)
    {
        return new UserAddPhoneResponse($userPhone->getId());
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}