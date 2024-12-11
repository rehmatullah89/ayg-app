<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use ArrayIterator;

class RetailerItemList extends Entity implements \JsonSerializable, \IteratorAggregate
{
    const UNIQUE_ID_PREFIX = 'grab_';

    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerItem $retailerItem)
    {
        $this->list[] = $retailerItem;
    }

    public static function createFromGrabRetailerInfoJson(string $json, \DateTimeZone $dateTimeZone): RetailerItemList
    {
        $array = json_decode($json, true);

        $list = new RetailerItemList();

        foreach ($array['inventoryItemMains'] as $item) {
            $list->addItem(RetailerItem::createFromArray($item, $dateTimeZone));
        }
        return $list;
    }

    public function jsonSerialize()
    {
        return $this->list;
    }


    public function getIterator()
    {
        return new ArrayIterator($this->list);
    }
}
