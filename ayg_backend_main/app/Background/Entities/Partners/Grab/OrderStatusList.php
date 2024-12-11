<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use ArrayIterator;

class OrderStatusList extends Entity implements \IteratorAggregate
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(OrderStatus $orderStatus)
    {
        $this->list[] = $orderStatus;
    }

    public function getList(): array
    {
        return $this->list;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->list);
    }
}
