<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;
use ArrayIterator;

class RetailerItemChoiceList extends Entity implements \JsonSerializable, \Countable, \IteratorAggregate
{


    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerItemChoice $retailerItemInventoryTitle)
    {
        $this->list[] = $retailerItemInventoryTitle;
    }

    public static function createFromArray(? array $array)
    {
        $list = new RetailerItemChoiceList();
        if ($array === null){
            return $list;
        }

        foreach ($array as $item) {
            $list->addItem(RetailerItemChoice::createFromArray($item));
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
