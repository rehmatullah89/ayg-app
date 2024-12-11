<?php
namespace App\Delivery\Entities;

class UserDevices extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $deviceId;
    /**
     * @var string
     */
    private $oneSignalId;
    

    public function __construct(
        string $deviceId,
        string $oneSignalId
    ) {
        $this->deviceId = $deviceId;
        $this->oneSignalId = $oneSignalId;
    }

    /**
     * @return mixed
     */
    public function getOneSignalId()
    {
        return $this->oneSignalId;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}