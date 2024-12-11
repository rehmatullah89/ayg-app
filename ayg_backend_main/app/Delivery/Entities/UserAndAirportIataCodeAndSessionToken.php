<?php
namespace App\Delivery\Entities;

class UserAndAirportIataCodeAndSessionToken extends Entity implements \JsonSerializable
{

    /**
     * @var User
     */
    private $user;
    /**
     * @var string
     */
    private $airportIataCode;
    /**
     * @var string
     */
    private $sessionToken;

    public function __construct(User $user, string $airportIataCode, string $sessionToken)
    {
        $this->user = $user;
        $this->airportIataCode = $airportIataCode;
        $this->sessionToken = $sessionToken;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
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
    public function getSessionToken(): string
    {
        return $this->sessionToken;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
