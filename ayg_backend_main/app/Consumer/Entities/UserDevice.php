<?php
namespace App\Consumer\Entities;


class UserDevice extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $objectId;
    /**
     * @var string
     */
    private $deviceType;
    /**
     * @var string
     */
    private $deviceId;
    /**
     * @var string
     */
    private $deviceModel;
    /**
     * @var string
     */
    private $appVersion;
    /**
     * @var string
     */
    private $deviceOS;
    
	 
    public function __construct(
        string $objectId,
        string $deviceType,
        string $deviceId,
        string $deviceModel,
        string $appVersion,
        string $deviceOS
    ) {
        $this->objectId = $objectId;
        $this->deviceType = $deviceType;
        $this->deviceId = $deviceId;
        $this->deviceModel = $deviceModel;
        $this->appVersion = $appVersion;
        $this->deviceOS = $deviceOS;
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
    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    /**
     * @return deviceId
     */
    public function getDeviceId(): string
    {
        return $this->deviceId;
    }
	
    /**
     * @return null|string
     */
    public function getDeviceModel(): ?string
    {
        return $this->deviceModel;
    }
    
    /**
     * @return null|string
     */
    public function getAppVersion(): ?string
    {
        return $this->appVersion;
    }

    /**
     * @return null|string
     */
    public function getDeviceOS(): ?string
    {
        return $this->deviceOS;
    }
    
    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}
