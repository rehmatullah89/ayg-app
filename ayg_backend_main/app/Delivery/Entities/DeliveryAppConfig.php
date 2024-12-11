<?php
namespace App\Delivery\Entities;

class DeliveryAppConfig extends Entity implements \JsonSerializable
{
    /**
     * @var int
     */
    private $pingInterval;
    /**
     * @var string
     */
    private $notificationSoundUrl;
    /**
     * @var bool
     */
    private $notificationVibrateUsage;
    /**
     * @var int
     */
    private $batteryCheckInterval;


    public function __construct(
        $pingInterval,
        $notificationSoundUrl,
        $notificationVibrateUsage,
        $batteryCheckInterval
    )
    {
        $this->pingInterval = $pingInterval;
        $this->notificationSoundUrl = $notificationSoundUrl;
        $this->notificationVibrateUsage = $notificationVibrateUsage;
        $this->batteryCheckInterval = $batteryCheckInterval;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
