<?php
namespace App\Delivery\Entities;

use ArrayIterator;

class OrderShortInfoList implements \IteratorAggregate, \Countable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(OrderShortInfo $orderShortInfo)
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

    public function asArray()
    {
        $array = [];
        foreach ($this->data as $order) {
            $array[] = $order;
        }
        return $array;

    }
}
