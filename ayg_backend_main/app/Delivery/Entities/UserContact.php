<?php
namespace App\Delivery\Entities;


class UserContact extends Entity implements \JsonSerializable
{

    /**
     * @var null|string
     */
    private $phoneNumber;

    public function __construct(
        ?string $phoneNumber
    ) {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return null|string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}
