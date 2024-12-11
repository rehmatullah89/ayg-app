<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemChoiceGroup extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $choiceGroupName;
    /**
     * @var int
     */
    private $choiceID;
    /**
     * @var bool
     */
    private $choiceGroupAvailable;
    /**
     * @var RetailerItemChoiceList
     */
    private $retailerItemChoiceList;

    public function __construct(
        string $choiceGroupName,
        int $choiceID,
        bool $choiceGroupAvailable,
        RetailerItemChoiceList $retailerItemChoiceList
    ) {

        $this->choiceGroupName = $choiceGroupName;
        $this->choiceID = $choiceID;
        $this->choiceGroupAvailable = $choiceGroupAvailable;
        $this->retailerItemChoiceList = $retailerItemChoiceList;
    }

    public static function createFromArray(array $array)
    {
        // modifierPOSName, - choiceGroupName
        // modifierDisplayName, - choiceGroupName
        // modifierDisplayDescription, - empty maybe need to be added manually?
        // modifierId, - choiceID
        // maxQuantity, - choiceSelection we dont know exactly how it works yet
        // minQuantity, - choiceSelection we dont know exactly how it works yet
        // isRequired, - required by choices, not by choice
        // isActive, - choiceGroupAvailable
        // uniqueRetailerItemId, - taken from "higher level"
        // uniqueId, - choiceID (in document it is related with product Id, which is not exactly what we need)
        // modifierDisplaySequence, Mod list -> modOrder
        // version - not yet

        return new RetailerItemChoiceGroup(
            $array['choiceGroupName'],
            (int)$array['choiceID'],
            (bool)$array['choiceGroupAvailable'],
            RetailerItemChoiceList::createFromArray($array['inventoryChoices'])
        );
    }

    /**
     * @return string
     */
    public function getChoiceGroupName(): string
    {
        return $this->choiceGroupName;
    }

    /**
     * @return int
     */
    public function getChoiceID(): int
    {
        return $this->choiceID;
    }

    /**
     * @return bool
     */
    public function isChoiceGroupAvailable(): bool
    {
        return $this->choiceGroupAvailable;
    }

    /**
     * @return RetailerItemChoiceList
     */
    public function getRetailerItemChoiceList(): RetailerItemChoiceList
    {
        return $this->retailerItemChoiceList;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
