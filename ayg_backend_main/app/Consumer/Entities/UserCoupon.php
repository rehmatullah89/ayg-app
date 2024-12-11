<?php

namespace App\Consumer\Entities;

/**
 * Class UserCoupon
 * @package App\Consumer\Entities
 */
class UserCoupon extends Entity implements \JsonSerializable
{


    public static $welcomeMessage = 'Welcome to AtYourGate. Your promo code was accepted and will be applied to your next order.';
    /**
     * @var string
     */
    private $id;

    /**
     * @var User|null
     */
    private $user;
    /**
     * @var Order|null
     */
    private $appliedToOrder;
    /**
     * @var Coupon|null
     */
    private $coupon;

    /**
     * @var string
     */
    private $addedOnStep;

    /**
     * UserCoupon constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->user = $data['user'];
        $this->appliedToOrder = $data['appliedToOrder'];
        $this->coupon = null;
        $this->addedOnStep = $data['addedOnStep'];
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Coupon
     */
    public function getCoupon()
    {
        return $this->coupon;
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
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param mixed $coupon
     * @return UserCoupon
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
        return $this;
    }

    /**
     * @return Order
     */
    public function getAppliedToOrder()
    {
        return $this->appliedToOrder;
    }

    /**
     * @param Order $appliedToOrder
     */
    public function setAppliedToOrder($appliedToOrder)
    {
        $this->appliedToOrder = $appliedToOrder;
    }

    /**
     * @return mixed
     */
    public function getAddedOnStep()
    {
        return $this->addedOnStep;
    }

    /**
     * @param mixed $addedOnStep
     */
    public function setAddedOnStep($addedOnStep)
    {
        $this->addedOnStep = $addedOnStep;
    }


}
