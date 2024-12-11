<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;

class CartItemOption extends Dto implements \JsonSerializable
{
    /**
     * @var string
     */
    private $optionId;
    /**
     * @var int
     */
    private $quantity;

    public function __construct(
        string $optionId,
        int $quantity
    ) {
        $this->optionId = $optionId;
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getOptionId(): string
    {
        return $this->optionId;
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
