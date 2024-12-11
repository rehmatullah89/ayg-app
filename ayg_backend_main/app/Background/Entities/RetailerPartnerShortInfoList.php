<?php
namespace App\Background\Entities;

use App\Background\Entities\Partners\Grab\Retailer;
use ArrayIterator;

class RetailerPartnerShortInfoList extends Entity implements \IteratorAggregate, \JsonSerializable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public static function createFromGrabAirportInfoJson($json, $partnerName)
    {
        $json = json_decode($json, true);

        $retailerPartnerShortInfoList = new RetailerPartnerShortInfoList();
        foreach ($json['grabAirportMap'] as $v) {
            $airportIataCode = $v['airportIdent'];
            foreach ($v['grabTerminalMap'] as $vv) {
                foreach ($vv['grabWaypointMap'] as $vvv) {
                    $retailerPartnerId = $vvv['waypointID'];
                    $retailerPartnerShortInfoList->addItem(new RetailerPartnerShortInfo(
                        (string)$partnerName,
                        (string)$retailerPartnerId,
                        (string)$airportIataCode
                    ));
                }
            }
        }

        return $retailerPartnerShortInfoList;
    }

    public function addItem(RetailerPartnerShortInfo $retailerUniqueIdLoadData)
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

    public function getUniqueIdArray(): array
    {
        $array = [];
        /** @var RetailerPartnerShortInfo $item */
        foreach ($this->list as $item) {
            $array[] = Retailer::getUniqueId($item->getPartnerId());
        }
        return $array;
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
        return $this->list;
    }
}
