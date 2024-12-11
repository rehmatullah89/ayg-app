<?php
namespace App\Consumer\Entities;


/**
 * Class UserCredit
 * @package App\Consumer\Entities
 */
/**
 * Class UserCredit
 * @package App\Consumer\Entities
 */
class UserCredit extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var float
     */
    private $creditsInCents;
    /**
     * @var $user User
     */
    private $user;
    /**
     * @var $fromOrder Order
     */
    private $fromOrder;
    /**
     * @var mixed
     */
    private $reasonForCredit;
    /**
     * @var mixed
     */
    private $reasonForCreditCode;

    /**
     * @var mixed
     */
    private $signupCoupon;


    /**
     * UserCredit constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->creditsInCents = $data['creditsInCents'];
        $this->reasonForCredit = $data['reasonForCredit'];
        $this->reasonForCreditCode = $data['reasonForCreditCode'];
        $this->fromOrder = $data['fromOrder'];
        $this->user = $data['user'];
        $this->signupCoupon = $data['signupCoupon'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    // function called when encoded with json_encode

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getReasonForCredit()
    {
        return $this->reasonForCredit;
    }

    /**
     * @return mixed
     */
    public function getReasonForCreditCode()
    {
        return $this->reasonForCreditCode;
    }

    /**
     * @param mixed $reasonForCredit
     */
    public function setReasonForCredit($reasonForCredit)
    {
        $this->reasonForCredit = $reasonForCredit;
    }

    /**
     * @param mixed $reasonForCreditCode
     */
    public function setReasonForCreditCode($reasonForCreditCode)
    {
        $this->reasonForCreditCode = $reasonForCreditCode;
    }

    /**
     * @return Order
     */
    public function getFromOrder()
    {
        return $this->fromOrder;
    }

    /**
     * @param Order $fromOrder
     */
    public function setFromOrder($fromOrder)
    {
        $this->fromOrder = $fromOrder;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return float
     */
    public function getCreditsInCents()
    {
        return $this->creditsInCents;
    }

    /**
     * @param float $creditsInCents
     */
    public function setCreditsInCents($creditsInCents)
    {
        $this->creditsInCents = $creditsInCents;
    }

    /**
     * @param string $id
     * @return UserCredit
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getsignupCoupon()
    {
        return $this->signupCoupon;
    }

    /**
     * @param mixed $signupCoupon
     */
    public function setsignupCoupon($signupCoupon)
    {
        $this->signupCoupon = $signupCoupon;
    }
}