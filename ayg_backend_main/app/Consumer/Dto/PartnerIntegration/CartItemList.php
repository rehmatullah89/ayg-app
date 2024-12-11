<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;
use ArrayIterator;
use IteratorAggregate;
use Traversable;

class CartItemList extends Dto implements \JsonSerializable, IteratorAggregate
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    /**
     * @param array $itemList
     * @return CartItemList
     * @see getOrderSummaryItemlist()
     */
    public static function createFromGetOrderSummaryItemListResult(array $itemList): CartItemList
    {
        $list = new CartItemList();

        foreach ($itemList as $item) {
            if (!isset($item['options'])) {
                $item['options'] = [];
            }
            $list->addItem(new CartItem(
                $item['itemId'],
                $item['itemQuantity'],
                $item['itemComment'],
                $item['itemName'],
                round($item['itemPrice']),
                CartItemOptionList::createFromGetOrderSummaryItemListResultOptions($item['options'])
            ));
        }

        return $list;
    }

    public function addItem(CartItem $cartItemOption)
    {
        $this->data[] = $cartItemOption;
    }


    function jsonSerialize()
    {
        return $this->data;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}
