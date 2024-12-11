<?php

namespace App\Consumer\Entities;

/**
 * Class LogInvalidSignupCoupons
 * @package App\Consumer\Entities
 */
class LogInvalidSignupCoupons extends Entity implements \JsonSerializable
{


    /**
     * @var string
     */
    private $id;

    /**
     * @var User|null
     */
    private $user;
    /**
     * @var Coupon|null
     */
    private $coupon;

    /**
     * UserCoupon constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->user = $data['user'];
        $this->coupon = null;
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
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}