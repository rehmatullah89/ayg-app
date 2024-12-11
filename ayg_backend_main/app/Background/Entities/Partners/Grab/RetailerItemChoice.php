<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemChoice extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $choiceDescription;
    /**
     * @var int
     */
    private $choiceID;
    /**
     * @var string
     */
    private $choiceCostDisplay;
    /**
     * @var int
     */
    private $choiceOrder;
    /**
     * @var string
     */
    private $productId;

    public function __construct(
        string $choiceDescription,
        int $choiceID,
        string $choiceCostDisplay,
        int $choiceOrder,
        string $productId
    ) {
        $this->choiceDescription = $choiceDescription;
        $this->choiceID = $choiceID;
        $this->choiceCostDisplay = $choiceCostDisplay;
        $this->choiceOrder = $choiceOrder;
        $this->productId = $productId;
    }

    // choicePOSName, - choiceDescription (in document it is choiceGroupName but that is the name of modifier not an choice_
    // choiceDisplayName,- choiceDescription
    // choiceDisplayDescription, dont have one - probably we need to add it manually if needed
    // choiceId, - choiceID
    // pricePerUnit, - choiceCostDisplay
    // priceLevelId, - dont have it
    // isActive, (higher level)
    // uniqueRetailerItemModifierId, - taken from above
    // uniqueId, - choiceId
    // choiceDisplaySequence - choiceOrder
    // version - none

    public static function createFromArray($item)
    {
        return new RetailerItemChoice(
            $item['choiceDescription'],
            (int)$item['choiceID'],
            $item['choiceCostDisplay'],
            (int)$item['choiceOrder'],
            $item['productID']
        );
    }

    /**
     * @return string
     */
    public function getChoiceDescription(): string
    {
        return $this->choiceDescription;
    }

    /**
     * @return int
     */
    public function getChoiceID(): int
    {
        return $this->choiceID;
    }

    /**
     * @return string
     */
    public function getChoiceCostDisplay(): string
    {
        return $this->choiceCostDisplay;
    }

    /**
     * @return int
     */
    public function getChoiceOrder(): int
    {
        return $this->choiceOrder;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getUniqueId(): string
    {
        return preg_replace("/[^A-Za-z0-9_]/", '',
            $this->getChoiceID() . '_' . str_replace(' ', '_', $this->getChoiceDescription()));
    }
}
