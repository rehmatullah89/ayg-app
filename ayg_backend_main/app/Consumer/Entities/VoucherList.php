<?php
namespace App\Consumer\Entities;

use ArrayIterator;

class VoucherList extends Entity implements \JsonSerializable, \Countable, \IteratorAggregate
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function addItem(Voucher $voucher)
    {
        $this->data[] = $voucher;
    }

    public function jsonSerialize()
    {
        return $this->data;
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
