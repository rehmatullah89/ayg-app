<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

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
                (float)$item['cost'],
                null
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

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return count($this->list);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->list);
    }
}
