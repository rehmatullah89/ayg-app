<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;

class Cart extends Dto implements \JsonSerializable
{
    /**
     * @var CartItemList
     */
    private $cartItemList;
    /**
     * @var string
     */
    private $partnerRetailerId;
    /**
     * @var \DateTimeZone
     */
    private $dateTimeZone;
    /**
     * @var int
     */
    private $subTotal;
    /**
     * @var CartUserDetails
     */
    private $cartUserDetails;
    /**
     * @var bool|null
     */
    private $isPickup;
    /**
     * @var bool|null
     */
    private $isDelivery;

    public function __construct(
        CartUserDetails $cartUserDetails,
        string $partnerRetailerId,
        CartItemList $cartItemList,
        \DateTimeZone $dateTimeZone,
        int $subTotal,
        ?bool $isPickup,
        ?bool $isDelivery
    ) {
        $this->cartUserDetails = $cartUserDetails;
        $this->cartItemList = $cartItemList;
        $this->partnerRetailerId = $partnerRetailerId;
        $this->dateTimeZone = $dateTimeZone;
        $this->subTotal = $subTotal;
        $this->isPickup = $isPickup;
        $this->isDelivery = $isDelivery;
    }

    /**
     * @return CartUserDetails
     */
    public function getCartUserDetails(): CartUserDetails
    {
        return $this->cartUserDetails;
    }
/*

     * @param string $partnerRetailerId
     * @param array $itemList
     * @param \DateTimeZone $dateTimeZone
     * @param int $subtotal
     * @return Cart
     * @see getOrderSummaryItemlist()

    public static function createFromGetOrderSummaryItemListResult(
        string $partnerRetailerId,
        array $itemList,
        \DateTimeZone $dateTimeZone,
        int $subtotal

    ): Cart {
        return new Cart(
            $partnerRetailerId,
            CartItemList::createFromGetOrderSummaryItemListResult($itemList),
            $dateTimeZone,
            $subtotal
        );
    }
*/

    /**
     * @return string
     */
    public function getPartnerRetailerId(): string
    {
        return $this->partnerRetailerId;
    }

    /**
     * @return CartItemList
     */
    public function getCartItemList(): CartItemList
    {
        return $this->cartItemList;
    }

    /**
     * @return \DateTimeZone
     */
    public function getDateTimeZone(): \DateTimeZone
    {
        return $this->dateTimeZone;
    }

    /**
     * @return int
     */
    public function getSubTotal(): int
    {
        return $this->subTotal;
    }

    /**
     * @return bool|null
     */
    public function getIsPickup()
    {
        return $this->isPickup;
    }

    /**
     * @return bool|null
     */
    public function getIsDelivery()
    {
        return $this->isDelivery;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
