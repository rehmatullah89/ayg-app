<?php
namespace App\Consumer\Entities;


class SessionDevice extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $objectId;
    /**
     * @var string
     */
    private $checkinTimestamp;
    /**
     * @var string
     */
    private $IPAddress;
    /**
     * @var UserDevice
     */
    private $userDevice;

    public function __construct(
        string $objectId,
        UserDevice $userDevice,
        string $IPAddress,
        ?string $checkinTimestamp
    ) {
        $this->objectId = $objectId;
        $this->userDevice = $userDevice;
        $this->IPAddress = $IPAddress;
        $this->checkinTimestamp = $checkinTimestamp;
    }

    /**
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getCheckinTimestamp(): string
    {
        return $this->checkinTimestamp;
    }

    /**
     * @return UserDevice
     */
    public function getUserDevice(): UserDevice
    {
        return $this->userDevice;
    }
    
    /**
     * @return null|string
     */
    public function getIPAddress(): ?string
    {
        return $this->IPAddress;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}
