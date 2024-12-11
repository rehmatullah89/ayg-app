<?php
namespace App\Background\Entities;

class MenuUpdateRetailerUniqueIdList extends Entity
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData)
    {
        $this->list[] = $retailerUniqueIdLoadData;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function getByAirportIataCode(string $airportIataCode)
    {
        $result = [];
        /** @var MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData */
        foreach ($this->list as $retailerUniqueIdLoadData) {
            if ($retailerUniqueIdLoadData->getAirportIataCode() === $airportIataCode) {
                $result[] = $retailerUniqueIdLoadData;
            }
        }
        return $result;
    }
}
