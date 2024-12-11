<?php
namespace App\Consumer\Entities;

/**
 * Class TerminalGateMap
 * @package App\Tablet\Entities
 */
class TerminalGateMap extends Entity implements \JsonSerializable
{

    private $id;
    private $createdAt;
    private $updatedAt;
    private $airportIataCode;
    private $concourse;
    private $displaySequence;
    private $gate;
    private $geoPointLocation;
    private $locationDisplayName;
    private $terminal;
    private $uniqueId;
    private $gateDisplayName;
    private $isDefaultLocation;

    /**
     * TerminalGateMap constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->airportIataCode = $data['airportIataCode'];
        $this->concourse = $data['concourse'];
        $this->displaySequence = $data['displaySequence'];
        $this->gate = $data['gate'];
        $this->geoPointLocation = $data['geoPointLocation'];
        $this->locationDisplayName = $data['locationDisplayName'];
        $this->terminal = $data['terminal'];
        $this->uniqueId = $data['uniqueId'];
        $this->gateDisplayName = $data['gateDisplayName'];
        $this->isDefaultLocation = $data['isDefaultLocation'];
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
    public function getCreatedAt()
    {
        return $this->createdAt;
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
    public function getAirportIataCode()
    {
        return $this->airportIataCode;
    }

    /**
     * @return mixed
     */
    public function getConcourse()
    {
        return $this->concourse;
    }

    /**
     * @return mixed
     */
    public function getDisplaySequence()
    {
        return $this->displaySequence;
    }

    /**
     * @return mixed
     */
    public function getGate()
    {
        return $this->gate;
    }

    /**
     * @return mixed
     */
    public function getGeoPointLocation()
    {
        return $this->geoPointLocation;
    }

    /**
     * @return mixed
     */
    public function getLocationDisplayName()
    {
        return $this->locationDisplayName;
    }

    /**
     * @return mixed
     */
    public function getTerminal()
    {
        return $this->terminal;
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
    public function getGateDisplayName()
    {
        return $this->gateDisplayName;
    }

    /**
     * @return mixed
     */
    public function getIsDefaultLocation()
    {
        return $this->isDefaultLocation;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}