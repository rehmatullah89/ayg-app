<?php

namespace App\Consumer\Entities;

class Coupon extends Entity implements \JsonSerializable
{
    const COUPON_STEP_SIGNUP='signup';
    const COUPON_STEP_AFTERSIGNUP='aftersignup';

    private $id;
    private $createdAt;
    private $updatedAt;
    private $couponCode;
    private $couponDiscountPCT;
    private $expiresTimestamp;
    private $applicableRetailerUniqueIds;
    private $isRetailerCompensated;
    private $couponDiscountCents;
    private $forSignup;

    /**
     * Coupon constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->couponCode = $data['couponCode'];
        $this->couponDiscountPCT = $data['couponDiscountPCT'];
        $this->expiresTimestamp = $data['expiresTimestamp'];
        $this->applicableRetailerUniqueIds = $data['applicableRetailerUniqueIds'];
        $this->isRetailerCompensated = $data['isRetailerCompensated'];
        $this->couponDiscountCents = $data['couponDiscountCents'];
        $this->forSignup = $data['forSignup'];
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return mixed
     */
    public function getCouponCode()
    {
        return $this->couponCode;
    }

    /**
     * @return mixed
     */
    public function getCouponDiscountPCT()
    {
        return $this->couponDiscountPCT;
    }

    /**
     * @return mixed
     */
    public function getExpiresTimestamp()
    {
        return $this->expiresTimestamp;
    }

    /**
     * @return mixed
     */
    public function getApplicableRetailerUniqueIds()
    {
        return $this->applicableRetailerUniqueIds;
    }

    /**
     * @return mixed
     */
    public function getIsRetailerCompensated()
    {
        return $this->isRetailerCompensated;
    }

    /**
     * @return mixed
     */
    public function getCouponDiscountCents()
    {
        return $this->couponDiscountCents;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function isForSignup()
    {
        return $this->forSignup;
    }

    /**
     * @param mixed $forSignup
     */
    public function setForSignup($forSignup)
    {
        $this->forSignup = $forSignup;
    }
}