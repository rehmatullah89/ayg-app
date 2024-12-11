<?php

namespace App\Consumer\Responses;

class UserSignInByPhoneResponse extends ControllerResponse implements \JsonSerializable
{
    private $success;

    public function __construct(bool $status)
    {
        $this->success = $status;
    }

    public static function createSuccess(): UserSignInByPhoneResponse
    {
        return new UserSignInByPhoneResponse(true);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
