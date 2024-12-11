<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use ArrayIterator;

class RetailerStatusList extends Entity implements \IteratorAggregate
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public static function createFromGrabAirportInfoJson($json)
    {
        $json = json_decode($json, true);

        $retailerPartnerShortInfoList = new RetailerStatusList();
        foreach ($json['grabAirportMap'] as $v) {
            $airportIataCode = $v['airportIdent'];
            foreach ($v['grabTerminalMap'] as $vv) {
                foreach ($vv['grabWaypointMap'] as $vvv) {
                    $retailerPartnerShortInfoList->addItem(new RetailerStatus(
                        (string)$vvv['waypointID'],
                        (bool)$vvv['bPickupEnabled'],
                        (bool)$vvv['bStoreDelivery'],
                        (bool)$vvv['bStoreIsCurrentlyOpen']
                    ));
                }
            }
        }

        return $retailerPartnerShortInfoList;
    }

    public function addItem(RetailerStatus $retailerUniqueIdLoadData)
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
}
