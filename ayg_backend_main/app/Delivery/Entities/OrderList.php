<?php
namespace App\Delivery\Entities;

use App\Delivery\Exceptions\OrderNotFoundException;
use ArrayIterator;

class OrderList implements \IteratorAggregate, \Countable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(Order $order)
    {
        $this->data[] = $order;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getFirst()
    {
        if (count($this->data)==0){
            throw new OrderNotFoundException('Can not get first out of empty Order list');
        }
        return $this->data[0];
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
