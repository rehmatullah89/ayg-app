<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

class RetailerStatus extends Entity
{
    /**
     * @var string
     */
    private $waypointID;
    /**
     * @var bool
     */
    private $bPickupEnabled;
    /**
     * @var bool
     */
    private $bStoreDelivery;
    /**
     * @var bool
     */
    private $bStoreIsCurrentlyOpen;

    public function __construct(
        string $waypointID,
        bool $bPickupEnabled,
        bool $bStoreDelivery,
        bool $bStoreIsCurrentlyOpen
    ) {

        $this->waypointID = $waypointID;
        $this->bPickupEnabled = $bPickupEnabled;
        $this->bStoreDelivery = $bStoreDelivery;
        $this->bStoreIsCurrentlyOpen = $bStoreIsCurrentlyOpen;
    }

    /**
     * @return string
     */
    public function getWaypointID(): string
    {
        return $this->waypointID;
    }

    /**
     * @return bool
     */
    public function isBPickupEnabled(): bool
    {
        return $this->bPickupEnabled;
    }

    /**
     * @return bool
     */
    public function isBStoreDelivery(): bool
    {
        return $this->bStoreDelivery;
    }

    /**
     * @return bool
     */
    public function isBStoreIsCurrentlyOpen(): bool
    {
        return $this->bStoreIsCurrentlyOpen;
    }

    public function getUniqueId()
    {
        return Retailer::getUniqueId($this->getWaypointID());
    }
}
