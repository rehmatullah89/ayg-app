<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;

class EmployeeDiscount extends Dto implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $isApplicable;
    /**
     * @var bool|null
     */
    private $isPercentage;
    /**
     * @var bool|null
     */
    private $discountPercentage;
    /**
     * @var
     */
    private $additionalData;

    public function __construct(
        bool $isApplicable,
        ?bool $isPercentage,
        ?int $discountPercentage,
        $additionalData
    ) {
        $this->isApplicable = $isApplicable;
        $this->isPercentage = $isPercentage;
        $this->discountPercentage = $discountPercentage;
        $this->additionalData = $additionalData;
    }

    /**
     * @return bool
     */
    public function isIsApplicable(): bool
    {
        return $this->isApplicable;
    }

    /**
     * @return bool|null
     */
    public function isPercentage()
    {
        return $this->isPercentage;
    }

    /**
     * @return mixed
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @return bool|null
     */
    public function getDiscountPercentage()
    {
        return $this->discountPercentage;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
