<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;
use ArrayIterator;
use IteratorAggregate;

class CartItemOptionList extends Dto implements \JsonSerializable, IteratorAggregate
{
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    /**
     * @param array $options
     * @return CartItemOptionList
     * @see getOrderSummaryItemlist()
     */
    public static function createFromGetOrderSummaryItemListResultOptions(array $options): CartItemOptionList
    {
        $cartItemOptionList = new CartItemOptionList();
        foreach ($options as $option) {
            $cartItemOptionList->addItem(
                new CartItemOption(
                    $option['optionId'],
                    $option['optionQuantity']
                )
            );
        }

        return $cartItemOptionList;
    }

    public function addItem(CartItemOption $cartItemOption)
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
