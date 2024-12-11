<?php
namespace App\Consumer\Entities;

class RetailerPartner extends Entity
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $partnerName;
    /**
     * @var int
     */
    private $partnerId;
    /**
     * @var string
     */
    private $airportIataCode;
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var string
     */
    private $itemsDirectoryName;
    /**
     * @var string
     */
    private $retailerUniqueId;

    public function __construct(
        string $id,
        string $partnerName,
        int $partnerId,
        string $airportIataCode,
        bool $isActive,
        string $itemsDirectoryName,
        string $retailerUniqueId
    ) {
        $this->partnerName = $partnerName;
        $this->partnerId = $partnerId;
        $this->airportIataCode = $airportIataCode;
        $this->isActive = $isActive;
        $this->itemsDirectoryName = $itemsDirectoryName;
        $this->id = $id;
        $this->retailerUniqueId = $retailerUniqueId;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPartnerName(): string
    {
        return $this->partnerName;
    }

    /**
     * @return int
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * @return bool
     */
    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getAirportIataCode(): string
    {
        return $this->airportIataCode;
    }

    /**
     * @return string
     */
    public function getItemsDirectoryName(): string
    {
        return $this->itemsDirectoryName;
    }

    /**
     * @return string
     */
    public function getRetailerUniqueId(): string
    {
        return $this->retailerUniqueId;
    }
}
