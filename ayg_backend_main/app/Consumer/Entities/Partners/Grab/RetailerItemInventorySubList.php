<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;
use Traversable;

class RetailerItemInventorySubList extends Entity implements \JsonSerializable, \Countable, \IteratorAggregate
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerItemInventorySub $retailerItemInventorySub)
    {
        $this->list[] = $retailerItemInventorySub;
    }

    public static function createFromArray(array $array)
    {
        $list = new RetailerItemInventorySubList();

        foreach ($array as $item) {
            $list->addItem(new RetailerItemInventorySub(
                (int)$item['inventoryItemID'],
                (int)$item['inventoryItemSubID'],
                (string)$item['inventoryItemSubName'],
                (float)$item['cost']
            ));
        }

        return $list;
    }

    public function getFirst():?RetailerItemInventorySub
    {
        if (!isset($this->list[0])) {
            return null;
        }
        return $this->list[0];
    }

    function jsonSerialize()
    {
        return $this->list;
    }

    public function count()
    {
        return count($this->list);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->list);
    }
}
