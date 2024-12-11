<?php
namespace App\Background\Entities;

use ArrayIterator;
use Traversable;

class RetailerPartnerList extends Entity implements \IteratorAggregate
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerPartner $retailerUniqueIdLoadData)
    {
        $this->list[] = $retailerUniqueIdLoadData;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->list);
    }

    public function getAllAirportIataCodes(): array
    {
        $list = [];

        /** @var RetailerPartner $item */
        foreach ($this->list as $item) {
            $list[$item->getAirportIataCode()] = $item->getAirportIataCode();
        }

        return array_values($list);
    }

    public function findByPartnerNameAndPartnerId(string $partnerName, int $partnerId):?RetailerPartner
    {
        /** @var RetailerPartner $item */
        foreach ($this->list as $item) {
            if ($item->getPartnerId() == $partnerId && $item->getPartnerName() == $partnerName) {
                return $item;
            }
        }
        return null;
    }

    public function filterByAirportIataCode(string $airportIataCode): RetailerPartnerList
    {
        $list = new RetailerPartnerList();

        /** @var RetailerPartner $item */
        foreach ($this->list as $item) {
            if ($item->getAirportIataCode() == $airportIataCode) {
                $list->addItem(clone($item));
            }
        }

        return $list;
    }


    public function getRetailerUniqueIdArray():array
    {
        $array = [];
        /** @var RetailerPartner $item */
        foreach ($this->list as $item) {
            $array[] = $item->getRetailerUniqueId();
        }
        return $array;
    }
}
