<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemInventorySub extends Entity implements \JsonSerializable
{

    /**
     * @var int
     */
    private $inventoryItemID;
    /**
     * @var int
     */
    private $inventoryItemSubID;
    /**
     * @var string
     */
    private $inventoryItemSubName;
    /**
     * @var float
     */
    private $cost;
    /**
     * @var RetailerItemCustomization|null
     */
    private $retailerItemCustomization;

    public function __construct(
        int $inventoryItemID,
        int $inventoryItemSubID,
        string $inventoryItemSubName,
        float $cost,
        ?RetailerItemCustomization $retailerItemCustomization
    ) {
        $this->inventoryItemID = $inventoryItemID;
        $this->inventoryItemSubID = $inventoryItemSubID;
        $this->inventoryItemSubName = $inventoryItemSubName;
        $this->cost = $cost;
        $this->retailerItemCustomization = $retailerItemCustomization;
    }

    /**
     * @return int
     */
    public function getInventoryItemID(): int
    {
        return $this->inventoryItemID;
    }

    /**
     * @return int
     */
    public function getInventoryItemSubID(): int
    {
        return $this->inventoryItemSubID;
    }

    /**
     * @return string
     */
    public function getInventoryItemSubName(): string
    {
        return $this->inventoryItemSubName;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }


    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function setRetailerItemCustomization(?RetailerItemCustomization $customization)
    {
        $this->retailerItemCustomization = $customization;
    }

    /**
     * @return RetailerItemCustomization|null
     */
    public function getRetailerItemCustomization()
    {
        return $this->retailerItemCustomization;
    }

}

