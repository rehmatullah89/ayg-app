<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;

class CartTotals extends Dto implements \JsonSerializable
{
    /**
     * @var int
     */
    private $subtotal;
    /**
     * @var int
     */
    private $tax;
    /**
     * @var int
     */
    private $total;

    public function __construct(int $subtotal, int $tax, int $total)
    {
        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    /**
     * @return int
     */
    public function getTax(): int
    {
        return $this->tax;
    }

    /**
     * @return int
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
