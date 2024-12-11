<?php
namespace App\Background\Entities;

class RetailerPartnerShortInfo extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $partnerName;
    /**
     * @var string
     */
    private $partnerId;
    /**
     * @var string
     */
    private $airportIataCode;

    public function __construct(
        string $partnerName,
        string $partnerId,
        string $airportIataCode
    ) {
        $this->partnerName = $partnerName;
        $this->partnerId = $partnerId;
        $this->airportIataCode = $airportIataCode;
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
    public function getPartnerId(): string
    {
        return $this->partnerId;
    }

    /**
     * @return string
     */
    public function getAirportIataCode(): string
    {
        return $this->airportIataCode;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
