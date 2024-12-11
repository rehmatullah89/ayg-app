<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

class RetailerItemOptionGroup extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $optionGroupName;
    /**
     * @var int
     */
    private $optionID;
    /**
     * @var bool
     */
    private $optionGroupAvailable;
    /**
     * @var RetailerItemOptionList
     */
    private $retailerItemOptionList;
    /**
     * @var string
     */
    private $optionSelection;

    public function __construct(
        string $optionGroupName,
        int $optionID,
        bool $optionGroupAvailable,
        RetailerItemOptionList $retailerItemOptionList,
        string $optionSelection
    ) {

        $this->optionGroupName = $optionGroupName;
        $this->optionID = $optionID;
        $this->optionGroupAvailable = $optionGroupAvailable;
        $this->retailerItemOptionList = $retailerItemOptionList;
        $this->optionSelection = $optionSelection;
    }

    public static function createFromArray(array $array)
    {
        // modifierPOSName, - optionGroupName
        // modifierDisplayName, - optionGroupName
        // modifierDisplayDescription, - empty maybe need to be added manually?
        // modifierId, - optionID
        // maxQuantity, - optionSelection we dont know exactly how it works yet
        // minQuantity, - optionSelection we dont know exactly how it works yet
        // isRequired, - required by choices, not by option
        // isActive, - optionGroupAvailable
        // uniqueRetailerItemId, - taken from "higher level"
        // uniqueId, - optionID (in document it is related with product Id, which is not exactly what we need)
        // modifierDisplaySequence, Mod list -> modOrder
        // version - not yet

        return new RetailerItemOptionGroup(
            $array['optionGroupName'],
            (int)$array['optionID'],
            (bool)$array['optionGroupAvailable'],
            RetailerItemOptionList::createFromArray($array['inventoryOptions']),
            (string) $array['optionSelection']
        );
    }

    /**
     * @return string
     */
    public function getOptionGroupName(): string
    {
        return $this->optionGroupName;
    }

    /**
     * @return int
     */
    public function getOptionID(): int
    {
        return $this->optionID;
    }

    /**
     * @return bool
     */
    public function isOptionGroupAvailable(): bool
    {
        return $this->optionGroupAvailable;
    }

    /**
     * @return RetailerItemOptionList
     */
    public function getRetailerItemOptionList(): RetailerItemOptionList
    {
        return $this->retailerItemOptionList;
    }

    /**
     * @return string
     */
    public function getOptionSelection(): string
    {
        return $this->optionSelection;
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
