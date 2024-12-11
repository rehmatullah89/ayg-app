<?php
namespace App\Delivery\Entities;


class User extends Entity implements \JsonSerializable
{
    const USER_TYPE_OPS_TEAM = 2;
    const USER_TYPE_RETAILER = 1;

    const POSSIBLE_USER_TYPES = [
        self::USER_TYPE_OPS_TEAM,
        self::USER_TYPE_RETAILER,
    ];

    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $firstName;
    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $profileImage;
    /**
     * @var mixed
     */
    private $airEmpValidUntilTimestamp;
    /**
     * @var bool
     */
    private $emailVerified;
    /**
     * @var string
     */
    private $typeOfLogin;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $emailVerifyToken;

    /**
     * @var bool
     */
    private $hasDeliveryAccess;

    /**
     * @var string
     * @see User::POSSIBLE_USER_TYPES
     */
    private $retailerUserType;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->email = $data['email'];
        $this->firstName = $data['firstName'];
        $this->lastName = $data['lastName'];
        $this->profileImage = $data['profileImage'];
        $this->airEmpValidUntilTimestamp = $data['airEmpValidUntilTimestamp'];
        $this->emailVerified = $data['emailVerified'];
        $this->typeOfLogin = $data['typeOfLogin'];
        $this->username = $data['username'];
        $this->emailVerifyToken = $data['emailVerifyToken'];
        $this->hasDeliveryAccess = $data['hasDeliveryAccess'];
        $this->retailerUserType = $data['retailerUserType'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getProfileImage()
    {
        return $this->profileImage;
    }

    /**
     * @return mixed
     */
    public function getAirEmpValidUntilTimestamp()
    {
        return $this->airEmpValidUntilTimestamp;
    }

    /**
     * @return bool
     */
    public function isEmailVerified()
    {
        return $this->emailVerified;
    }

    /**
     * @param bool $emailVerified
     * @return $this
     */
    public function setEmailVerified($emailVerified)
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    /**
     * @return string
     */
    public function getTypeOfLogin()
    {
        return $this->typeOfLogin;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @param string $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @param string $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getEmailVerifyToken()
    {
        return $this->emailVerifyToken;
    }

    /**
     * @return bool
     */
    public function isHasDeliveryAccess()
    {
        return $this->hasDeliveryAccess;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getRetailerUserType()
    {
        return $this->retailerUserType;
    }

    public function getUserNameForComments()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }
}
