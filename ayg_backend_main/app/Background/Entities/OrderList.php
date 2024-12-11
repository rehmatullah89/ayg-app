<?php
namespace App\Background\Entities;

use App\Background\Mappers\ParseOrderIntoOrderMapper;
use App\Background\Mappers\ParseRetailerIntoRetailerMapper;
use App\Background\Mappers\ParseTerminalGateMapIntoTerminalGateMapMapper;
use Parse\ParseObject;

class OrderList extends Entity implements \IteratorAggregate
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(Order $retailerUniqueIdLoadData)
    {
        $this->list[] = $retailerUniqueIdLoadData;
    }

    public static function createFromParseObjectsArray(array $array): OrderList
    {
        $list = new OrderList();
        /** @var ParseObject $parseObject */
        foreach ($array as $parseObject) {
            $order = ParseOrderIntoOrderMapper::map($parseObject);
            $retailer = ParseRetailerIntoRetailerMapper::map($parseObject->get('retailer'));
            $retailerLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseObject->get('retailer')->get('location'));
            $retailer = $retailer->setLocation($retailerLocation);
            $order = $order->setRetailer($retailer);

            $list->addItem($order);
        }
        return $list;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->list);
    }
}
