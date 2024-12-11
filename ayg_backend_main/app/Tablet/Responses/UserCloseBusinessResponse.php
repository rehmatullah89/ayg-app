<?php

namespace App\Tablet\Responses;

/**
 * Class UserCloseBusinessResponse
 */
class UserCloseBusinessResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var int
     */
    private $numberOfSecondsToClose;

    /**
     * UserCloseBusinessResponse constructor.
     * @param $numberOfSecondsToClose
     */
    public function __construct(
        $numberOfSecondsToClose
    )
    {
        $this->numberOfSecondsToClose = $numberOfSecondsToClose;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}