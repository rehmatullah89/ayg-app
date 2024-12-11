<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class RetailerItemPropertyList implements \IteratorAggregate, \Countable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(RetailerItemProperty $retailerItemProperty)
    {
        $this->data[] = $retailerItemProperty;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getFirst():?RetailerItemProperty
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return $this->data[0];
    }
}
