<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class InfoTipValueList extends Entity implements \JsonSerializable, \Countable, \IteratorAggregate
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(InfoTipValue $userIdentifier)
    {
        $this->data[] = $userIdentifier;
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }
}
