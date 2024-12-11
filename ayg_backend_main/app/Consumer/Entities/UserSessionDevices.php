<?php
namespace App\Consumer\Entities;


class UserSessionDevices extends Entity implements \JsonSerializable
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
     * @var UserDevices
     */
    private $userDevice;
    
	 
    public function __construct(
        string $objectId,
        UserDevices $userDevice,
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
     * @param string
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
		return $this;
    }

    /**
     * @return string
     */
    public function getCheckinTimestamp(): string
    {
        return $this->checkinTimestamp;
    }

    /**
     * @param string
     */	
	public function setCheckinTimestamp($checkinTimestamp)
    {
        $this->checkinTimestamp = $checkinTimestamp;
		return $this;
    }

    /**
     * @return UserDevices
     */
    public function getUserDevice(): UserDevices
    {
        return $this->userDevice;
    }
	
	/**
     * @param UserDevices
     */
	public function setUserDevice(UserDevices $userDevice)
    {
        $this->userDevice = $userDevice;
		return $this;
    }

    
    /**
     * @return null|string
     */
    public function getIPAddress(): ?string
    {
        return $this->IPAddress;
    }

	/**
     * @param string
     */
    public function setIPAddress($IPAddress)
    {
       $this->IPAddress = $IPAddress;
	   return $this;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}
