<?php
namespace App\Background\Entities;

class MenuUpdateRetailerUniqueId extends Entity
{
    private $airportIataCode;

    private $retailerUniqueId;

    private $retailerDataDirName;

    private $retailerDataPath;

    public function __construct(
        string $airportIataCode,
        string $retailerUniqueId,
        string $retailerDataDirName
    ) {
        $this->airportIataCode = $airportIataCode;
        $this->retailerUniqueId = $retailerUniqueId;
        $this->retailerDataDirName = $retailerDataDirName;
        $this->retailerDataPath = $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $airportIataCode . '-' . $retailerDataDirName;
    }

    public function getAirportIataCode(): string
    {
        return $this->airportIataCode;
    }

    public function getRetailerUniqueId(): string
    {
        return $this->retailerUniqueId;
    }

    public function getRetailerDataDirName(): string
    {
        return $this->retailerDataDirName;
    }

    public function getRetailerDataPath(): string
    {
        return $this->retailerDataPath;
    }
}
