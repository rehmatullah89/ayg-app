<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use ArrayIterator;

class RetailerList extends Entity implements \JsonSerializable, \IteratorAggregate
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(Retailer $retailerItem)
    {
        $this->list[] = $retailerItem;
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
