<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;

class CartItem extends Dto implements \JsonSerializable
{
    /**
     * @var string
     */
    private $itemId;
    /**
     * @var CartItemOptionList
     */
    private $cartItemOptionList;
    /**
     * @var string
     */
    private $itemQuantity;
    /**
     * @var string
     */
    private $itemComment;
    /**
     * @var int
     */
    private $itemPrice;
    /**
     * @var string
     */
    private $itemName;

    public function __construct(
        string $itemId,
        string $itemQuantity,
        string $itemComment,
        string $itemName,
    int $itemPrice,
        CartItemOptionList $cartItemOptionList
    ) {
        $this->itemId = $itemId;
        $this->cartItemOptionList = $cartItemOptionList;
        $this->itemQuantity = $itemQuantity;
        $this->itemComment = $itemComment;
        $this->itemPrice = $itemPrice;
        $this->itemName = $itemName;
    }

    /**
     * @return string
     */
    public function getItemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     */
    public function getItemQuantity(): string
    {
        return $this->itemQuantity;
    }

    /**
     * @return string
     */
    public function getItemComment(): string
    {
        return $this->itemComment;
    }

    /**
     * @return CartItemOptionList
     */
    public function getCartItemOptionList(): CartItemOptionList
    {
        return $this->cartItemOptionList;
    }

    /**
     * @return int
     */
    public function getItemPrice(): int
    {
        return $this->itemPrice;
    }

    /**
     * @return string
     */
    public function getItemName(): string
    {
        return $this->itemName;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
