<?php
namespace App\Delivery\Entities;

use ArrayIterator;

class ItemModifierOptionList implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(ItemModifierOption $orderShortInfo)
    {
        $this->data[] = $orderShortInfo;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return $this->data;
    }
}
