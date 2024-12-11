<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

class RetailerItem extends Entity implements \JsonSerializable
{

    const UNIQUE_ID_PREFIX = 'grab_';

    const MAIN_SUB_NAME = 'MAIN';
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
    private $inventoryItemSubId;
    /**
     * @var string
     */
    private $inventoryItemSubName;
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
     * @var RetailerItemCustomization|null
     */
    private $retailerItemCustomization;
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
        string $inventoryItemSubId,
        string $inventoryItemSubName,
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
        \DateTimeZone $dateTimeZone,
        ?RetailerItemCustomization $retailerItemCustomization
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
        $this->retailerItemCustomization = $retailerItemCustomization;
        $this->inventoryItemSubId = $inventoryItemSubId;
        $this->inventoryItemSubName = $inventoryItemSubName;
        $this->retailerItemInventorySubList = $retailerItemInventorySubList;
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


        $inventoryItemSubId = '';
        $inventoryItemSubName = '';
        if (isset($array['inventoryItemSubs'])) {
            $inventoryItemSubHasBeenSet = false;
            foreach ($array['inventoryItemSubs'] as $sub) {
                if ($sub['inventoryItemSubName'] == self::MAIN_SUB_NAME) {
                    $inventoryItemSubId = $sub['inventoryItemSubID'];
                    $inventoryItemSubName = $sub['inventoryItemSubName'];
                    $inventoryItemSubHasBeenSet = true;
                    break;
                }
            }
            if (!$inventoryItemSubHasBeenSet){
                $inventoryItemSubId=$array['inventoryItemSubs'][0]['inventoryItemSubID'];
                $inventoryItemSubName=$array['inventoryItemSubs'][0]['inventoryItemSubName'];
            }
        }

        return new RetailerItem(
            RetailerItemInventoryTitleList::createFromArray($array['inventoryTitles']),
            $array['inventoryItemName'],
            RetailerItemInventorySubList::createFromArray($array['inventoryItemSubs']),
            $array['inventoryItemDescription'],
            $array['inventoryItemID'],
            $inventoryItemSubId,
            $inventoryItemSubName,
            $array['costDisplay'],
            (bool)$array['bRetailInStock'],
            (bool)$array['bRetailPurchasable'],
            (bool)$array['inventoryItemAvailable'],
            (bool)$array['inventoryItemAvailableAndInsideTimeWindow'],
            $array['inventoryItemImageName'],
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

    public function asArrayForCsvLine()
    {
        //inventoryTitles -> first -> inventoryTitleDescription
        $array['itemCategoryName'] = (string)$this->getRetailerItemInventoryTitleList()->getFirst()->getInventoryTitleDescription(true);

        //we usually skipp it, in document it is empty
        $array['itemSecondCategoryName'] = '';

        // we usually skipp it, in document it is inventoryItemAttributeTypes
        $array['itemThirdCategoryName'] = '';

        // inventoryItemName
        $array['itemPOSName'] = $this->getInventoryItemName();

        // -inventoryItemName
        $array['itemDisplayName'] = $this->getInventoryItemName();

        // -inventoryItemDescription
        $array['itemDisplayDescription'] = $this->getInventoryItemDescription();

        // -inventoryItemID
        // @todo - check if prefix is fine
        $array['itemId'] = $this->getUniqueId();

        // -in document it is costDisplay, but it should be cost multiplied by 100 (or with removed dot)
        $array['itemPrice'] = intval(trim($this->getCostDisplay(), '$')) * 100;

        // -need to be added manually
        // @todo manual work
        $array['priceLevelId'] = '1';

        // bRetailInStock, bRetailPurchasable, inventoryItemAvailable
        $grabIsActive = $this->isActive() == 1 ? 'Y' : 'N';

        if ($this->getRetailerItemCustomization() == null) {
            $array['isActive'] = $grabIsActive;
        } else {
            $array['isActive'] = $this->getRetailerItemCustomization()->isIsActive() == false ? 'N' : $grabIsActive;
        }

        // @todo - get it from Json
        $array['uniqueRetailerId'] = 'unique_retailer_id';

        // inventoryItemID
        // @todo - check if prefix is fine
        $array['uniqueId'] = self::UNIQUE_ID_PREFIX . $this->getInventoryItemID();

        // inventoryItemImageName
        $array['itemImageURL'] = $this->getInventoryItemImageName();

        // -in docuument inventoryItemAttributeTypes -> list -> inventoryItemAttributeDescription, but it is related with retailer, not item so skipped or manual
        $array['itemTags'] = '';

        // inventoryOrder
        if ($this->getRetailerItemCustomization() == null) {
            $array['itemDisplaySequence'] = $this->getInventoryOrder();
        } else {
            $array['itemDisplaySequence'] = $this->getRetailerItemCustomization()->getItemDisplaySequence() == 0 ? $this->getInventoryOrder() : $this->getRetailerItemCustomization()->getItemDisplaySequence();
        }

        // , -taxRate(but might not be the same) we usually leave it blank
        $array['taxCategory'] = '';

        // - manual

        if ($this->getRetailerItemCustomization() == null) {
            $array['allowedThruSecurity'] = 'Y';
        } else {
            $array['allowedThruSecurity'] = $this->getRetailerItemCustomization()->isAllowedThruSecurity() ? 'Y' : 'N';
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

        //if (!$this->isInventoryItemAvailableAndInsideTimeWindow()) {
        //    return false;
        //}

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

    public function getRetailerItemCustomization():?RetailerItemCustomization
    {
        return $this->retailerItemCustomization;
    }

    public function setRetailerItemCustomization(?RetailerItemCustomization $retailerItemCustomization): self
    {
        $this->retailerItemCustomization = $retailerItemCustomization;
        return $this;
    }


    public function jsonSerialize()
    {
        return get_object_vars($this);
    }


    public function getUniqueId(): string
    {
        return self::UNIQUE_ID_PREFIX . $this->getInventoryItemID();
    }

    /**
     * @return string
     */
    public function getInventoryItemSubId(): string
    {
        return $this->inventoryItemSubId;
    }

    /**
     * @return string
     */
    public function getInventoryItemSubName(): string
    {
        return $this->inventoryItemSubName;
    }

    /**
     * @return RetailerItemInventorySubList
     */
    public function getRetailerItemInventorySubList(): RetailerItemInventorySubList
    {
        return $this->retailerItemInventorySubList;
    }

    /**
     * @return bool
     */
    public function isInventoryItemAvailableAndInsideTimeWindow(): bool
    {
        return $this->inventoryItemAvailableAndInsideTimeWindow;
    }
}
