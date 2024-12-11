<?php
namespace App\Tablet\Entities;

/**
 * Class CloseEarlyData
 * @package App\Tablet\Entities
 */
class CloseEarlyData extends Entity implements \JsonSerializable
{
    /**
     * @var
     */
    private $isCloseEarlyRequested;
    /**
     * @var
     */
    private $isClosedEarly;

    public function __construct($isCloseEarlyRequested, $isClosedEarly)
    {
        $this->isCloseEarlyRequested = $isCloseEarlyRequested;
        $this->isClosedEarly = $isClosedEarly;
    }

    /**
     * @return mixed
     */
    public function getIsCloseEarlyRequested()
    {
        return $this->isCloseEarlyRequested;
    }

    /**
     * @return mixed
     */
    public function getIsClosedEarly()
    {
        return $this->isClosedEarly;
    }

    

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}