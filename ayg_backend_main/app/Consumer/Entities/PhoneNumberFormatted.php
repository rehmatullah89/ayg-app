<?php

namespace App\Consumer\Entities;

/**
 * Class PhoneNumberFormatted
 * @package App\Consumer\Entities
 */
class PhoneNumberFormatted extends Entity implements \JsonSerializable
{
    private $number;
    private $nationalFormat;
    private $carrierName;

    public function __construct(
        $number,
        $nationalFormat,
        $carrierName
    )
    {
        $this->number = $number;
        $this->nationalFormat = $nationalFormat;
        $this->carrierName = $carrierName;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return mixed
     */
    public function getNationalFormat()
    {
        return $this->nationalFormat;
    }

    /**
     * @return mixed
     */
    public function getCarrierName()
    {
        return $this->carrierName;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}