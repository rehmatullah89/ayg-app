<?php

namespace App\Consumer\Entities;

class PhoneNumberInput extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $phoneCountryCode;
    /**
     * @var string
     */
    private $phoneNumber;

    public function __construct(array $data)
    {
        $this->phoneCountryCode = $data['phoneCountryCode'];
        $this->phoneNumber = $data['phoneNumber'];
    }

    /**
     * @return string
     */
    public function getPhoneCountryCode()
    {
        return $this->phoneCountryCode;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @return string
     */
    public function getFullNumber()
    {
        return $this->phoneCountryCode . $this->phoneNumber;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}