<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;


class RetailerOpeningHours extends Entity implements \JsonSerializable
{
    private $day;
    private $openingHours;
    private $closingHours;

    public function __construct(
        int $day, // 1 is monday
        string $openingHours,
        string $closingHours

    ) {
        $this->day = $day;
        $this->openingHours = $openingHours;
        $this->closingHours = $closingHours;
    }

    /**
     * @return int
     */
    public function getDay(): int
    {
        return $this->day;
    }

    /**
     * @return string
     */
    public function getOpeningHours(): string
    {
        return $this->openingHours;
    }

    /**
     * @return string
     */
    public function getClosingHours(): string
    {
        return $this->closingHours;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
