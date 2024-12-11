<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use ArrayIterator;

class RetailerItemOptionList extends Entity implements \JsonSerializable, \Countable, \IteratorAggregate
{


    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerItemOption $retailerItemInventoryTitle)
    {
        $this->list[] = $retailerItemInventoryTitle;
    }

    public static function createFromArray(? array $array)
    {
        $list = new RetailerItemOptionList();
        if ($array === null){
            return $list;
        }

        foreach ($array as $item) {
            $list->addItem(RetailerItemOption::createFromArray($item));
        }

        return $list;
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
        return new ArrayIterator($this->list);
    }
}
