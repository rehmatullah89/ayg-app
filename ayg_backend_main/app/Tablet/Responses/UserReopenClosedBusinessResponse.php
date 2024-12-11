<?php

namespace App\Tablet\Responses;

/**
 * Class UserReopenClosedBusinessResponse
 */
class UserReopenClosedBusinessResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $status;

    /**
     * UserReopenClosedBusinessResponse constructor.
     * @param $status
     */
    public function __construct(
        $status
    )
    {
        $this->status = $status;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}