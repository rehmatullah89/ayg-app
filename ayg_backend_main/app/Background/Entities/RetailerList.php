<?php
namespace App\Background\Entities;

use ArrayIterator;
use Countable;

class RetailerList extends Entity implements \IteratorAggregate, Countable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(Retailer $retailerUniqueIdLoadData)
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

    public function getFirst():?Retailer
    {
        if (!(isset($this->list[0]))) {
            return null;
        }
        return $this->list[0];
    }


    public function getRetailerIdArray()
    {
        $array = [];
        /** @var Retailer $item */
        foreach ($this->list as $item) {
            $array[] = $item->getId();
        }
        return $array;
    }

    public function count()
    {
        return count($this->list);
    }
}
