<?php

namespace App\Consumer\Responses;

class UserAddProfileDataResponse extends ControllerResponse implements \JsonSerializable
{
    private $success;

    public function __construct(bool $status)
    {
        $this->success = $status;
    }

    public static function createSuccess(): UserAddProfileDataResponse
    {
        return new UserAddProfileDataResponse(true);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
