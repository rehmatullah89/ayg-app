<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

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
    /**
     * @var string
     */
    private $choiceSelection;

    public function __construct(
        string $choiceGroupName,
        int $choiceID,
        bool $choiceGroupAvailable,
        RetailerItemChoiceList $retailerItemChoiceList,
        string $choiceSelection
    ) {

        $this->choiceGroupName = $choiceGroupName;
        $this->choiceID = $choiceID;
        $this->choiceGroupAvailable = $choiceGroupAvailable;
        $this->retailerItemChoiceList = $retailerItemChoiceList;
        $this->choiceSelection = $choiceSelection;
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
            RetailerItemChoiceList::createFromArray($array['inventoryChoices']),
            (string)$array['choiceSelection']
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

    /**
     * @return string
     */
    public function getChoiceSelection(): string
    {
        return $this->choiceSelection;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}
