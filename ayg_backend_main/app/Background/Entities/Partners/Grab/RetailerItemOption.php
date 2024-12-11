<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemOption extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $optionDescription;
    /**
     * @var int
     */
    private $optionID;
    /**
     * @var string
     */
    private $optionCostDisplay;
    /**
     * @var int
     */
    private $optionOrder;
    /**
     * @var string
     */
    private $productID;

    public function __construct(
        string $optionDescription,
        int $optionID,
        string $optionCostDisplay,
        int $optionOrder,
        string $productID
    ) {
        $this->optionDescription = $optionDescription;
        $this->optionID = $optionID;
        $this->optionCostDisplay = $optionCostDisplay;
        $this->optionOrder = $optionOrder;
        $this->productID = $productID;
    }

    // optionPOSName, - optionDescription (in document it is optionGroupName but that is the name of modifier not an option_
    // optionDisplayName,- optionDescription
    // optionDisplayDescription, dont have one - probably we need to add it manually if needed
    // optionId, - optionID
    // pricePerUnit, - optionCostDisplay
    // priceLevelId, - dont have it
    // isActive, (higher level)
    // uniqueRetailerItemModifierId, - taken from above
    // uniqueId, - optionId
    // optionDisplaySequence - optionOrder
    // version - none

    public static function createFromArray($item)
    {
        return new RetailerItemOption(
            $item['optionDescription'],
            (int)$item['optionID'],
            $item['optionCostDisplay'],
            (int)$item['optionOrder'],
            $item['productID']
        );
    }

    /**
     * @return string
     */
    public function getOptionDescription(): string
    {
        return $this->optionDescription;
    }

    /**
     * @return int
     */
    public function getOptionID(): int
    {
        return $this->optionID;
    }

    /**
     * @return string
     */
    public function getOptionCostDisplay(): string
    {
        return $this->optionCostDisplay;
    }

    /**
     * @return int
     */
    public function getOptionOrder(): int
    {
        return $this->optionOrder;
    }

    /**
     * @return string
     */
    public function getProductID(): string
    {
        return $this->productID;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getUniqueId(): string
    {
        return preg_replace("/[^A-Za-z0-9_]/", '',
            $this->getOptionID() . '_' . str_replace(' ', '_', $this->getOptionDescription()));
    }
}
