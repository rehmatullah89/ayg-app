<?php

namespace App\Consumer\Dto\PartnerIntegration;

use App\Consumer\Dto\Dto;

class CartUserDetails extends Dto implements \JsonSerializable
{
    /**
     * @var string
     */
    private $firstName;
    /**
     * @var string
     */
    private $lastName;

    public function __construct(
        string $firstName,
        string $lastName
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getLastNameInitial(): string
    {
        return substr($this->lastName,0,1).'.';
    }


    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
