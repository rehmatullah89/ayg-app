<?php
namespace App\Delivery\Entities;

use ArrayIterator;

class ItemList implements \IteratorAggregate, \Countable, \JsonSerializable
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(Item $orderShortInfo)
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

    /**
     * @param string $itemId
     * @return Item|null
     */
    public function findItemById(string $itemId):?Item
    {
        /** @var Item $item */
        foreach ($this->data as $item) {
            if ($item->getId() == $itemId) {
                return $item;
            }
        }
        return null;
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
