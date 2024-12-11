<?php
namespace App\Consumer\Entities;

class Voucher extends Entity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $objectId;
    /**
     * @var string
     */
    private $partnerName;
    /**
     * @var string
     */
    private $limitInCents;
    /**
     * @var bool
     */
    private $isActive;

    public function __construct(
        string $objectId,
        string $partnerName,
        string $limitInCents,
        bool $isActive
    ) {
        $this->objectId = $objectId;
        $this->partnerName = $partnerName;
        $this->limitInCents = $limitInCents;
        $this->isActive = $isActive;
    }

    /**
     * @return string
     */
    public function getObjectId(): string
    {
        return $this->objectId;
    }

    /**
     * @return string
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * @return string
     */
    public function getLimitInCents(): string
    {
        return $this->limitInCents;
    }

    /**
     * @return bool
     */
    public function isIsActive(): bool
    {
        return $this->isActive;
    }


    function jsonSerialize()
    {
        $return = get_object_vars($this);
        unset($return['isActive']);
        return $return;
    }
}
