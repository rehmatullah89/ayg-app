<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class OrderValidationErrorList implements \IteratorAggregate, \Countable
{
    /**
     * @var array<OrderValidationError>
     */
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(OrderValidationError $orderValidationError)
    {
        $this->data[] = $orderValidationError;
    }

    public function returnAsArray()
    {
        $list = [];
        foreach ($this->data as $item) {
            /**
             * @var $item OrderValidationError
             */
            $list[] = $item->returnAsArray();
        }
        return $list;
    }

    // function called when encoded with json_encode
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
