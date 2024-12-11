<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

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

    public function __construct(
        int $inventoryItemID,
        int $inventoryItemSubID,
        string $inventoryItemSubName,
        float $cost
    ) {
        $this->inventoryItemID = $inventoryItemID;
        $this->inventoryItemSubID = $inventoryItemSubID;
        $this->inventoryItemSubName = $inventoryItemSubName;
        $this->cost = $cost;
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
}
