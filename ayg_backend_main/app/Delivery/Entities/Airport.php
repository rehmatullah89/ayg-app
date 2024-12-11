<?php
namespace App\Delivery\Entities;

class Airport extends Entity implements \JsonSerializable
{

    /**
     * @var
     */
    private $id;
    /**
     * @var string
     */
    private $iataCode;
    /**
     * @var string
     */
    private $timezone;

    public function __construct($id, string $iataCode, string $timezone)
    {
        $this->id = $id;
        $this->iataCode = $iataCode;
        $this->timezone = $timezone;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIataCode(): string
    {
        return $this->iataCode;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
