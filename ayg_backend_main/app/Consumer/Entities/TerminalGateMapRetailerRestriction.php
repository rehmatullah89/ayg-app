<?php
namespace App\Consumer\Entities;

class TerminalGateMapRetailerRestriction extends Entity implements \JsonSerializable
{
    /**
     * @var
     */
    private $id;
    /**
     * @var string
     */
    private $uniqueId;
    /**
     * @var bool
     */
    private $isDeliveryLocationNotAvailable;
    /**
     * @var bool
     */
    private $isPickupLocationNotAvailable;

    public function __construct(
        $id,
        string $uniqueId,
        bool $isDeliveryLocationNotAvailable,
        bool $isPickupLocationNotAvailable
    ) {
        $this->id = $id;
        $this->uniqueId = $uniqueId;
        $this->isDeliveryLocationNotAvailable = $isDeliveryLocationNotAvailable;
        $this->isPickupLocationNotAvailable = $isPickupLocationNotAvailable;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @return bool
     */
    public function isIsDeliveryLocationNotAvailable(): bool
    {
        return $this->isDeliveryLocationNotAvailable;
    }

    /**
     * @return bool
     */
    public function isIsPickupLocationNotAvailable(): bool
    {
        return $this->isPickupLocationNotAvailable;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
