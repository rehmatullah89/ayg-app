<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class ScheduledOrderTimeRangeList implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(ScheduledOrderTimeRange $scheduledOrderTimeRange)
    {
        $this->data[] = $scheduledOrderTimeRange;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function isEmpty()
    {
        if (count($this->data) == 0) {
            return true;
        }
        return false;
    }

    function jsonSerialize()
    {
        return $this->data;
    }
}
