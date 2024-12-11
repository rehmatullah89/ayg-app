<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItem extends Entity implements \JsonSerializable
{

    const UNIQUE_ID_PREFIX = 'grab_';
    /**
     * @var RetailerItemInventoryTitleList
     */
    private $retailerItemInventoryTitleList;
    /**
     * @var string
     */
    private $inventoryItemName;
    /**
     * @var string
     */
    private $inventoryItemDescription;
    /**
     * @var string
     */
    private $inventoryItemID;
    /**
     * @var string
     */
    private $costDisplay;
    /**
     * @var bool
     */
    private $bRetailInStock;
    /**
     * @var bool
     */
    private $bRetailPurchasable;
    /**
     * @var bool
     */
    private $inventoryItemAvailable;
    /**
     * @var bool
     */
    private $inventoryItemAvailableAndInsideTimeWindow;
    /**
     * @var string
     */
    private $inventoryItemImageName;
    /**
     * @var int
     */
    private $inventoryOrder;
    /**
     * @var float
     */
    private $taxRate;
    /**
     * @var string
     */
    private $startTimeLocalString;
    /**
     * @var string
     */
    private $endTimeLocalString;
    /**
     * @var RetailerItemOptionGroupList
     */
    private $retailerItemOptionGroupList;
    /**
     * @var RetailerItemChoiceGroupList
     */
    private $retailerItemChoiceGroupList;
    /**
     * @var \DateTimeZone
     */
    private $dateTimeZone;
    /**
     * @var RetailerItemInventorySubList
     */
    private $retailerItemInventorySubList;

    public function __construct(
        RetailerItemInventoryTitleList $retailerItemInventoryTitleList,
        string $inventoryItemName,
        RetailerItemInventorySubList $retailerItemInventorySubList,
        string $inventoryItemDescription,
        string $inventoryItemID,
        string $costDisplay,
        bool $bRetailInStock,
        bool $bRetailPurchasable,
        bool $inventoryItemAvailable,
        bool $inventoryItemAvailableAndInsideTimeWindow,
        string $inventoryItemImageName,
        int $inventoryOrder,
        float $taxRate,
        string $startTimeLocalString,
        string $endTimeLocalString,
        RetailerItemOptionGroupList $retailerItemOptionGroupList,
        RetailerItemChoiceGroupList $retailerItemChoiceGroupList,
        \DateTimeZone $dateTimeZone
    ) {
        $this->retailerItemInventoryTitleList = $retailerItemInventoryTitleList;
        $this->inventoryItemName = $inventoryItemName;
        $this->inventoryItemDescription = $inventoryItemDescription;
        $this->inventoryItemID = $inventoryItemID;
        $this->costDisplay = $costDisplay;
        $this->bRetailInStock = $bRetailInStock;
        $this->bRetailPurchasable = $bRetailPurchasable;
        $this->inventoryItemAvailable = $inventoryItemAvailable;
        $this->inventoryItemAvailableAndInsideTimeWindow = $inventoryItemAvailableAndInsideTimeWindow;
        $this->inventoryItemImageName = $inventoryItemImageName;
        $this->inventoryOrder = $inventoryOrder;
        $this->taxRate = $taxRate;
        $this->startTimeLocalString = $startTimeLocalString;
        $this->endTimeLocalString = $endTimeLocalString;
        $this->retailerItemOptionGroupList = $retailerItemOptionGroupList;
        $this->retailerItemChoiceGroupList = $retailerItemChoiceGroupList;
        $this->dateTimeZone = $dateTimeZone;


        $this->getStartTime();
        $this->retailerItemInventorySubList = $retailerItemInventorySubList;
    }

    /**
     * @return RetailerItemInventorySubList
     */
    public function getRetailerItemInventorySubList(): RetailerItemInventorySubList
    {
        return $this->retailerItemInventorySubList;
    }

    public static function createFromArray(array $array, \DateTimeZone $dateTimeZone)
    {

        // itemCategoryName, - inventoryTitles -> first -> inventoryTitleDescription
        //itemSecondCategoryName - we usually skipp it, in document it is empty
        //itemThirdCategoryName, - we usually skipp it, in document it is inventoryItemAttributeTypes
        //itemPOSName, - inventoryItemName
        //itemDisplayName, - inventoryItemName
        //itemDisplayDescription, - inventoryItemDescription
        //itemId, - inventoryItemID
        //itemPrice, - in document it is costDisplay, but it should be cost multiplied by 100 (or with removed dot)
        //priceLevelId, - need to be added manually
        //isActive, - bRetailInStock, bRetailPurchasable, inventoryItemAvailable
        //uniqueRetailerId, -
        //uniqueId, - inventoryItemID
        //itemImageURL - inventoryItemImageName
        //itemTags, - in docuument inventoryItemAttributeTypes -> list -> inventoryItemAttributeDescription, but it is related with retailer, not item so skipped or manual
        //itemDisplaySequence, - inventoryOrder
        //taxCategory, - taxRate (but might not be the same) we usually leave it blank
        //allowedThruSecurity - manual
        //version - blank

        return new RetailerItem(
            RetailerItemInventoryTitleList::createFromArray($array['inventoryTitles']),
            $array['inventoryItemName'],
            RetailerItemInventorySubList::createFromArray($array['inventoryItemSubs']),
            $array['inventoryItemDescription'],
            $array['inventoryItemID'],
            $array['costDisplay'],
            (bool)$array['bRetailInStock'],
            (bool)$array['bRetailPurchasable'],
            (bool)$array['inventoryItemAvailable'],
            (bool)$array['inventoryItemAvailableAndInsideTimeWindow'],
            (string)trim($array['inventoryItemImageName'], '/'),
            (int)$array['inventoryOrder'],
            (float)$array['taxRate'],
            (string)$array['startTimeLocalString'],
            (string)$array['endTimeLocalString'],
            RetailerItemOptionGroupList::createFromArray(
                isset($array['inventoryMainOptionChoice']['options']) ? $array['inventoryMainOptionChoice']['options'] : null
            ),
            RetailerItemChoiceGroupList::createFromArray(
                isset($array['inventoryMainOptionChoice']['choices']) ? $array['inventoryMainOptionChoice']['choices'] : null
            ),
            $dateTimeZone,
            null
        );
    }

    public function asArrayForCsvLine(RetailerItemInventorySub $selectedSub)
    {
        //inventoryTitles -> first -> inventoryTitleDescription
        $array['itemCategoryName'] = (string)$this->getRetailerItemInventoryTitleList()->getFirst()->getInventoryTitleDescription(true);

        //we usually skipp it, in document it is empty
        $array['itemSecondCategoryName'] = '';

        // we usually skipp it, in document it is inventoryItemAttributeTypes
        $array['itemThirdCategoryName'] = '';

        // inventoryItemName
        //$array['itemPOSName'] = $this->getInventoryItemName();
        $array['itemPOSName'] = $this->getInventoryItemNameWithSubInfo($selectedSub);

        // -inventoryItemName
        //$array['itemDisplayName'] = $this->getInventoryItemName();
        $array['itemDisplayName'] = $this->getInventoryItemNameWithSubInfo($selectedSub);

        // -inventoryItemDescription
        $array['itemDisplayDescription'] = $this->getInventoryItemDescription();

        // -inventoryItemID
        // @todo - check if prefix is fine
        $array['itemId'] = $this->getUniqueId($selectedSub);

        /*
        // -in document it is costDisplay, but it should be cost multiplied by 100 (or with removed dot)
        // get the first one
        $priceDisplay = $this->getCostDisplay();
        $priceDisplay = explode(' ', $priceDisplay);
        $priceDisplay = $priceDisplay[0];
        $priceDisplay = explode('-', $priceDisplay);
        $priceDisplay = $priceDisplay[0];
*/

        //$array['itemPrice'] = intval(trim($priceDisplay, '$') * 100);
        $array['itemPrice'] = intval(round($selectedSub->getCost() * 100));

        // -need to be added manually
        // @todo manual work
        $array['priceLevelId'] = '1';

        // bRetailInStock, bRetailPurchasable, inventoryItemAvailable
        $grabIsActive = $this->isActive() == 1 ? 'Y' : 'N';

        if ($selectedSub->getRetailerItemCustomization() == null) {
            $array['isActive'] = $grabIsActive;
        } else {
            $array['isActive'] = $selectedSub->getRetailerItemCustomization()->isIsActive() == false ? 'N' : $grabIsActive;
        }

        // @todo - get it from Json
        $array['uniqueRetailerId'] = 'unique_retailer_id';

        // inventoryItemID
        // @todo - check if prefix is fine
        $array['uniqueId'] = $this->getUniqueId($selectedSub);

        // inventoryItemImageName
        $array['itemImageURL'] = $this->getInventoryItemImageName();

        // -in docuument inventoryItemAttributeTypes -> list -> inventoryItemAttributeDescription, but it is related with retailer, not item so skipped or manual
        $array['itemTags'] = '';

        // inventoryOrder
        if ($selectedSub->getRetailerItemCustomization() == null) {
            $array['itemDisplaySequence'] = $this->getInventoryOrder();
        } else {
            $array['itemDisplaySequence'] = $selectedSub->getRetailerItemCustomization()->getItemDisplaySequence() == 0 ? $this->getInventoryOrder() : $selectedSub->getRetailerItemCustomization()->getItemDisplaySequence();
        }

        // , -taxRate(but might not be the same) we usually leave it blank
        $array['taxCategory'] = '';

        // - manual

        if ($selectedSub->getRetailerItemCustomization() == null) {
            $array['allowedThruSecurity'] = 'Y';
        } else {
            $array['allowedThruSecurity'] = $selectedSub->getRetailerItemCustomization()->isAllowedThruSecurity() ? 'Y' : 'N';
        }

        // -blank
        $array['version'] = '';

        return array_values($array);
    }

    private function isActive()
    {
        if (!$this->isBRetailInStock()) {
            return false;
        }

        if (!$this->isBRetailPurchasable()) {
            return false;
        }

        if (!$this->isInventoryItemAvailable()) {
            return false;
        }
        /*
                if (!$this->isInventoryItemAvailableAndInsideTimeWindow()) {
                    return false;
                }
        */
        return true;
    }

    /**
     * @return RetailerItemInventoryTitleList
     */
    public function getRetailerItemInventoryTitleList(): RetailerItemInventoryTitleList
    {
        return $this->retailerItemInventoryTitleList;
    }

    /**
     * @return string
     */
    public function getInventoryItemName(): string
    {
        return $this->inventoryItemName;
    }

    public function getInventoryItemNameWithSubInfo(RetailerItemInventorySub $selectedSub): string
    {
        if (count($this->getRetailerItemInventorySubList()) == 1) {
            return $this->inventoryItemName;
        }
        return $this->inventoryItemName . ' ' . $selectedSub->getInventoryItemSubName();
    }

    public function getSubById(int $subId):?RetailerItemInventorySub
    {
        /** @var RetailerItemInventorySub $retailerItemInventorySub */
        foreach ($this->getRetailerItemInventorySubList() as $retailerItemInventorySub) {
            if ($retailerItemInventorySub->getInventoryItemSubID() == $subId) {
                return $retailerItemInventorySub;
            }
        }
        return null;
    }

    /**
     * @return string
     */
    public function getInventoryItemDescription(): string
    {
        return $this->inventoryItemDescription;
    }

    /**
     * @return string
     */
    public function getInventoryItemID(): string
    {
        return $this->inventoryItemID;
    }

    /**
     * @return string
     */
    public function getCostDisplay(): string
    {
        return $this->costDisplay;
    }

    /**
     * @return bool
     */
    public function isBRetailInStock(): bool
    {
        return $this->bRetailInStock;
    }

    /**
     * @return bool
     */
    public function isBRetailPurchasable(): bool
    {
        return $this->bRetailPurchasable;
    }

    /**
     * @return bool
     */
    public function isInventoryItemAvailable(): bool
    {
        return $this->inventoryItemAvailable;
    }

    /**
     * @return bool
     */
    public function isInventoryItemAvailableAndInsideTimeWindow(): bool
    {
        return $this->inventoryItemAvailableAndInsideTimeWindow;
    }

    /**
     * @return string
     */
    public function getInventoryItemImageName(): string
    {
        return $this->inventoryItemImageName;
    }

    /**
     * @return int
     */
    public function getInventoryOrder(): int
    {
        return $this->inventoryOrder;
    }

    /**
     * @return string
     */
    public function getStartTimeLocalString(): string
    {
        return $this->startTimeLocalString;
    }

    /**
     * @return string
     */
    public function getEndTimeLocalString(): string
    {
        return $this->endTimeLocalString;
    }

    /**
     * @return \DateTime
     */
    public function getStartTime(): \DateTime
    {
        return \DateTime::createFromFormat('m/d/Y H:i A', $this->getStartTimeLocalString(), $this->getDateTimeZone());
    }

    /**
     * @return \DateTime
     */
    public function getEndTime(): \DateTime
    {
        return \DateTime::createFromFormat('m/d/Y H:i A', $this->getEndTimeLocalString(), $this->getDateTimeZone());
    }

    /**
     * @return RetailerItemOptionGroupList
     */
    public function getRetailerItemOptionGroupList(): RetailerItemOptionGroupList
    {
        return $this->retailerItemOptionGroupList;
    }

    /**
     * @return RetailerItemChoiceGroupList
     */
    public function getRetailerItemChoiceGroupList(): RetailerItemChoiceGroupList
    {
        return $this->retailerItemChoiceGroupList;
    }

    /**
     * @return float
     */
    public function getTaxRate(): float
    {
        return $this->taxRate;
    }

    /**
     * @return \DateTimeZone
     */
    public function getDateTimeZone(): \DateTimeZone
    {
        return $this->dateTimeZone;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function asArrayForCsvLineItemTimes($selectedSub)
    {
        $array["uniqueRetailerItemId"] = $this->getUniqueId($selectedSub);
        $array["dayOfWeek"] = $this->getStartTime()->format('N');
        $array["restrictOrderTimes"] = $this->getStartTime()->format("h:i A") . ' - ' . $this->getEndTime()->format("h:i A");
        $array["prepRestrictTimesGroup1"] = "";
        $array["prepTimeCategoryIdGroup1"] = "";
        $array["prepRestrictTimesGroup2"] = "";
        $array["prepTimeCategoryIdGroup2"] = "";
        $array["prepRestrictTimesGroup3"] = "";
        $array["prepTimeCategoryIdGroup3"] = "";
        $array["isActive"] = "Y";

        return array_values($array);
    }

    public function asArrayForCsvLineItemCustom(RetailerItemInventorySub $selectedSub)
    {
        $array["itemPOSName"] = $this->getInventoryItemNameWithSubInfo($selectedSub);
        $array["uniqueId"] = $this->getUniqueId($selectedSub);
        $array["isActive"] = "";
        $array["itemDisplaySequence"] = "";
        $array["allowedThruSecurity"] = "";
        $array["verified"] = "";

        return array_values($array);
    }

    public function asArrayForCsvLineModifiersFromChoices(RetailerItemInventorySub $selectedSub)
    {
        $array = [];
        $i = 0;
        $retailerItemChoiceGroupList = $this->getRetailerItemChoiceGroupList();
        /** @var RetailerItemChoiceGroup $choiceGroup */
        foreach ($retailerItemChoiceGroupList as $choiceGroup) {
            $array[$i]["modifierPOSName"] = $choiceGroup->getChoiceGroupName();
            $array[$i]["modifierDisplayName"] = $choiceGroup->getChoiceGroupName();
            $array[$i]["modifierDisplayDescription"] = "";
            $array[$i]["modifierId"] = $this->getUniqueId($selectedSub) . '_mod_from_choice_' . $choiceGroup->getChoiceID();
            $array[$i]["maxQuantity,"] = "1";
            $array[$i]["minQuantity"] = "1";
            $array[$i]["isRequired"] = "Y";
            $array[$i]["isActive"] = "Y";
            $array[$i]["uniqueRetailerItemId"] = $this->getUniqueId($selectedSub);
            $array[$i]["uniqueId"] = $this->getUniqueId($selectedSub) . '_mod_from_choice_' . $choiceGroup->getChoiceID();
            $array[$i]["modifierDisplaySequence"] = "";
            $array[$i]["version"] = "";
            $array[$i] = array_values($array[$i]);
            $i++;
        }

        return $array;
    }

    public function asArrayForCsvLineModifiersFromOptions(RetailerItemInventorySub $selectedSub)
    {
        $array = [];
        $i = 0;
        $retailerItemOptionGroupList = $this->getRetailerItemOptionGroupList();
        /** @var RetailerItemOptionGroup $optionGroup */
        foreach ($retailerItemOptionGroupList as $optionGroup) {
            $array[$i]["modifierPOSName"] = $optionGroup->getOptionGroupName();
            $array[$i]["modifierDisplayName"] = $optionGroup->getOptionGroupName();
            $array[$i]["modifierDisplayDescription"] = "";
            $array[$i]["modifierId"] = $this->getUniqueId($selectedSub) . '_mod_from_option_' . $optionGroup->getOptionID();
            $array[$i]["maxQuantity,"] = "0";
            $array[$i]["minQuantity"] = "0";
            $array[$i]["isRequired"] = "N";
            $array[$i]["isActive"] = "Y";
            $array[$i]["uniqueRetailerItemId"] = $this->getUniqueId($selectedSub);
            $array[$i]["uniqueId"] = $this->getUniqueId($selectedSub) . '_mod_from_option_' . $optionGroup->getOptionID();
            $array[$i]["modifierDisplaySequence"] = "";
            $array[$i]["version"] = "";
            $array[$i] = array_values($array[$i]);
            $i++;
        }
        return $array;
    }

    public function asArrayForCsvLineModifierOptionsFromChoices(RetailerItemInventorySub $selectedSub)
    {
        $array = [];
        $i = 0;
        $retailerItemChoiceGroupList = $this->getRetailerItemChoiceGroupList();
        /** @var RetailerItemChoiceGroup $choiceGroup */
        foreach ($retailerItemChoiceGroupList as $choiceGroup) {
            /** @var RetailerItemChoice $choice */
            foreach ($choiceGroup->getRetailerItemChoiceList() as $choice) {

                $array[$i]["optionPOSName"] = $choice->getChoiceDescription();
                $array[$i]["optionDisplayName"] = $choice->getChoiceDescription();
                $array[$i]["optionDisplayDescription"] = '';
                $array[$i]["optionId"] = $this->getUniqueId($selectedSub) . '_mod_from_choice_' . $choiceGroup->getChoiceID() . '_' . $choice->getUniqueId();
                $array[$i]["pricePerUnit"] = intval(round(floatval(trim($choice->getChoiceCostDisplay(), '$')) * 100));
                $array[$i]["priceLevelId"] = '';
                $array[$i]["isActive"] = 'Y';
                $array[$i]["uniqueRetailerItemModifierId"] = $this->getUniqueId($selectedSub) . '_mod_from_choice_' . $choiceGroup->getChoiceID();
                $array[$i]["uniqueId"] = $this->getUniqueId($selectedSub) . '_mod_from_choice_' . $choiceGroup->getChoiceID() . '_' . $choice->getUniqueId();
                $array[$i]["optionDisplaySequence"] = '';
                $array[$i]["version"] = '';

                $array[$i] = array_values($array[$i]);
                $i++;
            }
        }

        return $array;
    }

    public function asArrayForCsvLineModifierOptionsFromOptions(RetailerItemInventorySub $selectedSub)
    {
        $array = [];
        $i = 0;
        $retailerItemOptionGroupList = $this->getRetailerItemOptionGroupList();
        /** @var RetailerItemOptionGroup $optionGroup */
        foreach ($retailerItemOptionGroupList as $optionGroup) {
            /** @var RetailerItemOption $option */
            foreach ($optionGroup->getRetailerItemOptionList() as $option) {

                $array[$i]["optionPOSName"] = $option->getOptionDescription();
                $array[$i]["optionDisplayName"] = $option->getOptionDescription();
                $array[$i]["optionDisplayDescription"] = '';
                $array[$i]["optionId"] = $this->getUniqueId($selectedSub) . '_mod_from_option_' . $optionGroup->getOptionID() . '_' . $option->getUniqueId();
                $array[$i]["pricePerUnit"] = intval(round(floatval(trim($option->getOptionCostDisplay(), '$')) * 100));
                $array[$i]["priceLevelId"] = '';
                $array[$i]["isActive"] = 'Y';
                $array[$i]["uniqueRetailerItemModifierId"] = $this->getUniqueId($selectedSub) . '_mod_from_option_' . $optionGroup->getOptionID();
                $array[$i]["uniqueId"] = $this->getUniqueId($selectedSub) . '_mod_from_option_' . $optionGroup->getOptionID() . '_' . $option->getUniqueId();
                $array[$i]["optionDisplaySequence"] = '';
                $array[$i]["version"] = '';

                $array[$i] = array_values($array[$i]);
                $i++;
            }

        }
        return $array;
    }

    public function getUniqueId(RetailerItemInventorySub $selectedSub): string
    {
        return self::UNIQUE_ID_PREFIX . $this->getInventoryItemID() . '_' . $selectedSub->getInventoryItemSubID();
    }

    /**
     * @param string $inventoryItemImageName
     */
    public function setInventoryItemImageName(string $inventoryItemImageName)
    {
        $this->inventoryItemImageName = $inventoryItemImageName;
    }

    /** Product is 86d when it is temporary not available,
     * that means time it should be available in a given time,
     * but inventoryItemAvailableAndInsideTimeWindow is set to false
     */
    public function is86d(\DateTime $dateTime)
    {
        $startTime = $this->getStartTime();
        $endTime = $this->getEndTime();

        if ($dateTime < $startTime) {
            return false;
        }

        if ($dateTime > $endTime) {
            return false;
        }

        if ($this->isInventoryItemAvailableAndInsideTimeWindow() === false) {
            var_dump('isInventoryItemAvailableAndInsideTimeWindow false');
            return true;
        }

        var_dump('isInventoryItemAvailableAndInsideTimeWindow true');
        return false;
    }
}
