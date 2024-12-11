<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class SessionDeviceList implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private $data;

    public function __construct()
    {
        $THIS->data = [];
    }

    public function addItem(SessionDevice $sessionDevice)
    {
        $this->data[] = $sessionDevice;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function count()
    {
        return count($this->data);
    }

    public function getFirst():?SessionDevice
    {
        if (!isset($this->data[0])) {
            return null;
        }
        return $this->data[0];
    }

    public function jsonSerialize()
    {
        return $this->data;
    }
}
