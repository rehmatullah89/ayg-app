<?php
namespace App\Tablet\Entities;

class UserRetailersAndSessionToken extends Entity implements \JsonSerializable
{
    /**
     * @var
     */
    private $sessionToken;
    /**
     * @var Retailer[]
     */
    private $retailers;
    /**
     * @var User
     */
    private $user;

    public function __construct(User $user, array $retailers, $sessionToken)
    {
        $this->user = $user;
        $this->sessionToken = $sessionToken;
        $this->retailers = $retailers;
    }

    /**
     * @return mixed
     */
    public function getSessionToken()
    {
        return $this->sessionToken;
    }

    /**
     * @return Retailer[]
     */
    public function getRetailers()
    {
        return $this->retailers;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}