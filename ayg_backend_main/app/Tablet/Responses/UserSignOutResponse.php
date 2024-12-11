<?php

namespace App\Tablet\Responses;
/**
 * Class UserSignOutResponse
 */
class UserSignOutResponse extends ControllerResponse implements \JsonSerializable
{
    private $status;

    /**
     * UserSignOutResponse constructor.
     * @param $status
     */
    public function __construct(
        $status
    )
    {
        $this->status = $status;
    }


    /**
     * @param $bool
     * @return UserSignOutResponse
     */
    public static function createFromBool($bool)
    {
        return new UserSignOutResponse($bool);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}