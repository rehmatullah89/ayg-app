<?php
namespace App\Consumer\Entities;

class OrderDeliveryPlan extends Entity
{
    /**
     * @var int
     */
    private $weekDay;
    /**
     * @var string
     */
    private $startingTime;
    /**
     * @var string
     */
    private $endingTime;
    /**
     * @var string
     */
    private $airportIataCode;

    public function __construct(
        int $weekDay,
        string $startingTime,
        string $endingTime,
        string $airportIataCode
    )
    {
        $this->weekDay = $weekDay;
        $this->startingTime = $startingTime;
        $this->endingTime = $endingTime;
        $this->airportIataCode = $airportIataCode;
    }

    /**
     * @return int
     */
    public function getWeekDay(): int
    {
        return $this->weekDay;
    }

    /**
     * @return string
     */
    public function getStartingTime(): string
    {
        return $this->startingTime;
    }

    /**
     * @return string
     */
    public function getEndingTime(): string
    {
        return $this->endingTime;
    }

    /**
     * @return string
     */
    public function getAirportIataCode(): string
    {
        return $this->airportIataCode;
    }


}
