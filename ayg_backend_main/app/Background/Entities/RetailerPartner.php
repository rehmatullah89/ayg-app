<?php
namespace App\Background\Entities;

class RetailerPartner extends Entity
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string|null
     */
    private $parseRetailerId;
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
     * @var \DateTimeZone
     */
    private $dateTimeZone;
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
        ?string $parseRetailerId,
        string $partnerName,
        int $partnerId,
        string $airportIataCode,
        bool $isActive,
        \DateTimeZone $dateTimeZone,
        string $itemsDirectoryName,
        string $retailerUniqueId
    ) {
        $this->parseRetailerId = $parseRetailerId;
        $this->partnerName = $partnerName;
        $this->partnerId = $partnerId;
        $this->airportIataCode = $airportIataCode;
        $this->isActive = $isActive;
        $this->dateTimeZone = $dateTimeZone;
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
    public function getParseRetailerId(): ?string
    {
        return $this->parseRetailerId;
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
     * @return \DateTimeZone
     */
    public function getDateTimeZone(): \DateTimeZone
    {
        return $this->dateTimeZone;
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



    public function setItemsDirectoryName(string $itemsDirectoryName): self
    {
        $this->itemsDirectoryName = $itemsDirectoryName;
        return $this;
    }
}
