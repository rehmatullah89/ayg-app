<?php
namespace App\Tablet\Entities;

class Retailer extends Entity implements \JsonSerializable
{
    CONST PREPARATION_BUFFER_TIME_IN_SECONDS = 600;
    CONST DELIVERY_TIME_IN_SECONDS = 300;

    private $id;
    private $searchTags;
    private $imageLogo;
    private $closeTimesSaturday;
    private $closeTimesThursday;
    private $closeTimesWednesday;
    private $imageBackground;
    private $retailerFoodSeatingType;
    private $retailerType;
    private $openTimesSunday;
    private $openTimesMonday;
    private $closeTimesFriday;
    private $hasDelivery;
    private $retailerCategory;
    private $updatedAt;
    private $isActive;
    private $openTimesTuesday;
    private $openTimesSaturday;
    /**
     * @var TerminalGateMap
     */
    private $location;
    private $openTimesThursday;
    private $uniqueId;
    private $hasPickup;
    private $retailerPriceCategory;
    private $isChain;
    private $openTimesWednesday;
    private $createdAt;
    private $retailerName;
    private $openTimesFriday;
    private $description;
    private $airportIataCode;
    private $closeTimesMonday;
    private $closeTimesSunday;
    private $closeTimesTuesday;
    private $lastPing;


    private $locationId;

    /**
     * Retailer constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        // relations
        if (isset($data['retailerType'])) {
            $this->retailerType = $data['retailerType'];
        }
        if (isset($data['location'])) {
            $this->location = $data['location'];
        }
        if (isset($data['retailerPriceCategory'])) {
            $this->retailerPriceCategory = $data['retailerPriceCategory'];
        }


        // relations ids
        if (isset($data['locationId'])) {
            $this->locationId = $data['locationId'];
        }

        $this->id = $data['id'];
        $this->searchTags = $data['searchTags'];
        $this->imageLogo = $data['imageLogo'];
        $this->closeTimesSaturday = $data['closeTimesSaturday'];
        $this->closeTimesThursday = $data['closeTimesThursday'];
        $this->closeTimesWednesday = $data['closeTimesWednesday'];
        $this->imageBackground = $data['imageBackground'];
        $this->retailerFoodSeatingType = $data['retailerFoodSeatingType'];
        $this->openTimesSunday = $data['openTimesSunday'];
        $this->openTimesMonday = $data['openTimesMonday'];
        $this->closeTimesFriday = $data['closeTimesFriday'];
        $this->hasDelivery = $data['hasDelivery'];
        $this->retailerCategory = $data['retailerCategory'];
        $this->updatedAt = $data['updatedAt'];
        $this->isActive = $data['isActive'];
        $this->openTimesTuesday = $data['openTimesTuesday'];
        $this->openTimesSaturday = $data['openTimesSaturday'];
        $this->openTimesThursday = $data['openTimesThursday'];
        $this->uniqueId = $data['uniqueId'];
        $this->hasPickup = $data['hasPickup'];
        $this->isChain = $data['isChain'];
        $this->openTimesWednesday = $data['openTimesWednesday'];
        $this->createdAt = $data['createdAt'];
        $this->retailerName = $data['retailerName'];
        $this->openTimesFriday = $data['openTimesFriday'];
        $this->description = $data['description'];
        $this->airportIataCode = $data['airportIataCode'];
        $this->closeTimesMonday = $data['closeTimesMonday'];
        $this->closeTimesSunday = $data['closeTimesSunday'];
        $this->closeTimesTuesday = $data['closeTimesTuesday'];
        $this->lastPing = $data['lastPing'];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return mixed
     */
    public function getSearchTags()
    {
        return $this->searchTags;
    }

    /**
     * @return mixed
     */
    public function getImageLogo()
    {
        return $this->imageLogo;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesSaturday()
    {
        return $this->closeTimesSaturday;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesThursday()
    {
        return $this->closeTimesThursday;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesWednesday()
    {
        return $this->closeTimesWednesday;
    }

    /**
     * @return mixed
     */
    public function getImageBackground()
    {
        return $this->imageBackground;
    }

    /**
     * @return mixed
     */
    public function getRetailerFoodSeatingType()
    {
        return $this->retailerFoodSeatingType;
    }

    /**
     * @return RetailerType
     */
    public function getRetailerType()
    {
        return $this->retailerType;
    }

    /**
     * @return mixed
     */
    public function getOpenTimesSunday()
    {
        return $this->openTimesSunday;
    }

    /**
     * @return mixed
     */
    public function getOpenTimesMonday()
    {
        return $this->openTimesMonday;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesFriday()
    {
        return $this->closeTimesFriday;
    }

    /**
     * @return mixed
     */
    public function getHasDelivery()
    {
        return $this->hasDelivery;
    }

    /**
     * @return mixed
     */
    public function getRetailerCategory()
    {
        return $this->retailerCategory;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return mixed
     */
    public function getOpenTimesTuesday()
    {
        return $this->openTimesTuesday;
    }

    /**
     * @return mixed
     */
    public function getOpenTimesSaturday()
    {
        return $this->openTimesSaturday;
    }

    /**
     * @return TerminalGateMap
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param TerminalGateMap $terminalGateMap
     * @return $this
     */
    public function setLocation(TerminalGateMap $terminalGateMap)
    {
        $this->location = $terminalGateMap;
        return $this;
    }

    /**
     * @param mixed $retailerType
     */
    public function setRetailerType($retailerType)
    {
        $this->retailerType = $retailerType;
    }


    /**
     * @return mixed
     */
    public function getOpenTimesThursday()
    {
        return $this->openTimesThursday;
    }

    /**
     * @return mixed
     */
    public function getHasPickup()
    {
        return $this->hasPickup;
    }

    /**
     * @return mixed
     */
    public function getRetailerPriceCategory()
    {
        return $this->retailerPriceCategory;
    }

    /**
     * @return mixed
     */
    public function getIsChain()
    {
        return $this->isChain;
    }

    /**
     * @return mixed
     */
    public function getOpenTimesWednesday()
    {
        return $this->openTimesWednesday;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function getRetailerName()
    {
        return $this->retailerName;
    }

    /**
     * @return mixed
     */
    public function getOpenTimesFriday()
    {
        return $this->openTimesFriday;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return mixed
     */
    public function getAirportIataCode()
    {
        return $this->airportIataCode;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesMonday()
    {
        return $this->closeTimesMonday;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesSunday()
    {
        return $this->closeTimesSunday;
    }

    /**
     * @return mixed
     */
    public function getCloseTimesTuesday()
    {
        return $this->closeTimesTuesday;
    }

    /**
     * @return mixed
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * @return mixed
     */
    public function getLastPing()
    {
        return $this->lastPing;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}