<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use App\Background\Entities\Partners\Grab\RetailerCategoryList;
use App\Background\Mappers\Partners\Grab\ItemTypeAndCategoryMapper;

class Retailer extends Entity implements \JsonSerializable
{
    const UNIQUE_ID_PREFIX = 'grab_';

    // as partner grab
    const UNIQUE_ID_PREFIX_SHORT = 'pg_';

    /**
     * @var int
     */
    private $storeWaypointID;
    private $airportIdent;
    private $storeName;
    private $storeWaypointDescription;
    private $categories;
    /**
     * @var bool
     */
    private $bStoreDineIn;
    /**
     * @var bool
     */
    private $bStoreDineToGo;
    /**
     * @var int
     */
    private $storeWaypointTerminalID;
    /**
     * @var string
     */
    private $nearGate;
    /**
     * @var bool
     */
    private $bStoreDelivery;
    /**
     * @var bool
     */
    private $bPickupEnabled;
    /**
     * @var string
     */
    private $storeImageName;
    /**
     * @var bool
     */
    private $storeOnline;
    /**
     * @var bool
     */
    private $bStoreLocal;
    /**
     * @var RetailerOpeningHoursList
     */
    private $retailerOpeningHoursList;
    /**
     * @var \DateTimeZone
     */
    private $dateTimeZone;
    /**
     * @var RetailerCustomization|null
     */
    private $retailerCustomization;

    public function __construct(
        int $storeWaypointID,
        string $airportIdent,
        string $storeName,
        string $storeWaypointDescription,
        RetailerCategoryList $categories,
        bool $bStoreDineIn,
        bool $bStoreDineToGo,
        int $storeWaypointTerminalID,
        string $nearGate,
        bool $bStoreDelivery,
        bool $bPickupEnabled,
        string $storeImageName,
        bool $storeOnline,
        bool $bStoreLocal,
        RetailerOpeningHoursList $retailerOpeningHoursList,
        \DateTimeZone $dateTimeZone,
        ?RetailerCustomization $retailerCustomization
    ) {
        $this->storeWaypointID = $storeWaypointID;
        $this->airportIdent = $airportIdent;
        $this->storeName = $storeName;
        $this->storeWaypointDescription = $storeWaypointDescription;
        $this->categories = $categories;
        $this->bStoreDineIn = $bStoreDineIn;
        $this->bStoreDineToGo = $bStoreDineToGo;
        $this->storeWaypointTerminalID = $storeWaypointTerminalID;
        $this->nearGate = $nearGate;
        $this->bStoreDelivery = $bStoreDelivery;
        $this->bPickupEnabled = $bPickupEnabled;
        $this->storeImageName = $storeImageName;
        $this->storeOnline = $storeOnline;
        $this->bStoreLocal = $bStoreLocal;
        $this->retailerOpeningHoursList = $retailerOpeningHoursList;
        $this->dateTimeZone = $dateTimeZone;
        $this->retailerCustomization = $retailerCustomization;
    }

    /**
     * @return int
     */
    public function getStoreWaypointID(): int
    {
        return $this->storeWaypointID;
    }

    /**
     * @return string
     */
    public function getAirportIdent(): string
    {
        return $this->airportIdent;
    }

    /**
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->storeName;
    }

    /**
     * @return string
     */
    public function getStoreWaypointDescription(): string
    {
        return $this->storeWaypointDescription;
    }

    /**
     * @return \App\Background\Entities\Partners\Grab\RetailerCategoryList
     */
    public function getCategories(): \App\Background\Entities\Partners\Grab\RetailerCategoryList
    {
        return $this->categories;
    }

    /**
     * @return bool
     */
    public function isBStoreDineIn(): bool
    {
        return $this->bStoreDineIn;
    }

    /**
     * @return bool
     */
    public function isBStoreDineToGo(): bool
    {
        return $this->bStoreDineToGo;
    }

    /**
     * @return int
     */
    public function getStoreWaypointTerminalID(): int
    {
        return $this->storeWaypointTerminalID;
    }

    /**
     * @return string
     */
    public function getNearGate(): string
    {
        return $this->nearGate;
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
    public function isBPickupEnabled(): bool
    {
        return $this->bPickupEnabled;
    }

    /**
     * @return string
     */
    public function getStoreImageName(): string
    {
        return $this->storeImageName;
    }

    /**
     * @return bool
     */
    public function isStoreOnline(): bool
    {
        return $this->storeOnline;
    }

    /**
     * @return bool
     */
    public function isBStoreLocal(): bool
    {
        return $this->bStoreLocal;
    }

    /**
     * @return RetailerOpeningHoursList
     */
    public function getRetailerOpeningHoursList(): RetailerOpeningHoursList
    {
        return $this->retailerOpeningHoursList;
    }

    /**
     * @return \DateTimeZone
     */
    public function getDateTimeZone(): \DateTimeZone
    {
        return $this->dateTimeZone;
    }

    public static function createFromGrabRetailerInfoJson(string $json, \DateTimeZone $dateTimeZone)
    {
        //airportIataCode - airportIdent
        //retailerName - storeName
        //description, - storeWaypointDescription
        //retailerType - categories, (categoryType of main category)
        //retailerCategory , - categories, (categoryDescription of main category)
        //retailerPriceCategory - need to be included manually
        //retailerFoodSeatingType, - bStoreDineIn True or False , bStoreDineToGo True or False
        //searchTags,  - need to be included manually
        //terminal, - storeWaypointTerminalID - there is ID, we need to find a way to get more data
        //concourse, - need to be filled manually
        //gate, - nearGate
        //hasDelivery, - bStoreDelivery
        //hasPickup, - bPickupEnabled
        //imageBackground, - storeImageName, but might need to add some manually
        //imageLogo - storeImageName
        //isActive, - storeOnline
        //isChain, - bStoreLocal
        //openTimesMonday,openTimesTuesday,openTimesWednesday,openTimesThursday,openTimesFriday,openTimesSaturday,openTimesSunday,closeTimesMonday,closeTimesTuesday,closeTimesWednesday,closeTimesThursday,closeTimesFriday,closeTimesSaturday,closeTimesSunday - that is covered


        $array = json_decode($json, true);

        return new Retailer(
            $array['storeWaypointID'],
            $array['airportIdent'],
            $array['storeName'],
            $array['storeWaypointDescription'],
            RetailerCategoryList::createFromArray($array['categories']),
            (bool)$array['bStoreDineIn'],
            (bool)$array['bStoreDineToGo'],
            (int)$array['storeWaypointTerminalID'],
            $array['nearGate'],
            (bool)$array['bStoreDelivery'],
            (bool)$array['bPickupEnabled'],
            $array['storeImageName'],
            $array['storeOnline'],
            $array['bStoreLocal'],
            RetailerOpeningHoursList::createFromString($array['localStoreTimeWeekly']),
            $dateTimeZone,
            null
        );
    }

    public function asArrayForCsvLine(): array
    {
        if ($this->getRetailerCustomization() === null || $this->getRetailerCustomization()->getVerified() !== true) {
            $arrayCustomize['terminal'] = '';
            $arrayCustomize['concourse'] = '';
            $arrayCustomize['gate'] = '';
            $arrayCustomize['hasDelivery'] = $this->isBStoreDelivery() ? 'Y' : 'N';
            $arrayCustomize['hasPickup'] = $this->isBPickupEnabled() ? 'Y' : 'N';
            $arrayCustomize['isActive'] = $this->isStoreOnline() ? 'Y' : 'N';
        } else {
            $arrayCustomize['terminal'] = $this->retailerCustomization->getTerminal();
            $arrayCustomize['concourse'] = $this->retailerCustomization->getConcourse();
            $arrayCustomize['gate'] = $this->retailerCustomization->getGate();
            $arrayCustomize['hasDelivery'] = $this->retailerCustomization->isHasDelivery() === true ? 'Y' : ($this->isBStoreDelivery() ? 'Y' : 'N');
            $arrayCustomize['hasPickup'] = $this->retailerCustomization->isHasPickup() === true ? 'Y' : ($this->isBPickupEnabled() ? 'Y' : 'N');
            $arrayCustomize['isActive'] = $this->retailerCustomization->isIsActive() ? 'Y' : 'N';
        }


        $array['airportIataCode'] = $this->getAirportIdent();
        $array['retailerName'] = $this->getStoreName();
        $array['description'] = $this->getStoreWaypointDescription();
        $retailerType = ($this->getCategories()->getPrimaryCategory() === null) ? '' : $this->getCategories()->getPrimaryCategory()->getCategoryType();
        $array['retailerType'] = ItemTypeAndCategoryMapper::mapType($retailerType);
        $retailerCategory = ($this->getCategories()->getPrimaryCategory() === null) ? '' : $this->getCategories()->getPrimaryCategory()->getCategoryDescription();
        $array['retailerCategory'] = ItemTypeAndCategoryMapper::mapCategory($retailerCategory);
        $array['retailerPriceCategory'] = '1';
        $array['retailerFoodSeatingType'] = $this->isBStoreDineIn() ? 'Sit Down' : 'Food Court';
        $array['searchTags'] = '';
        $array['terminal'] = $arrayCustomize['terminal'];
        $array['concourse'] = $arrayCustomize['concourse'];
        $array['gate'] = $arrayCustomize['gate'];
        $array['hasDelivery'] = $arrayCustomize['hasDelivery'];
        $array['hasPickup'] = $arrayCustomize['hasPickup'];
        $array['imageBackground'] = '';
        $array['imageLogo'] = $this->getStoreImageName();
        $array['isActive'] = $arrayCustomize['isActive'];
        $array['isChain'] = $this->isBStoreLocal() ? 'N' : 'Y';

        $array['openTimesMonday'] = $this->getRetailerOpeningHoursList()->getByDayId(1)->getOpeningHours();
        $array['openTimesTuesday'] = $this->getRetailerOpeningHoursList()->getByDayId(2)->getOpeningHours();
        $array['openTimesWednesday'] = $this->getRetailerOpeningHoursList()->getByDayId(3)->getOpeningHours();
        $array['openTimesThursday'] = $this->getRetailerOpeningHoursList()->getByDayId(4)->getOpeningHours();
        $array['openTimesFriday'] = $this->getRetailerOpeningHoursList()->getByDayId(5)->getOpeningHours();
        $array['openTimesSaturday'] = $this->getRetailerOpeningHoursList()->getByDayId(6)->getOpeningHours();
        $array['openTimesSunday'] = $this->getRetailerOpeningHoursList()->getByDayId(7)->getOpeningHours();
        $array['closeTimesMonday'] = $this->getRetailerOpeningHoursList()->getByDayId(1)->getClosingHours();
        $array['closeTimesTuesday'] = $this->getRetailerOpeningHoursList()->getByDayId(2)->getClosingHours();
        $array['closeTimesWednesday'] = $this->getRetailerOpeningHoursList()->getByDayId(3)->getClosingHours();
        $array['closeTimesThursday'] = $this->getRetailerOpeningHoursList()->getByDayId(4)->getClosingHours();
        $array['closeTimesFriday'] = $this->getRetailerOpeningHoursList()->getByDayId(5)->getClosingHours();
        $array['closeTimesSaturday'] = $this->getRetailerOpeningHoursList()->getByDayId(6)->getClosingHours();
        $array['closeTimesSunday'] = $this->getRetailerOpeningHoursList()->getByDayId(7)->getClosingHours();


        $array['uniqueId'] = self::getUniqueId($this->getStoreWaypointID());

        return array_values($array);
    }

    public function generateMenuDirName(string $airportIataCode)
    {
        $name = preg_replace("/[^A-Za-z0-9]/", '', $this->getStoreName());
        return $airportIataCode . '-' . $name . '_' . $this->getStoreWaypointID();
    }

    public function asArrayForCsvLineItemCustom()
    {
        $array["retailerName"] = $this->getStoreName();
        $array["retailerId"] = $this->getStoreWaypointID();
        $array["terminal"] = "";
        $array["concourse"] = "";
        $array["gate"] = "";
        $array["hasDelivery"] = "";
        $array["hasPickup"] = "";
        $array["isActive"] = "";
        $array["verified"] = "";

        return array_values($array);

    }

    /**
     * @return RetailerCustomization|null
     */
    public function getRetailerCustomization()
    {
        return $this->retailerCustomization;
    }


    public function setRetailerCustomization(?RetailerCustomization $retailerCustomization): self
    {
        $this->retailerCustomization = $retailerCustomization;
        return $this;
    }

    public static function getUniqueId($id)
    {
        return self::UNIQUE_ID_PREFIX_SHORT . md5(self::UNIQUE_ID_PREFIX . $id . md5(sha1($id)));
    }

    /**
     * @param string $storeImageName
     */
    public function setStoreImageName(string $storeImageName)
    {
        $this->storeImageName = $storeImageName;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
